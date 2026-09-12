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

## Design decisions

- **Brand colors unchanged.** Header/footer steel blue `#8fa4bf`, green CTA
  `#81d742`, purple links `#7f54b3` all come from the existing Customizer
  settings and are re-exported as CSS variables
  (`--apx-accent`, `--apx-ink`, …) in `design-tokens.php`.
- **Header:** compact two-row flexbox header — logo + product search on top,
  primary nav + cart icon below. Sticky on tablet+. Cart/Checkout were
  removed from the nav display (still in the WP menu; the icon + mobile
  bottom bar cover them).
- **Mobile:** search hides (the handheld bottom bar already provides it),
  menu toggle + cart icon sit beside the logo.
- **Accessibility:** visible `:focus-visible` rings, screen-reader cart label
  with live count, hidden H1 on the front page, `prefers-reduced-motion`
  support.
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
