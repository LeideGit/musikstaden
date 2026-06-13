# Deploy Musikstaden to SiteGround (GrowBig)

Step-by-step guide for beginners. Production URL: **https://musikstaden.se**

## Before you start

- SiteGround GrowBig account with domain **musikstaden.se** pointed to SiteGround
- FTP/SFTP credentials (Site Tools → Site → FTP Accounts)
- This repository cloned or downloaded

## 1. Back up existing WordPress

1. Log in to **SiteGround Site Tools**
2. Go to **WordPress → Install & Manage**
3. Use **Back Up** on the existing site (or **Security → Backups**)
4. Download the backup zip to your computer

You chose to **replace** the old site — keep the backup safe.

## 2. Fresh WordPress (recommended)

1. Site Tools → **WordPress → Install & Manage**
2. Install WordPress on **musikstaden.se** (or remove old install first if SiteGround allows)
3. Note admin email and password

Alternatively, keep the existing install and only replace the theme + content.

## 3. Install required plugins

In WP Admin → **Plugins → Add New**, install and activate:

| Plugin | Purpose |
|--------|---------|
| [Advanced Custom Fields](https://wordpress.org/plugins/advanced-custom-fields/) | Band fields (bio, embeds, social) |
| [New User Approve](https://wordpress.org/plugins/new-user-approve/) (optional) | Extra approval gate for logins |

## 4. Upload the theme

### Option A: SFTP (recommended)

1. Site Tools → **Site → FTP Accounts** — create or use existing FTP user
2. Connect with FileZilla (or Cyberduck):
   - Host: your SiteGround FTP host (e.g. `ftp.musikstaden.se`)
   - Path: `public_html/wp-content/themes/`
3. Upload the entire folder `theme/musikstaden/` from this repo
4. Final path on server: `public_html/wp-content/themes/musikstaden/`

### Option B: ZIP upload

1. Zip the `theme/musikstaden/` folder locally
2. WP Admin → **Appearance → Themes → Add New → Upload Theme**
3. Upload the zip and activate **Musikstaden**

## 5. Activate theme and flush permalinks

1. WP Admin → **Appearance → Themes** → Activate **Musikstaden**
2. **Settings → Permalinks** → select **Post name** → **Save Changes** (important for `/artist/slug` URLs)

## 6. Seed demo data (first time only)

While logged in as admin, visit:

```
https://musikstaden.se/wp-admin/?musikstaden_seed=1
```

This creates:
- Cities, genres, event types, gig types
- 12 demo band pages
- Required pages (dashboard, login, for-artists, privacy, cookies)

Then set the front page:
- **Settings → Reading** → "A static page" → Home (created by seed)

## 7. Configure site

1. **Settings → General** — Site title: Musikstaden, language Swedish if available
2. **Settings → General** — disable "Anyone can register" (theme also disables this)
3. Create your admin workflow:
   - Artists apply at `/for-artists/`
   - Review at **WP Admin → Artist Applications**
   - Click **Approve & Create User** — artist receives set-password email

## 8. Email (important for invites & approvals)

SiteGround shared hosting sometimes blocks WP `mail()`. If emails don't arrive:

1. Site Tools → **Email → Accounts** — create e.g. `hello@musikstaden.se`
2. Install **WP Mail SMTP** plugin
3. Configure with SiteGround SMTP settings (Site Tools → Email → Mail Configuration)

## 9. SSL

Site Tools → **Security → SSL Manager** → enable Let's Encrypt for musikstaden.se (usually automatic).

## 10. Staging subdomain (optional but recommended)

1. Site Tools → **Domain → Subdomains** → create `staging.musikstaden.se`
2. Install a second WordPress there for testing
3. Deploy theme to staging first, then copy to production when ready

## GitHub Pages static preview

The repo includes `static-preview/` — a **visual-only** mirror with no WordPress. Enable GitHub Pages:

1. Push repo to GitHub
2. Repository **Settings → Pages**
3. Source: **GitHub Actions** (workflow included) or branch `main` / folder `/static-preview`

Use this to share design previews; real functionality requires WordPress on SiteGround.

## Updating the theme

1. Edit files locally in `theme/musikstaden/`
2. Upload changed files via SFTP (overwrite), or re-zip and upload
3. No need to deactivate theme for CSS/PHP updates

## Troubleshooting

| Problem | Fix |
|---------|-----|
| `/artist/name` 404 | Settings → Permalinks → Save again |
| Band fields missing | Install Advanced Custom Fields plugin |
| Login says "pending approval" | WP Admin → Users → edit user → ensure approved; or use Applications approve button |
| Embeds not showing | Paste full YouTube/Spotify/SoundCloud URLs in band editor |
| White screen | Enable WP_DEBUG in wp-config.php temporarily; check PHP 8.0+ |

## Security checklist

- Use strong admin password + 2FA if SiteGround offers it
- Keep WordPress and plugins updated
- Do not commit SFTP passwords to GitHub
