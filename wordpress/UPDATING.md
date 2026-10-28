# Updating the Storefront parent theme

The `apexvalue` child theme overrides specific Storefront behaviour. Before
updating the **parent** Storefront theme, run through this checklist.

## 1. Before the update

- [ ] Note the currently installed Storefront version (Appearance → Themes →
      Storefront, or `wp-content/themes/storefront/style.css`).
- [ ] Back up the database and `wp-content/themes/`.
- [ ] Read the Storefront changelog
      (https://developer.woocommerce.com/storefront/changelog/) for the
      versions between the installed one and the target.
- [ ] On a **staging copy** first, never straight on production.

## 2. What we override — re-check after every parent update

| Override | Where | How it can break |
|---|---|---|
| Header cart icon + mini-cart | `inc/template-overrides.php` (pluggable `storefront_cart_link()`, `storefront_header_cart()`) | Parent renames the pluggable functions or changes the fragment filter name |
| Footer (3-column) | `inc/footer.php` (pluggable `storefront_footer_widgets()`) | Parent changes footer hooks (`storefront_footer`), widget region names (`footer-1..4`) or the `.footer-widgets .block` grid classes |
| Nav cleanup (Cart/Checkout hidden) | `inc/template-functions.php` (filter on `wp_nav_menu_…items`) | Menu item IDs change in the WP admin — update the ID list if the menu is rebuilt |
| Menu reuse (primary → handheld) | `inc/class-apexvalue.php` | Parent changes the `storefront_handheld_footer_bar` / navigation markup |
| Homepage hero / CTA band / footer copy | `inc/hero.php`, `inc/cta-band.php`, `inc/footer.php` (Customizer sections, hooks `storefront_before_content`, `storefront_before_footer`) | Hook names change or breadcrumbs move around `storefront_before_content` |
| 404 page | `404.php` (template override) | Parent template restructure rarely matters — ours is standalone; check `storefront_do_shortcode()` still exists |
| CSS coupling | `style.css` | Parent class/selector renames in `header.php`, `woocommerce.css`, `icons.css` (Font Awesome family names) |

CSS selectors that are **load-order dependent** (our sheet must load after the
Customizer inline styles — verify after update):

- `.site-footer a:not(.button):not(.components-button)` hardening
- `.site-footer h*.apex-footer__heading` hardening
- Mini-cart `left:-999em` reveal overrides

## 3. After the update (staging)

1. Flush caches: SpeedyCache purge **and** APCu (clear via a web request —
   see README "Development notes").
2. Walk every template: home, shop, single product, cart, checkout,
   my-account, search, category/archive, single post, 404.
3. Check `view-source` for `Fatal error`, `Warning`, `Deprecated`.
4. Verify the cart icon count updates on AJAX add-to-cart.
5. Verify mobile: hamburger menu opens, no horizontal scroll, handheld
   bottom bar intact.
6. Compare the header/footer visually against the pre-update screenshots.
7. Run `php -l` on the theme if any PHP errors surface.

## 4. Rollback

- Restore `wp-content/themes/storefront/` from the backup, or reinstall the
  previous Storefront version from the WordPress.org theme archive.
- The child theme is version-controlled — `git checkout v1.3.1` if needed.
