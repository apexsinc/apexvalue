# Apex Value — WordPress Child Theme

Child theme of [Storefront](https://woocommerce.com/products/storefront/) for
[apexvalue.com](https://www.apexvalue.com) (authorized Davis Instruments
distributor, Philippines).

## Why a child theme

Storefront stays the parent (`Template: storefront`), so every Storefront and
WooCommerce feature keeps working — menus, widgets, Customizer, cart
fragments, handheld footer bar, etc. All customization lives here and
survives parent updates.

## Structure

| File | Purpose |
|------|---------|
| `style.css` | Design system: typography, colors, buttons, forms, header, cards, footer, responsive rules |
| `functions.php` | Bootstrap — loads the files below (child loads **before** the parent, which enables pluggable overrides) |
| `inc/class-apexvalue.php` | Theme class: body classes, reusable menu location |
| `inc/template-overrides.php` | Pluggable overrides: `storefront_cart_link()` → icon + count chip, `storefront_header_cart()` → icon + mini-cart dropdown |
| `inc/template-functions.php` | Filter that hides Cart/Checkout links from header menus (the cart icon covers them; nothing is deleted from the menu) |
| `inc/design-tokens.php` | CSS custom properties synced with the Customizer + hidden H1 on the front page |
| `inc/cta-band.php` | Pre-footer CTA band, fully editable in Appearance → Customize → *Apex Value CTA Band* (empty headline hides it; hidden on cart/checkout/account) |
| `assets/js/cta-preview.js` | Customizer live-preview for the CTA band |
| `inc/hero.php` | Homepage hero — kicker/headline/text/2 buttons + background color, all editable in *Apex Value Hero* (empty headline hides it and restores the hidden H1; also removes the lone "Home" breadcrumb on the front page) |
| `assets/js/hero-preview.js` | Customizer live-preview for the hero |
| `inc/footer.php` | Pluggable `storefront_footer_widgets()` override: three-column footer (brand + Facebook, Explore menu, contact details) editable in *Apex Value Footer*. If any `footer-*` widget area is ever activated, Storefront's original widget rendering is used instead |
| `inc/quote-flow.php` | **Request-a-Quotation flow** — header "Request a Quote" pill (desktop), Quote item in the mobile handheld bar, "Quotation" badge on quote-only products (replaces the empty price), reassurance note on the quote basket + checkout. Editable in *Apex Value Quote CTA* |
| `AGENTS.md` | Repository rules: single contributor identity (`janasco <jaymaranasco@gmail.com>`), no AI attribution |
| `UPDATING.md` | Checklist for updating the Storefront **parent** theme — lists every override and how to re-verify it |
| `.githooks/commit-msg` | Enforces the AGENTS.md rules at commit time (run `git config core.hooksPath .githooks` after cloning) |
| `404.php` | Custom 404: oversized 404 badge, friendly copy, homepage/products buttons, product search, explore pills, "New in store" grid (WooCommerce promoted products preserved) |
| `inc/performance.php` | Image performance: single-product gallery main image gets `loading=eager` + `fetchpriority=high` (it's the LCP element); content images after the first are lazy-loaded (closes WP core's skip-first-N gap for block patterns) |

## The quotation flow

APEX Value sells via **quotation** (Quotes for WooCommerce): product buttons say "Request Quote", the cart is the quote basket, and checkout submits through the "Ask for Quotation" gateway. The theme's conversion path is therefore:

**Header pill / hero / mobile bar → Products → "Request Quote" on a product → quote basket (reassurance note) → checkout ("no payment taken" note) → team replies with a formal quotation.**

Every CTA in that path defaults to `/inquiry/` (the Airtable inquiry form) or the basket itself, and each label/URL is editable in the Customizer (*Apex Value Quote CTA*, *Apex Value Hero*, *Apex Value CTA Band*).

## Design decisions

- **Brand colors unchanged.** Header/footer steel blue `#8fa4bf`, green CTA
  `#81d742`, purple links `#7f54b3` all come from the existing Customizer
  settings and are re-exported as CSS variables
  (`--apx-accent`, `--apx-ink`, …) in `design-tokens.php`.
- **Header:** compact two-row flexbox header — logo + product search on top,
  primary nav + cart icon below. Sticky on tablet+. Cart/Checkout were
  removed from the nav display (still in the WP menu; the icon + mobile
  bottom bar cover them).
- **Homepage hero:** deep navy band (default `#1d3d5c`, Customizer-editable)
  with kicker chip, H1, supporting text and two CTAs. It owns the page's
  visible H1; the accessibility fallback H1 is suppressed while active.
- **Footer:** three columns (brand/about + Facebook icon, Explore links
  reusing the primary menu, contact list with icons). Footer text colors are
  **adaptive** — the theme computes the background's WCAG luminance
  (`apexvalue_hex_luminance()`) and emits light or dark text tokens, so the
  current black footer background gets light text automatically.
- **Mobile:** search hides (the handheld bottom bar already provides it),
  menu toggle + cart icon sit beside the logo.
- **Accessibility:** visible `:focus-visible` rings, screen-reader cart label
  with live count, hidden H1 on the front page, `prefers-reduced-motion`
  support.
- **WP spacing tokens:** the homepage patterns reference
  `--wp--style--root--padding-*` / `--wp--custom--gap--horizontal` which
  Storefront doesn't define — the child theme provides them so section
  padding doesn't collapse to zero.
- **Product page:** framed gallery with accent-highlighted thumbnails,
  divider rhythm in the summary (short description → price → add-to-cart),
  flexbox cart row (full-width CTA on mobile), quiet meta line, card-style
  tab list and polished related-products heading.
- **Self-hosted fonts:** Source Sans Pro (300/400/600/700/900, latin
  subset, ~15 KB per face) ships in `assets/fonts/` with `font-display:
  swap` and unicode-range. The render-blocking Google Fonts request and
  third-party connections are gone (GDPR-friendlier too). CSS weights
  match shipped faces exactly — no synthetic bolding.
- **Sidebar:** block-widget headings follow the compact widget-title
  treatment; latest-posts/archives/categories lists styled; an empty
  widget was removed from the shop sidebar (recoverable in Inactive
  Widgets).
- **Blog:** archive cards with hover lift, scoped entry-title sizing,
  accent blockquotes and links, card-styled prev/next navigation and
  comment bubbles. Static page titles are untouched.
- **Notices & account/checkout:** Storefront notices recoloured to
  left-accent cards; My Account navigation as a soft card list with accent
  active state; login/register forms carded; checkboxes/radios follow the
  accent via `accent-color`.
- **404:** custom `404.php` replaces Storefront's plain version while
  keeping WooCommerce product search + recent products.
- **AJAX cart:** the add-to-cart fragment handler keeps working because it
  calls the overridden `storefront_cart_link()` directly.
- **Search results:** both search flavours match the design system — the
  product search uses the WooCommerce grid, while SiteSEO's full-site search
  gets product hits cardified via a `post_class` filter (`.apex-card`) and a
  carded page header. Reviews, the cookie bar and the empty quote-basket
  state follow the same tokens.

## Recent updates

- **1.9.2** — Structured data: **FAQPage JSON-LD on the `/faqs/`
  archive** (10 question/answer pairs, built from the posts shown so
  pagination stays valid) and removed the **hollow duplicate Product
  node** on product pages — siteseo-pro's auto Product schema emitted
  null offers/image next to WooCommerce's complete one (DB option
  edited, backup in `/var/backups/apex-schema-20260914/`). Checkout
  audit: already quote-appropriate (title, gateway, button, optional
  company/phone). New *Safe-editing workflow* section in this README.
- **1.9.1** — Homepage **"How quoting works"** section above the CTA
  band: three-step explainer (browse → submit → quotation) with a
  Request-a-Quotation button; all copy editable in Appearance →
  Customize → Apex Value How It Works, empty heading hides the section.
  Also fixed `--apx-accent-dark` being used but never defined (accent
  button hovers were silently falling back to transparent): the token is
  now computed from the Customizer accent (25% darker, guarded against
  near-black values). Contrast stress test: 14/14 token combinations
  pass WCAG AA (9 also AAA).
- **1.9.0** — Collections visually enriched: homepage collection cards
  now show a **product image tile** (first product's thumbnail per
  collection, cached in a 1-hour transient; falls back to the term
  thumbnail when set, or a monogram tile when no image exists), and the
  **Shop dropdown + handheld menu highlight the collection currently
  being browsed** (accent colour/weight on category archives, Shop kept
  in its active state as the dropdown parent).
- **1.8.2** — Collections now reachable from every page: a **Shop
  dropdown** (desktop + handheld) injects the ten product collections as a
  sub-menu under the Shop item via `wp_nav_menu_objects` (display-only,
  with keyboard `:focus-within` support), the footer gains a
  **Collections** column heading with the same links, and the homepage
  gets a **"Shop by collection"** strip under the hero — tinted band,
  responsive 2/3/5-column card grid with per-collection product counts,
  `prefers-reduced-motion` respected. One shared helper
  (`apexvalue_get_collections()`) now feeds the chips, dropdown, footer
  and homepage, so adding a product category updates all four surfaces
  automatically.
- **1.8.1** — Collection chip navigation on shop & product-category
  archives: a chip row linking every non-empty product category
  (Marine, Accessory, Sensor, Weather Station, …) now sits after the
  archive title, with the current category highlighted. These
  `/collections/<slug>/` archives previously had no inbound navigation
  anywhere on the site. Mobile chips scroll sideways instead of stacking;
  chips are hidden on basket/checkout like the quote CTA.
- **1.8.0** — Accessibility, SEO & email-branding pass: fixed an empty
  homepage H1 (hero headline regression), aria-labels for third-party
  review-carousel buttons, alt text on 8 product content images; SiteSEO
  homepage title/description, per-page meta descriptions
  (home/shop/inquiry) and default og:image configured; WooCommerce emails
  re-branded via the email-templates plugin (logo header, ink/tint palette,
  accent links) — DB-side settings, not theme files. Mobile layout and
  performance audits: no regressions found.
- **1.7.2** — Legacy `/contact/` requests now 301-redirect to `/contact-us/`.
- **1.7.1** — Search results polished (SiteSEO product hits cardified via
  `post_class`), review list/form, cookie bar, empty quote-basket badge
  (Customizer-driven), fixed a `radius:` typo in the stylesheet.
- **1.7.0** — Quotation experience: header "Request a Quote" pill, mobile
  Quote item, "Quotation" badge replacing the empty price hole, basket +
  checkout reassurance notes, hero/CTA defaults point at `/inquiry/`.
- **1.6.x** — Motion layer: header elevation, scroll reveals with staggered
  cascades, hero entrance, cart bump, tactile buttons, footer
  micro-interactions (all reduced-motion & no-JS safe).
- **1.5.0** — Source Sans Pro self-hosted (`assets/fonts/`), Google Fonts
  request removed; weights 500/800 corrected to shipped 600/900.
- **1.4.0** — Sidebar cleanup + font preconnect/display-swap.

## Development notes

- Bump `Version:` in `style.css` **and** `APEXVALUE_VERSION` in
  `functions.php` together (cache-busting uses both).
- Testable via `curl -H "Host: www.apexvalue.com" http://127.0.0.1/` on the
  server.
- Caching stack on this server: SpeedyCache (page cache) + APCu object cache.
  Clear both after theme switches (APCu is cleared from a web request, not
  the CLI).

## Safe-editing workflow

1. **Theme files only via git.** Every change to this directory goes
   through a commit — never hand-edit and leave the tree dirty. Files
   outside the theme (plugins, uploads, `wp-content` root) are NOT in
   version control: before touching a plugin option or postmeta, save a
   dated backup to `/var/backups/` first (see `apex-schema-*`,
   `apex-email-*`, `alt-fix-*` for the pattern).
2. **Lint before you flush.** `php -l` every changed PHP file; check CSS
   braces balance after stylesheet edits.
3. **Flush caches from a web request**, then verify with
   `curl -H "Host: www.apexvalue.com"` — expect `ver=<current>` on theme
   assets and the edited markup actually present in the HTML.
4. **Keep versions in lockstep** (`style.css` + `functions.php`), bump on
   every user-visible change, and note it under *Recent updates*.
5. **DB-side settings are the escape hatch, not the default.** Plugin
   options (SiteSEO, email-templates, WooCommerce) are changed only when
   the theme cannot own the concern — and always with a backup + a note
   here.
6. **Commits:** author `janasco <jaymaranasco@gmail.com>`, single
   contributor, no machine attribution (a server-side hook enforces
   this). Tag releases `vX.Y.Z` with a one-line summary.

## Server operations log

Changes made outside version control (DB/plugin settings), newest
first. Every entry had a verification step and, for option edits, a
backup under `/var/backups/`.

- **2026-09-14 — plugin rationalisation (27 → 22 active).** Deactivated
  five plugins with zero runtime impact, each verified after the
  change: `yaymail` (email-templates is the active email renderer —
  branding re-verified through `wrap_message()`), `wp-fastest-cache`
  (was double page-caching alongside SpeedyCache; confirmed both were
  live first), `gosmtp` (self-defers to gosmtp-pro; real send tested
  via `wp_mail()` = true), `loginizer` (self-defers to
  loginizer-security), `fileorganizer` (self-defers to
  fileorganizer-pro). Updated `loginizer-security` 2.0.8 → 2.1.0.
  siteseo + siteseo-pro both kept (Pro is a true add-on, not a
  duplicate).
- **2026-09-14 — checkout terms: intentionally NOT wired.** The
  *Refund and Returns Policy* page (ID 25) is still WooCommerce's
  draft placeholder ("This is a sample page." + stock template).
  Wiring it to checkout requires real policy text from the owner
  first.
- **2026-09-12 — email branding** (`mailtpl_opts`, backup
  `apex-email-20260912/`), **SiteSEO titles/descriptions/OG**
  (`apex-seo-20260912/`), **hollow Product auto-schema removed**
  (`apex-schema-20260914/`), **product-content alt text**
  (`alt-fix-20260912/`).
