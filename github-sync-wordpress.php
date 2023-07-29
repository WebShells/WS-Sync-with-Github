<?php
/*
Plugin Name: WS GitHub Sync Wordpress
Plugin URI: https://github.com/WebShells/git-sync-wordpress
Description: Display GitHub repository issues, commits, and pull requests using shortcodes.
Version: 1.0
Author: WebShells ( WebShells Services Co. )
Author URI: https://www.wshells.ws
Text Domain: github-sync-wordpress
*/

function time_since_creation($created_at) {
    $current_time = time();
    $time_diff = $current_time - strtotime($created_at);
    
    if ($time_diff < 60 * 60 * 24) { // Less than 1 day
        return 'Today';
    } elseif ($time_diff < 60 * 60 * 24 * 30) { // Less than 30 days
        $days_ago = floor($time_diff / (60 * 60 * 24));
        return $days_ago . ' ' . ($days_ago > 1 ? 'days' : 'day') . ' ago';
    } else { // More than 30 days
        $months_ago = floor($time_diff / (60 * 60 * 24 * 30));
        return $months_ago . ' ' . ($months_ago > 1 ? 'months' : 'month') . ' ago';
    }
}

function gitsync_issues_shortcode($atts) {
    $atts = shortcode_atts(array(
        'token' => '',
        'repository' => '',
        'owner' => '',
    ), $atts, 'gitsync_issues');

    // Make API request for open issues using cURL
    $issues_api_url = "https://api.github.com/repos/{$atts['owner']}/{$atts['repository']}/issues?state=open";
    $headers = array(
        'Authorization: token ' . $atts['token'],
        'User-Agent: Your-User-Agent', // Add your own User-Agent here
    );

	// Initialize cURL session for issues
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $issues_api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Execute cURL session for issues
    $issues_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Close cURL session for issues
    curl_close($ch);

    // Check if the API request for issues was successful
    if ($http_code !== 200) {
        return '<p>Error retrieving issues data from GitHub API.</p>';
    }

    // Check if the response is valid JSON
    $issues_data = json_decode($issues_response, true);

    // Display the data
    $output = ''; // Initialize output variable

    if (!empty($issues_data)) {
        // Sort issues by created_at date in descending order (newest to oldest)
        usort($issues_data, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        // Get the latest 10 issues
        $last_10_issues = array_slice($issues_data, 0, 10);

        // Display issues
        $output .= '<ul>';
        foreach ($last_10_issues as $issue) {
            // Check if the issue is not a pull request
            if (!isset($issue['pull_request'])) {
                // Generate list items for each issue
                $output .= '<li>';

                // Make the author image clickable and link to the user's GitHub profile
                $output .= '<a href="' . esc_url($issue['user']['html_url']) . '" target="_blank">';
                $output .= '<img src="' . esc_url($issue['user']['avatar_url']) . '" alt="' . esc_attr($issue['user']['login']) . '" width="24" height="24" style="vertical-align: middle; margin-right: 8px;" />';
                $output .= '</a>';

                // Issue Number (Clickable)
                $output .= '<a href="' . esc_url($issue['html_url']) . '" target="_blank">Issue #' . esc_html($issue['number']) . '</a>';

                // Issue Title
                $output .= ' - ' . esc_html($issue['title']);

                // Calculate the time since the issue was created
                $time_since_creation = time_since_creation($issue['created_at']);
                $output .= ' - Since (' . $time_since_creation . ')';

                // Make the author name clickable and link to the user's GitHub profile
                $output .= ' - <a href="' . esc_url($issue['user']['html_url']) . '" target="_blank">' . esc_html($issue['user']['login']) . '</a>';

                $output .= '</li>';
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

    // Make API request for open commits using cURL
    $commits_api_url = "https://api.github.com/repos/{$atts['owner']}/{$atts['repository']}/commits?per_page=10&sort=author-date&direction=desc";
    $headers = array(
        'Authorization: token ' . $atts['token'],
        'User-Agent: Your-User-Agent', // Add your own User-Agent here
    );

    // Initialize cURL session for commits
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $commits_api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Execute cURL session for commits
    $commits_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Close cURL session for commits
    curl_close($ch);

    // Check if the API request for commits was successful
    if ($http_code !== 200) {
        return '<p>Error retrieving commits data from GitHub API.</p>';
    }

    // Check if the response is valid JSON
    $commits_data = json_decode($commits_response, true);

    // Display the data
    $output = ''; // Initialize output variable

    if (!empty($commits_data)) {
        // Display commits
        $output .= '<ul>';
        foreach ($commits_data as $commit) {
            // Check if the commit message contains "Translate-URL:", and if so, exclude the exact line that contains it
            $commit_message = explode("\n", $commit['commit']['message']);
            $filtered_message = '';
            $found_translate_url = false;
            foreach ($commit_message as $line) {
                if (strpos($line, 'Translate-URL:') !== false) {
                    $found_translate_url = true;
                } else {
                    // Remove line breaks within the commit message
                    $filtered_message .= str_replace("\n", "", $line) . " ";
                }
            }

            // Generate list items for each commit
            $output .= '<li>';

            // Make the author image clickable and link to the user's GitHub profile
            $output .= '<a href="' . esc_url($commit['author']['html_url']) . '" target="_blank">';
            $output .= '<img src="' . esc_url($commit['author']['avatar_url']) . '" alt="' . esc_attr($commit['commit']['author']['name']) . '" width="24" height="24" style="vertical-align: middle; margin-right: 8px;" />';
            $output .= '</a>';

            // Commit Number (Clickable)
            $commit_number = substr($commit['sha'], 0, 7); // Extract the first 7 characters
            $output .= '<a href="' . esc_url($commit['html_url']) . '" target="_blank">Commit #' . esc_html($commit_number) . '</a>';

            // Commit Message (remove any line breaks within the commit message)
            $output .= ' - ' . esc_html($filtered_message);

            // Calculate the time since the commit was created
            $time_since_creation = time_since_creation($commit['commit']['author']['date']);
            $output .= ' - Since (' . $time_since_creation . ')';

            // Make the author name clickable and link to the user's GitHub profile
            $output .= ' - <a href="' . esc_url($commit['author']['html_url']) . '" target="_blank">' . esc_html($commit['commit']['author']['name']) . '</a>';

            $output .= '</li>';
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

    // Make API request for open pull requests using cURL
    $pulls_api_url = "https://api.github.com/repos/{$atts['owner']}/{$atts['repository']}/pulls?state=open";
    $headers = array(
        'Authorization: token ' . $atts['token'],
        'User-Agent: Your-User-Agent', // Add your own User-Agent here
    );

    // Initialize cURL session for pull requests
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $pulls_api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Execute cURL session for pull requests
    $pulls_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Close cURL session for pull requests
    curl_close($ch);

    // Check if the API request for pull requests was successful
    if ($http_code !== 200) {
        return '<p>Error retrieving pull requests data from GitHub API.</p>';
    }

    // Check if the response is valid JSON
    $pulls_data = json_decode($pulls_response, true);

    // Display the data
    $output = ''; // Initialize output variable

    if (!empty($pulls_data)) {
        // Sort pull requests by created_at date in descending order (newest to oldest)
        usort($pulls_data, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        // Get the latest 10 pull requests
        $last_10_pulls = array_slice($pulls_data, 0, 10);

        // Display pull requests
        $output .= '<ul>';
        foreach ($last_10_pulls as $pull) {
            // Generate list items for each pull request
            $output .= '<li>';

            // Make the author image clickable and link to the user's GitHub profile
            $output .= '<a href="' . esc_url($pull['user']['html_url']) . '" target="_blank">';
            $output .= '<img src="' . esc_url($pull['user']['avatar_url']) . '" alt="' . esc_attr($pull['user']['login']) . '" width="24" height="24" style="vertical-align: middle; margin-right: 8px;" />';
            $output .= '</a>';

            // Pull Request Number (Clickable)
            $output .= '<a href="' . esc_url($pull['html_url']) . '" target="_blank">PR #' . esc_html($pull['number']) . '</a>';

            // Pull Request Title
            $output .= ' - ' . esc_html($pull['title']);

            // Calculate the time since the pull request was created
            $time_since_creation = time_since_creation($pull['created_at']);
            $output .= ' - Since (' . $time_since_creation . ')';

            // Make the author name clickable and link to the user's GitHub profile
            $output .= ' - <a href="' . esc_url($pull['user']['html_url']) . '" target="_blank">' . esc_html($pull['user']['login']) . '</a>';

            $output .= '</li>';
        }
        $output .= '</ul>';
    } else {
        $output .= '<p>No open pull requests found.</p>';
    }

    return $output;
}

add_shortcode('gitsync_pull_requests', 'gitsync_pull_requests_shortcode');
?>