# 🛠️ Blacksmith

Blacksmith is a convention-first review app initializer for Laravel Forge.

---

## ✅ What Blacksmith does

When you trigger the Blacksmith GitHub action for your pull request a review app will be setup using a combination of your app name, the pull request number, and your wildcard domain (Ex: mysite-123.domain.com). Blacksmith will setup the following for each sandbox:

- A new Forge site on your designated server
- A connection to your repo/branch for quick deployments
- A database (can be seeded)
- A Let's Encrypt SSL
- Robots.txt disabling crawlers

## 🧾 Requirements

If you want to utilize Blacksmith you'll need:

- A Forge server
- A Forge API token
- A wildcard domain for your review apps (Ex: *.domain.com)

## 📦 Install

1. Deploy the Blacksmith Laravel application to your Forge server
2. Setup the required `.env` variables:

```env
# Used to connect to the Forge SDK to provision your sandboxes
FORGE_TOKEN=

# The server the sandboxes should be created on
FORGE_SERVER_ID=

# The domain to use for your sandboxes (*.domain.com)
FORGE_REVIEW_APP_DOMAIN=

# MySQL credentials to use to create database backups when sandboxes are closed
FORGE_MYSQL_USER=
FORGE_MYSQL_PASSWORD=
```

3. Verify your Blacksmith homepage is being reported as "Online"
4. Ensure `php artisan schedule:run` runs every minute
5. Setup your repo to have a `.github/workflows/sandbox.yml` file and use the following:

```yaml
name: Sandbox

on:
  pull_request:
    types: [synchronize, labeled, closed, reopened]

env:
  APP_NAME: myapp

jobs:
  sandbox:
    if: contains(github.event.pull_request.labels.*.name, 'sandbox')
    runs-on: ubuntu-latest
    steps:
      - uses: trendyminds/github-actions-blacksmith@main
        with:
          app_name: ${{ env.APP_NAME }}
          pr_number: ${{ github.event.pull_request.number }}
          event: ${{ github.event.action }}
          host: ${{ secrets.BLACKSMITH_HOST }}
          key: ${{ secrets.BLACKSMITH_SSH_KEY }}
          path: ${{ secrets.BLACKSMITH_PATH }}
```

4. When you label your pull request with "sandbox" Blacksmith will now provision your sandbox environment.

## 🔧 Options

The GitHub Action comes with a number of options that have sensible defaults:

```yaml
- uses: trendyminds/github-actions-blacksmith@main
  with:
    event: ${{ github.event.action }}
    app_name: ${{ env.APP_NAME }}
    pr_number: ${{ github.event.pull_request.number }}
    php_version: php82
    doc_root: /web
    aliases: |
        test.domain.com
        another.domain.com
    user: myuser
    host: ${{ secrets.BLACKSMITH_HOST }}
    key: ${{ secrets.BLACKSMITH_SSH_KEY }}
    path: ${{ secrets.BLACKSMITH_PATH }}
```

- `event`: [Required] - The type of action being ran
- `app_name`: [Required] - The name of the app to use for the domain, database, etc.
- `pr_number`: [Required] - The PR number for the review app
- `php_version`: [Default: php83] - Which version of PHP to use for your app
- `doc_root`: [Default: /public] - The document root of your application
- `aliases`: Other domains that should also point to the site
- `user`: [Default: forge] - The SSH user to use for the connection
- `host`: [Required] - The host address to use for the connection
- `key`: [Required] - The SSH key to use for the connection
- `path`: [Required] - The path to the Blacksmith app on the server
