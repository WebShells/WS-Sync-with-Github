<p align="center"><img src="WebShells.png" alt="WebShells Logo" width="auto" height="150"></p>

## WS Sync with Github Shortcodes 

Now you can easily show/display open issues, new commits, and open pull requests using this plugin and it's shortcodes on your Wordpress website.
After downloading the [Plugin Files](https://github.com/WebShells/WS-Sync-with-Github/releases/download/v1.2.0/WS-Sync-with-Github.zip) and uploading it as a new plugin, just activate it and you are ready to use the shortcodes!

### 1. `[gitsync_issues]`

Displays a list of open issues from a GitHub repository.

#### Attributes

- `Token`: Your GitHub personal access token. This is required for authentication and API access.
- `repository`: The name of the GitHub repository.
- `owner`: The owner or organization of the GitHub repository.

#### Usage

```shortcode
[gitsync_issues token="YourGitHubToken" repository="YourRepository" owner="OwnerOfRepo"]
```

### 2. `[gitsync_commits]`

Displays a list of the latest commits from a GitHub repository.

#### Attributes

- `Token`: Your GitHub personal access token. This is required for authentication and API access.
- `repository`: The name of the GitHub repository.
- `owner`: The owner or organization of the GitHub repository.

#### Usage

```shortcode
[gitsync_commits token="YourGitHubToken" repository="YourRepository" owner="OwnerOfRepo"]
```

### 3. `[gitsync_pull_requests]`

Displays a list of open pull requests from a GitHub repository.

#### Attributes

- `Token`: Your GitHub personal access token. This is required for authentication and API access.
- `repository`: The name of the GitHub repository. 
- `owner`: The owner or organization of the GitHub repository.

#### Usage

```shortcode
[gitsync_pull_requests token="YourGitHubToken" repository="YourRepository" owner="OwnerOfRepo"]
```
![Screenshot](https://github.com/WebShells/WS-Sync-with-Github/blob/main/Screenshot.png?raw=true)

### Important Notes

- For public repositories, the shortcodes work without a token. Add a GitHub personal access token if you need better rate limits or private repo access.

- Replace `YourGitHubToken`, `YourRepository`, and `OwnerOfRepo` with the appropriate values for your GitHub repository.

- The plugin uses the WordPress HTTP API, so cURL-specific setup is no longer required.

- The plugin now sends a WordPress-safe User-Agent automatically.

- Insert the desired shortcode into your WordPress post, page, or widget to display GitHub repository information on your website.

Enjoy displaying GitHub repository data on your WordPress site using these simple and efficient shortcodes!

## Change Log

### v1.1

- Replaced raw cURL requests with the WordPress HTTP API.
- Added input validation for repository owner and name.
- Added short-term caching with transients to reduce GitHub API calls.
- Improved escaping and added `rel="noopener noreferrer"` to external links.
- Made commit, issue, and pull request rendering more tolerant of missing GitHub fields.
