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

1. Deploy Blacksmith to your Forge server
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
