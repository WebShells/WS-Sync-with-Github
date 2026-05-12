<?php
/*
Plugin Name: WS Sync with Github
Plugin URI: https://github.com/WebShells/WS-Sync-with-Github
Description: Display GitHub repository issues, commits, and pull requests using shortcodes.
Version: 1.1
Author: WebShells ( WebShells Services Co. )
Author URI: https://www.wshells.ws
Text Domain: WS-Sync-with-Github
*/

if (!defined('ABSPATH')) {
    exit;
}

function gitsync_time_since_creation($created_at) {
    $created_timestamp = strtotime($created_at);

    if (!$created_timestamp) {
        return '';
    }

    $time_diff = time() - $created_timestamp;

    if ($time_diff < DAY_IN_SECONDS) {
        return 'Today';
    }

    if ($time_diff < 30 * DAY_IN_SECONDS) {
        $days_ago = (int) floor($time_diff / DAY_IN_SECONDS);
        return $days_ago . ' ' . ($days_ago === 1 ? 'day' : 'days') . ' ago';
    }

    $months_ago = (int) floor($time_diff / (30 * DAY_IN_SECONDS));
    return $months_ago . ' ' . ($months_ago === 1 ? 'month' : 'months') . ' ago';
}

function gitsync_fetch_github_data($endpoint, $token = '') {
    $cache_key = 'gitsync_' . md5($endpoint . '|' . $token);
    $cached_data = get_transient($cache_key);

    if (false !== $cached_data) {
        return $cached_data;
    }

    $headers = array(
        'Accept' => 'application/vnd.github+json',
        'User-Agent' => 'WS Sync with Github WordPress Plugin',
    );

    if (!empty($token)) {
        $headers['Authorization'] = 'Bearer ' . $token;
    }

    $response = wp_remote_get($endpoint, array(
        'timeout' => 15,
        'headers' => $headers,
    ));

    if (is_wp_error($response)) {
        return $response;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if (200 !== $status_code) {
        return new WP_Error(
            'gitsync_http_error',
            sprintf('GitHub API returned HTTP %d.', $status_code)
        );
    }

    $data = json_decode($body, true);

    if (!is_array($data)) {
        return new WP_Error('gitsync_invalid_json', 'Invalid response from GitHub API.');
    }

    set_transient($cache_key, $data, 5 * MINUTE_IN_SECONDS);

    return $data;
}

function gitsync_render_error($message) {
    return '<p>' . esc_html($message) . '</p>';
}

function gitsync_build_list_item($label, $item_url, $title, $time_since, $author_name, $author_url, $avatar_url, $avatar_alt) {
    $output = '<li>';

    if (!empty($avatar_url) && !empty($author_url)) {
        $output .= '<a href="' . esc_url($author_url) . '" target="_blank" rel="noopener noreferrer">';
        $output .= '<img src="' . esc_url($avatar_url) . '" alt="' . esc_attr($avatar_alt) . '" width="24" height="24" style="vertical-align: middle; margin-right: 8px;" />';
        $output .= '</a>';
    }

    $output .= '<a href="' . esc_url($item_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($label) . '</a>';
    $output .= ' - ' . esc_html($title);

    if (!empty($time_since)) {
        $output .= ' - Since (' . esc_html($time_since) . ')';
    }

    if (!empty($author_name) && !empty($author_url)) {
        $output .= ' - <a href="' . esc_url($author_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($author_name) . '</a>';
    }

    $output .= '</li>';

    return $output;
}

function gitsync_issues_shortcode($atts) {
    $atts = shortcode_atts(array(
        'token' => '',
        'repository' => '',
        'owner' => '',
    ), $atts, 'gitsync_issues');

    $owner = sanitize_text_field($atts['owner']);
    $repository = sanitize_text_field($atts['repository']);
    $token = trim($atts['token']);

    if (empty($owner) || empty($repository)) {
        return gitsync_render_error('Repository owner and repository name are required.');
    }

    $issues_api_url = add_query_arg(array('state' => 'open', 'per_page' => 100), sprintf('https://api.github.com/repos/%s/%s/issues', rawurlencode($owner), rawurlencode($repository)));
    $issues_data = gitsync_fetch_github_data($issues_api_url, $token);

    if (is_wp_error($issues_data)) {
        return gitsync_render_error('Error retrieving issues data from GitHub API.');
    }

    $output = '';

    if (!empty($issues_data)) {
        // Sort issues by created_at date in descending order (newest to oldest)
        usort($issues_data, function ($a, $b) {
            return strtotime($b['created_at'] ?? '') <=> strtotime($a['created_at'] ?? '');
        });

        // Get the latest 10 issues
        $last_10_issues = array_slice($issues_data, 0, 10);

        // Display issues
        $output .= '<ul>';
        foreach ($last_10_issues as $issue) {
            // Check if the issue is not a pull request
            if (!isset($issue['pull_request'])) {
                $user = $issue['user'] ?? array();
                $output .= gitsync_build_list_item(
                    'Issue #' . ($issue['number'] ?? ''),
                    $issue['html_url'] ?? '',
                    $issue['title'] ?? '',
                    gitsync_time_since_creation($issue['created_at'] ?? ''),
                    $user['login'] ?? '',
                    $user['html_url'] ?? '',
                    $user['avatar_url'] ?? '',
                    $user['login'] ?? ''
                );
            }
        }
        $output .= '</ul>';
    } else {
        $output .= '<p>No open issues found.</p>';
    }

    return $output;
}

add_shortcode('gitsync_issues', 'gitsync_issues_shortcode');

function gitsync_commits_shortcode($atts) {
    $atts = shortcode_atts(array(
        'token' => '',
        'repository' => '',
        'owner' => '',
    ), $atts, 'gitsync_commits');

    $owner = sanitize_text_field($atts['owner']);
    $repository = sanitize_text_field($atts['repository']);
    $token = trim($atts['token']);

    if (empty($owner) || empty($repository)) {
        return gitsync_render_error('Repository owner and repository name are required.');
    }

    $commits_api_url = add_query_arg(array('per_page' => 10), sprintf('https://api.github.com/repos/%s/%s/commits', rawurlencode($owner), rawurlencode($repository)));
    $commits_data = gitsync_fetch_github_data($commits_api_url, $token);

    if (is_wp_error($commits_data)) {
        return gitsync_render_error('Error retrieving commits data from GitHub API.');
    }

    $output = '';

    if (!empty($commits_data)) {
        // Display commits
        $output .= '<ul>';
        foreach ($commits_data as $commit) {
            $author = $commit['author'] ?? array();
            $commit_author = $commit['commit']['author'] ?? array();
            $message_lines = preg_split('/\r\n|\r|\n/', $commit['commit']['message'] ?? '');
            $filtered_message = array();

            foreach ($message_lines as $line) {
                if (strpos($line, 'Translate-URL:') !== false) {
                    continue;
                }

                $line = trim($line);
                if ($line !== '') {
                    $filtered_message[] = $line;
                }
            }

            $display_message = trim(implode(' ', $filtered_message));
            $author_name = $commit_author['name'] ?? __('Unknown author', 'WS-Sync-with-Github');
            $author_url = $author['html_url'] ?? '';
            $avatar_url = $author['avatar_url'] ?? '';
            $commit_number = substr($commit['sha'] ?? '', 0, 7);

            $output .= gitsync_build_list_item(
                'Commit #' . $commit_number,
                $commit['html_url'] ?? '',
                $display_message,
                gitsync_time_since_creation($commit_author['date'] ?? ''),
                $author_name,
                $author_url,
                $avatar_url,
                $author_name
            );
        }
        $output .= '</ul>';
    } else {
        $output .= '<p>No new commits found.</p>';
    }
    
    return $output;
}

add_shortcode('gitsync_commits', 'gitsync_commits_shortcode');

function gitsync_pull_requests_shortcode($atts) {
    $atts = shortcode_atts(array(
        'token' => '',
        'repository' => '',
        'owner' => '',
    ), $atts, 'gitsync_pull_requests');

    $owner = sanitize_text_field($atts['owner']);
    $repository = sanitize_text_field($atts['repository']);
    $token = trim($atts['token']);

    if (empty($owner) || empty($repository)) {
        return gitsync_render_error('Repository owner and repository name are required.');
    }

    $pulls_api_url = add_query_arg(array('state' => 'open', 'per_page' => 100), sprintf('https://api.github.com/repos/%s/%s/pulls', rawurlencode($owner), rawurlencode($repository)));
    $pulls_data = gitsync_fetch_github_data($pulls_api_url, $token);

    if (is_wp_error($pulls_data)) {
        return gitsync_render_error('Error retrieving pull requests data from GitHub API.');
    }

    $output = '';

    if (!empty($pulls_data)) {
        // Sort pull requests by created_at date in descending order (newest to oldest)
        usort($pulls_data, function ($a, $b) {
            return strtotime($b['created_at'] ?? '') <=> strtotime($a['created_at'] ?? '');
        });

        // Get the latest 10 pull requests
        $last_10_pulls = array_slice($pulls_data, 0, 10);

        // Display pull requests
        $output .= '<ul>';
        foreach ($last_10_pulls as $pull) {
            $user = $pull['user'] ?? array();
            $output .= gitsync_build_list_item(
                'PR #' . ($pull['number'] ?? ''),
                $pull['html_url'] ?? '',
                $pull['title'] ?? '',
                gitsync_time_since_creation($pull['created_at'] ?? ''),
                $user['login'] ?? '',
                $user['html_url'] ?? '',
                $user['avatar_url'] ?? '',
                $user['login'] ?? ''
            );
        }
        $output .= '</ul>';
    } else {
        $output .= '<p>No open pull requests found.</p>';
    }

    return $output;
}

add_shortcode('gitsync_pull_requests', 'gitsync_pull_requests_shortcode');
?>
