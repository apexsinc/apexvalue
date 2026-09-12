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
| `AGENTS.md` | Repository rules: single contributor identity (`janasco <jaymaranasco@gmail.com>`), no AI attribution |
| `.githooks/commit-msg` | Enforces the AGENTS.md rules at commit time (run `git config core.hooksPath .githooks` after cloning) |

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
- **AJAX cart:** the add-to-cart fragment handler keeps working because it
  calls the overridden `storefront_cart_link()` directly.

## Development notes

- Bump `Version:` in `style.css` **and** `APEXVALUE_VERSION` in
  `functions.php` together (cache-busting uses both).
- Testable via `curl -H "Host: www.apexvalue.com" http://127.0.0.1/` on the
  server.
- Caching stack on this server: SpeedyCache (page cache) + APCu object cache.
  Clear both after theme switches (APCu is cleared from a web request, not
  the CLI).
