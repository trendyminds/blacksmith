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

3. Setup your repo to have a `.github/workflows/sandbox.yml` file and use the following:

```yaml
name: Sandbox

on: [pull_request]

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
          doc_root: /web
          host: ${{ secrets.BLACKSMITH_HOST }}
          key: ${{ secrets.BLACKSMITH_SSH_KEY }}
          path: ${{ secrets.BLACKSMITH_PATH }}

      - uses: trendyminds/github-actions-blacksmith@main
        if: github.event.action == 'closed'
        with:
          app_name: ${{ env.APP_NAME }}
          event: close
          host: ${{ secrets.BLACKSMITH_HOST }}
          key: ${{ secrets.BLACKSMITH_SSH_KEY }}
          path: ${{ secrets.BLACKSMITH_PATH }}
```

4. When you label your pull request with "sandbox" Blacksmith will now provision your sandbox environment.
