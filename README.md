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

Every CTA in that path defaults to `/inquiry/` (the Airtable inquiry form) or the basket itself, and each label/URL is editable in the Customizer (*Apex Value Quote CTA*, *Apex Value Hero*, *Apex Value CTA Band*). The cart and checkout pages use the WooCommerce **block** versions (client-rendered); the empty basket ends with a server-rendered "Browse products" CTA. Checkout's terms checkbox activates once the Refund & Returns Policy page is published.

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

- **1.11.6** — Performance: cart-page payload cut from ~2.85 MB to ~2.1 MB
  and two long-standing "jQuery is not defined" page errors fixed.
  - **Editor stack on the cart page:** WooCommerce's cart/checkout block
    assets legitimately depend on `wp-components`/`wp-plugins` (verified:
    the bundles call `wp.components.SVG` / `wp.plugins.PluginArea` at
    runtime — do not dequeue them). The *password-meter* chain, however,
    was removable: `zxcvbn-async` (~803 KB), `password-strength-meter`
    and `wc-password-strength-meter` are now dequeued on the front end
    (account registration is off, guest checkout on — nothing to attach
    to). Dequeue + dependency-scrub on both `wp_enqueue_scripts` and a
    late `wp_footer` pass, because WC registers block bundles after
    `wp_enqueue_scripts`.
  - **Unused font face dropped:** the self-hosted Source Sans Pro 300
    weight had zero `font-weight: 300` declarations — face and file
    removed (weights in use: 400, 600, 700, 900).
  - **jQuery-order fixes:** jQuery is deferred by the theme, so any
    script that *uses* jQuery must be deferred too (deferred scripts
    execute in document order). The siteseo consent bar and the quotes
    plugin's `qwc-product-js` (registered with empty deps despite using
    jQuery — plugin bug) now get deferred; dependency-based deferral
    catches any other jQuery-dependent script generically.
  - **Verified end-to-end:** real-browser quote flow (product → Request
    Quote → checkout) mounts the checkout block with zero console/page
    errors; 11-page render audit all green; PHP log quiet.

- **1.11.5** — Accessibility + performance audit (axe-core 4.10 via the
  headless-Chromium harness, plus full page-weight measurement). Result:
  all six audited page types (home/shop/product/contact/cart/account)
  render axe-clean — zero serious violations.
  - **Shop dropdown links had empty labels** — the auto-generated
    collection sub-menu items set `post_title` but never `title`, which
    is what the nav walker actually renders: 10 clickable-but-invisible
    rows in every header menu (axe `link-name` x10–15). Root-cause fix
    in the WP_Post factory in `inc/template-overrides.php`.
  - **Image-only links in raw content HTML unnamed** — the homepage
    collection strip's `<a><img alt=""></a>` links now get `aria-label`
    from the linked URL's slug ("Vantage Vue"), via a `render_block`
    filter; falls back to attachment title, then filename.
  - **Add-to-cart button contrast** — the classic product-page button
    rendered dark-on-dark (Storefront default); now the primary CTA
    green treatment with matching hover.
  - **Reviews-widget contrast, cascade-proof** — the plugin hard-codes
    its caption color with `!important`, but exposes it as a CSS
    variable; the theme sets `--powered-color` on `.wp-gr` instead of
    fighting the cascade.
  - **404 numeral redesigned** — the 10%-opacity ghost (0.5:1) is now a
    solid muted brand blue (5.8:1) at reduced scale; axe-clean.
  - **Keyboard + AT fixes (progressive JS)**: reviews carousel scroll
    container gains `tabindex=0`; duplicate shop pagination navs get
    distinct labels (top/bottom); empty `<h3>`s hidden from the a11y
    tree; carousel prev/next buttons named.
  - **Font preloads** — the two weights used on every page (400/700)
    are preloaded in `<head>`, killing late-swap flash (the unused
    300 weight is a candidate for removal in a future pass).
  - **Product-card images had no alt text** — the core product-image
    block omits `alt` even when the media library has it; theme filter
    now fills it from the attachment data (fallback: product name).
  - **Contact page weight flagged** (plugin-side, not fixed here): the
    Airtable form pulls ~9.3MB/694 requests — documented for follow-up
    (iframe placeholder or self-hosted form). Known accepted notes:
    WooCommerce's prev/next thumbnail `image-redundant-alt` (minor,
    core pattern) and its decoy console lines (intentional).
- **1.11.3** — Full-site render audit (headless Chromium, 11 page types)
  and the follow-up fixes it surfaced:
  - **COEP disabled** — the security-header plugin's default
    `Cross-Origin-Embedder-Policy: credentialless` blocked every
    cross-origin image without a CORP header (team photos on
    `img.apexvalue.com`, review avatars). Confirmed via browser console
    (`ERR_BLOCKED_BY_RESPONSE…Coep`), disabled at the plugin config
    (backup covers it), re-verified: all images load, zero console
    errors across all 11 page types.
  - **CSP extended twice more**, evidence-driven from real browser
    violations: `challenges.cloudflare.com` (Turnstile on the product
    page) and `static.airtable.com` (the embed snippet the Airtable
    iframes need).
  - **Cart/checkout block components themed**: buttons, text inputs,
    checkboxes/radios (`accent-color`), radio option cards, totals
    footer, notice banners — all on the design tokens, verified
    against the WC 10.7 build's actual class names. Interactive
    verification in a real browser recommended at next checkout.
- **1.11.2** — Fixed the site-wide visual breakage reported from the live
  browser (white hero, missing page margins, header/footer colors wrong):
  - **Root cause: the `security-header` plugin was serving its factory
    Content-Security-Policy** (`style-src 'self'` without
    `'unsafe-inline'`), which silently discarded **every inline `<style>`
    block and style attribute** on the public site. WordPress prints
    design tokens, theme.json spacing, and Customizer colors inline —
    so the hero lost its navy background, the page lost the WP spacing
    tokens (the "no margins" symptom), and header/footer fell back to
    Storefront defaults. It also blocked Google Analytics, the
    Cloudflare beacon, review-avatar images, and the Airtable iframes.
    Confirmed with a headless-Chromium render before/after: hero was
    `rgba(0,0,0,0)` (white page) → now `rgb(29,61,92)`; header now
    `rgb(143,164,191)`; footer now `rgb(0,0,0)`; gutters restored.
  - **CSP rebuilt properly** (custom mode, backed up first): allows
    self + inline styles (WordPress' architecture), GA + gtag + CF
    beacon scripts, Google/Airtable frames, https images. See the
    server ops log for the exact policy.
  - **White sections tinted**: brand-spotlight and how-it-works used
    `--apx-paper` (#fff) with white cards — white-on-white. Both now
    use `--apx-tint` so cards read as cards.
- **1.11.1** — Quote-flow audit + the long-blocked refund-policy item:
  - **Quote-flow audit (filled-basket path).** Cart/checkout are
    WooCommerce **blocks** (React, rendered client-side via the Store
    API), which is why quote-mode labels don't appear in curl HTML —
    verified working through the Store API instead: cart items return,
    and the plugin's `quotes-for-woocommerce` registers a "Request
    Quote" payment-method block, renames cart/checkout titles when the
    basket is quotable, and swaps the proceed-to-checkout button via
    its own blocks script. Session-driven testing from the server is
    impractical (Secure-flagged cookies + block hydration), so this
    path is confirmed at the API level; **a real-browser pass through
    add → basket → submit is still worth doing manually.**
  - **Empty quote-basket CTA** (`inc/quote-flow.php`): a themed
    "Browse products" buttons-block button now closes the empty-cart
    block, which previously ended with no next step. Server-rendered,
    scoped to the cart only.
  - **Refund & Returns Policy drafted** (still **draft**, not
    published — content approval is yours): replaced WooCommerce's
    sample placeholder with a real policy aligned to the site's
    one-year warranty post and the quote-based flow (30-day returns,
    non-returnable items, defect/damage handling, refund timing).
    Original saved to `/var/backups/apex-policy-draft-20260914/`.
    **Review and publish it to activate checkout's terms checkbox.**
- **1.11.0** — Cookie-consent bar fixed and enabled, brand spotlight, decluttered transactional pages:
  - **Cookie bar now actually renders.** GA tracking was active (GA4, consent-gated in auto-accept mode) but the consent notice could never display — its render hook only registers when the `opt_out_edit_choice` flag is set, and that flag was never saved. Enabled it (config-level, with `siteseo_google_analytics_option_name` backed up to `/var/backups/apex-siteseo-20260914/`) and restyled the bar onto the design tokens (hero-navy surface, accent button). The bar's assets were already loading unconditionally; now something justifies them.
  - **Homepage brand spotlight** (`inc/brand-spotlight.php`, new). Data-driven cards generated from live `product_brand` terms — thumbnail, name, product count — plus Customizer-editable heading/intro/button. Self-hides if no brands exist or the heading is emptied.
  - **Blog widgets removed from cart/checkout/account pages** via a pluggable `storefront_get_sidebar()` override. Those transactional pages now get full content width; blog and FAQ pages are untouched.
  - **`apex-flush.php` hardened:** added APCu object-cache clearing (wp-cli writes were invisible to FPM until flushed in web context) — and it turned out the helper itself could only ever run via the `Host: www.apexvalue.com` header, which is now the documented invocation.
- **1.10.1** — Correction + brand archive discoverability:
  - **Reverted the v1.10.0 removal of Storefront's WC Brands CSS/JS.**
    The original check for brand terms had failed silently (wp-cli root
    guard swallowed the output) and the "no brands" conclusion was
    wrong: a `davis-instruments` brand on 324 products is in active
    use, including a Davis-filtered product collection on the
    homepage. Assets restored; lesson recorded in
    `inc/performance.php` — verify a probe actually returned data.
  - **Footer "Brands" group added** — the `/brand/davis-instruments/`
    archive was reachable only by URL with zero inbound links. Now
    rendered from live `product_brand` terms (data-driven, appears
    only when brands exist), alongside Explore/Collections/Contact.
  - dashicons + PVC frontend CSS removal stands (counter verifiably
    never displayed).
  - Plugin config flagged, not changed: siteseo's cookies-bar assets
    load site-wide though no Google Analytics exists and no bar
    renders — a siteseo quirk; harmless but worth knowing.
- **1.10.0** — Front-end performance pass (first asset-level audit of
  the site):
  - **Zero render-blocking scripts**: jQuery, jQuery Migrate and the
    theme script now defer (all other head scripts already arrived
    deferred). Verified 9/9 head scripts deferred; safe because every
    jQuery dependent in the tag chain is also deferred and no plugin
    prints inline code that calls jQuery synchronously (scanned all
    page types).
  - **Removed unused CSS**: dashicons (59 KB) + the Post Views Counter
    frontend stylesheet — its counter is never displayed (display
    option off, zero `[post_views]` users, no dashicons classes in any
    guest markup); Storefront's WC Brands extension CSS+JS (~5 KB) —
    no brand taxonomy or terms exist and no brand markup renders.
  - **Emoji shims dropped**: the twemoji detector/converter, TinyMCE
    emoji stylesheet and s.w.org resource hints — the design system
    uses SVG/CSS icons only (~4.5 KB + one request saved).
  - Net effect: **~70 KB less on every page**, three fewer head
    requests, and a fully deferred script graph. gzip remains on
    (~80% wire reduction); images already lazy-load with correct
    LCP hints from the earlier passes.
  - All removals are dequeue-level with evidence recorded inline in
    `inc/performance.php` — delete the corresponding block if a view
    counter or brand filter ever appears.
- **1.9.3** — Whole-site polish pass (audited all 16 page types):
  content-embedded iframes (the Airtable forms on Inquiry/Contact and
  any future embeds) now get an accessible `title`, `loading="lazy"`
  and `referrerpolicy` via a `the_content` filter; authored block
  content (lists, separators, captions, block buttons) brought onto the
  design tokens; **DB content fix** — nine `h4` section headings on
  three Solution pages promoted to `h3` (h1→h4 skip; backups in
  `/var/backups/apex-heading-fix-20260914/`). Remaining known skip:
  `/solutions/` and detail pages open content at h3 directly under the
  page-title h1 — left as-is (h3 styling is intentional); first
  sections could become h2 if desired.
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

- **2026-09-14 — Content-Security-Policy reconfigured (site-breaking
  fix).** The `security-header` (Inspired Monks) plugin was active with
  its factory CSP `default-src 'self'; script-src 'self'; style-src
  'self';` — which blocks ALL inline styles and scripts. WordPress
  depends on inline styles (design tokens, theme.json, Customizer
  colors), so the public site rendered unstyled in several places:
  white hero, missing spacing tokens/margins, default header/footer
  colors; GA, CF beacon, review avatars and Airtable iframes were
  blocked too. Backed up `inspiredmonks_security_header_options` to
  `/var/backups/apex-csp-20260914/`, then set the CSP to custom mode:
  `default-src 'self'; script-src 'self' 'unsafe-inline'
  https://www.googletagmanager.com https://static.cloudflareinsights.com;
  style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;
  font-src 'self' data: https://fonts.gstatic.com; connect-src 'self'
  https://www.google-analytics.com https://analytics.google.com
  https://region1.google-analytics.com https://www.googletagmanager.com
  https://cloudflareinsights.com; frame-src https://airtable.com
  https://*.airtable.com https://www.google.com https://maps.google.com;
  child-src (same as frame-src); object-src 'none'; base-uri 'self';
  form-action 'self'; frame-ancestors 'self'; upgrade-insecure-requests`.
  Verified public header + headless-render before/after. If a new
  integration is added, extend the relevant directive — do not revert
  to the plugin's default mode. **Also 2026-09-14:** CSP extended for
  `challenges.cloudflare.com` (Turnstile) and `static.airtable.com`
  (embed snippet), and `Cross-Origin-Embedder-Policy` disabled — its
  default `credentialless` mode blocked cross-origin images without
  CORP headers (img.apexvalue.com team photos, review avatars; browser
  error `ERR_BLOCKED_BY_RESPONSE…Coep`).

- **2026-09-14 — refund-policy draft replaced (post 25, still draft).**
  WooCommerce's sample placeholder content replaced with a real draft
  aligned to the warranty post and quote flow. Original content backed
  up to `/var/backups/apex-policy-draft-20260914/post-25-original.html`.
  **Not published — awaiting your review.**

- **2026-09-14 — cookie-consent configuration + flush-helper fix.**
  Backed up `siteseo_toggle` and `siteseo_google_analytics_option_name`
  to `/var/backups/apex-siteseo-20260914/`, then set
  `google_analytics_opt_out_edit_choice = true` so the consent bar
  (previously unreachable: assets loaded, notice never rendered) now
  displays, restyled to the theme palette via the plugin's own color
  settings. GA4 tracking behaviour unchanged (auto-accept mode kept).
  Also: `apex-flush.php` gained `apcu_clear_cache()` — wp-cli-side
  writes to options were invisible to FPM (APCu object cache) until a
  web-context flush ran; and it was established the helper only ever
  executes when called with the `Host: www.apexvalue.com` header (the
  default vhost 301s it to https, where nothing listens locally).
  Verified: bar renders on all page types with the themed styles.

- **2026-09-14 — update-readiness drill.** Fresh full DB snapshot
  (`/var/backups/apex-db-20260914/apex-db-full.sql.gz`, 103 tables,
  gzip-integrity checked), UpdraftPlus safety net confirmed active
  (daily backups, 59 archives on disk), error-log review clean
  (WP_DEBUG off, no debug.log, FPM quiet), 15/15 page smoke test, mail
  transport last-logged `status=sent provider=smtp`. Everything on the
  site is current — next plugin/theme update can proceed against this
  snapshot.
- **2026-09-14 — plugin rationalisation, corrected after a regression
  (27 → 25 active).** Deactivated for good: `yaymail` (email-templates
  is the active email renderer — branding re-verified through
  `wrap_message()`) and `wp-fastest-cache` (was double page-caching
  alongside SpeedyCache — confirmed serving cached pages first).
  **Reverted the same day:** `gosmtp`, `loginizer`, `fileorganizer`.
  Their "self-defer to Pro" code only fires for OLD pro versions; with
  current Pro installed the FREE plugin keeps loading and gosmtp free
  is the actual SMTP transport (pro only adds logging/reports on top).
  Evidence: with gosmtp free off, `wp_mail()` still returned true but
  the message never reached the mail log — the unauthenticated PHP
  `mail()` fallback likely black-holed it; after reactivation the test
  send logged `status=sent, provider=smtp`. Lesson recorded: a true
  runtime behaviour test beats a source-code reading. Kept both
  siteseo + siteseo-pro (Pro is a real add-on). Updated
  `loginizer-security` 2.0.8 → 2.1.0. Two test mails were sent to
  superadmin@apexsinc.com during verification (only test 2 was
  properly transported).
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
