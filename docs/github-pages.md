# GitHub & Pages Setup

Push the repo to GitHub and deploy the **static preview** via GitHub Actions.

## 1. Create the repository on GitHub

1. Go to [github.com/new](https://github.com/new)
2. Repository name: **Musikstaden** (or `musikstaden`)
3. Visibility: **Public** (required for free GitHub Pages on personal accounts) or Private if you have GitHub Pro
4. **Do not** add README, .gitignore, or license (this repo already has them)
5. Click **Create repository**

## 2. Push from your Mac

In Terminal, from the project folder:

```bash
cd /Users/leidefors/Documents/Repos/Musikstaden

git remote add origin https://github.com/YOUR_USERNAME/Musikstaden.git
git push -u origin main
```

Replace `YOUR_USERNAME` with your GitHub username.

If you use SSH:

```bash
git remote add origin git@github.com:YOUR_USERNAME/Musikstaden.git
git push -u origin main
```

## 3. Enable GitHub Pages (GitHub Actions)

After the first push:

1. Open your repo on GitHub
2. Go to **Settings → Pages**
3. Under **Build and deployment**:
   - **Source:** select **GitHub Actions** (not "Deploy from a branch")
4. Save — no branch/folder selection needed

The workflow [`.github/workflows/deploy-preview.yml`](../.github/workflows/deploy-preview.yml) runs automatically on every push to `main`.

## 4. Verify deployment

1. Go to **Actions** tab — workflow **Deploy Static Preview** should be green
2. After ~1 minute, **Settings → Pages** shows the live URL, e.g.:
   - `https://YOUR_USERNAME.github.io/Musikstaden/`

Open that URL — you should see the home page with the orange **Preview only** badge.

## 5. Manual re-deploy (optional)

**Actions → Deploy Static Preview → Run workflow**

## Troubleshooting

| Issue | Fix |
|-------|-----|
| Pages URL 404 | Wait 2–3 min after first deploy; ensure Pages source is **GitHub Actions** |
| Workflow failed | Actions tab → click failed run → read error log |
| CSS/images broken on Pages | Repo name in URL must match case; links in `static-preview/` use relative paths (already correct) |
| Permission denied on push | Use GitHub personal access token or SSH key; run `gh auth login` if you install GitHub CLI |

## Install GitHub CLI (optional, recommended)

```bash
# macOS with Homebrew
brew install gh
gh auth login
gh repo create Musikstaden --public --source=. --remote=origin --push
```

Then enable Pages → GitHub Actions as in step 3.

## What gets deployed

Only the `static-preview/` folder — **not** the WordPress theme. Production WordPress stays on SiteGround; see [deploy-siteground.md](deploy-siteground.md).
