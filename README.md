# Musikstaden

Local music discovery platform for Sweden — find artists and bands in your city.

## Repository structure

```
theme/musikstaden/   WordPress theme (deploy to SiteGround)
static-preview/      Static HTML preview for GitHub Pages
docs/                Deployment and setup guides
assets/              Logo and design references
```

## Quick start (local)

1. Install [Local WP](https://localwp.com/) or use any WordPress 6.x environment.
2. Copy `theme/musikstaden/` to `wp-content/themes/musikstaden/`.
3. Install plugins: **Advanced Custom Fields**, **New User Approve** (optional).
4. Activate the Musikstaden theme.
5. Go to **Settings → Permalinks** and click Save (flushes rewrite rules).
6. Run seed: visit `/wp-admin/?musikstaden_seed=1` while logged in as admin.

## Production (SiteGround)

See [docs/deploy-siteground.md](docs/deploy-siteground.md).

## Static preview (GitHub Pages)

The `static-preview/` folder is deployed automatically via GitHub Actions to GitHub Pages for visual QA (no WordPress backend).

**Repo:** https://github.com/LeideGit/musikstaden

### First-time push (run in Terminal)

```bash
cd /Users/leidefors/Documents/Repos/Musikstaden
git push -u origin main
```

If prompted, sign in to GitHub (browser or personal access token).

### Enable Pages

1. GitHub repo → **Settings → Pages**
2. **Source:** **GitHub Actions**
3. After push, check **Actions** tab — workflow deploys to `https://leidegit.github.io/musikstaden/`

Full details: [docs/github-pages.md](docs/github-pages.md)
