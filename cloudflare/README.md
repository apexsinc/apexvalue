# apexvalue.com on Cloudflare

Rebuild of apexvalue.com (quotation-only distributor site, no payments)
as a static Astro site on Cloudflare Pages.

## Target stack

| Piece | Choice |
|---|---|
| Framework | Astro, static output, `@astrojs/cloudflare` adapter |
| Quote flow | Pages Function `POST /api/quote` → Turnstile verify → D1 insert (sequential quote no.) → Resend emails (team + customer) → Airtable row |
| Images | R2 (current 148 MB / 592 attachments fit free tier) |
| Editing | Decap CMS, GitHub-backed |
| Search | Pagefind |
| Redirects | `_redirects` / Bulk Redirects (8 rules from Redirection plugin) |
| Analytics | Cloudflare Web Analytics (replaces GA4) |
| Reviews | Worker fetching Google Business reviews (replaces WP plugin) |

## Source data (on the old container)

- Products: 223 (11 `product_cat`, 1 `product_brand`, 434 tags) — export Woo CSV/JSON
- Pages: 23 (incl. 5 solution pages, inquiry, contact, reviews) — convert to Markdown
- Posts: 35 — convert to Markdown
- Uploads: `/var/www/html/wordpress/wp-content/uploads/` → R2
- Redirects: `wp_redirection_items` (8 rows)
- SEO: SiteSEO titles/OG → frontmatter

## Scaffold (next)

```sh
cd cloudflare
npm create astro@latest . -- --template minimal --typescript strict --no-install --no-git --yes
npm install
npx astro add cloudflare --yes
npm run build
```

Reuse design tokens from `../wordpress/style.css` (`--apx-*`) for visual parity.
