## WS Github Sync Wordpress Shortcodes 

Now you can easily show open issues, new commits, and open pull requests using this plugin and it's shortcodes.
After downloading the [Plugin Files](https://github.com/WebShells/Github-Sync-Wordpress/releases/download/v1.0.0/WS-Github-Sync-Wordpress.zip) and uploading it as a new plugin, just activate it and you are ready to use the shortcodes!

### 1. `[gitsync_issues]`

Displays a list of open issues from a GitHub repository.

#### Attributes

- `Token`: Your GitHub personal access token. This is required for authentication and API access.
- `repository`: The name of the GitHub repository.
- `owner`: The owner or organization of the GitHub repository.

#### Usage

```shortcode
[gitsync_issues token="YourGitHubToken" repository="YourRepository" owner="YourOwner"]
```

### 2. `[gitsync_commits]`

Displays a list of the latest commits from a GitHub repository.

#### Attributes

- `Token`: Your GitHub personal access token. This is required for authentication and API access.
- `repository`: The name of the GitHub repository.
- `owner`: The owner or organization of the GitHub repository.

#### Usage

```shortcode
[gitsync_commits token="YourGitHubToken" repository="YourRepository" owner="YourOwner"]
```

### 3. `[gitsync_pull_requests]`

Displays a list of open pull requests from a GitHub repository.

#### Attributes

- `Token`: Your GitHub personal access token. This is required for authentication and API access.
- `repository`: The name of the GitHub repository. 
- `owner`: The owner or organization of the GitHub repository.

#### Usage

```shortcode
[gitsync_pull_requests token="YourGitHubToken" repository="YourRepository" owner="YourOwner"]
```

### Important Notes

- Before using these shortcodes, make sure you have a valid GitHub personal access token. You can create a token with appropriate permissions in your GitHub account settings.

- Replace `YourGitHubToken`, `YourRepository`, and `YourOwner` with the appropriate values for your GitHub repository.

- Ensure that cURL is enabled on your server to make API requests to GitHub.

- Add your own User-Agent in the code (where specified) to comply with GitHub API guidelines.

- Insert the desired shortcode into your WordPress post, page, or widget to display GitHub repository information on your website.

Enjoy displaying GitHub repository data on your WordPress site using these simple and efficient shortcodes!
