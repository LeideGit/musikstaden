# Musikstaden Theme Changelog

When you upload a new zip, check **Appearance → Themes** — the version number should match the latest row below.

| Version | Name | Changes |
|---------|------|---------|
| 1.0.28 | Hero Title | Homepage hero: "Lokala artister i din stad, för ditt event" |
| 1.0.27 | Mobile Search Nav | Search icon visible beside hamburger on mobile, outside the menu |
| 1.0.26 | Studio Polish | Fix Band Studio browser tab title; hide member invite UI on dashboard |
| 1.0.25 | Embed Save Fix | Preserve Spotify/YouTube iframe embeds when saving in Band Studio; fix checkbox dropdown layout |
| 1.0.24 | Embed Save Fix | (superseded by 1.0.25 — same changes, version bump for deploy) |
| 1.0.23 | Studio UI | Purple h3 section titles on Band Studio; genre and booking type checkbox dropdowns |
| 1.0.22 | Studio Route Fix | Fix Band Studio 404 (Nothing found) when Edit is clicked; rewrite + fallback routing |
| 1.0.21 | Band Studio | Frontend band editor for artists; no WordPress admin; dashboard checklist and publish flow; search button in top nav |
| 1.0.20 | Mobile Nav & Logo | Light purple skyline logo, mobile hamburger menu, darker band hero fade, orange gig-type tags |
| 1.0.18 | Band Page Hero | Full-width band hero, collapsible booking, sidebar similar artists with genre fallback |
| 1.0.17 | Booking Email | Inquiries go to band booking email; Reply-To routes artist replies to the booker |
| 1.0.16 | Booking Form | Secure booking inquiry form on band pages; artist email stays private |
| 1.0.15 | Booking Info | Optional booking contact field (email or phone) on band profiles |
| 1.0.14 | Remove Event Types | Delete unused event_type taxonomy and obsolete booking terms from database |
| 1.0.13 | Merge Booking Types | Remove event_type; use gig_type (Bokningstyp) for search and band tags |
| 1.0.12 | Split Embed Fields | Separate Spotify and YouTube embed fields in band editor |
| 1.0.11 | Social Icons | Recognizable YouTube, Spotify, Instagram, Facebook and website icons on band pages |
| 1.0.10 | Embed Textarea Fix | Replace ACF Pro repeater with textarea for Spotify/YouTube paste (free ACF) |
| 1.0.9 | Media Embeds | Paste Spotify/YouTube embed code or links on band profiles |
| 1.0.8 | Homepage Hero | Minimal header (logo, Ansök, Logga in); Swedish default; hero banner with logo and search |
| 1.0.7 | Approval Email Link | Always include wp-login password reset link in approval email |
| 1.0.6 | Critical Error Fix | Merge welcome email into applications.php; PHP 7.4 compatibility; safer admin types |
| 1.0.5 | Welcome Email | Clear Swedish approval email with next steps and password link |
| 1.0.4 | New Logo | Official Musikstaden logo (PNG) in header and static preview |
| 1.0.3 | Create Missing Band | Button to create band for already-approved applications; redirect to Bands list |
| 1.0.2 | Auto Band on Approve | Approval creates draft band; applications list filters by status |
| 1.0.1 | Approval Fix | Fix artist application approve button; show version in admin |
| 1.0.0 | Initial Beta | First public theme release |

## How to release a new version

1. Edit `inc/version.php` — bump `MUSIKSTADEN_VERSION` and `MUSIKSTADEN_VERSION_NAME`
2. Edit `style.css` — bump `Version:` to the same number
3. Add a row to this file
4. Zip the `musikstaden` folder as `musikstaden-1.0.x.zip`
5. Upload via **Appearance → Themes → Add New → Upload Theme**

WordPress replaces the existing `musikstaden` folder. Confirm the new version appears on the Themes screen.
