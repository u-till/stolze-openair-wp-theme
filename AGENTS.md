# STOLZE OPENAIR — WP Theme

Native WordPress theme for the Stolze Openair festival (Zürich). It renders the
festival content model directly in WordPress with classic PHP templates, ACF Pro,
Tailwind CSS, Alpine.js and WooCommerce.

## Environment

- **Local URL**: http://localhost:10058 (LocalWP, site "Stolze Openair")
- **WP-CLI**: `wp-cli.yml` + `.wp-cli-bootstrap.php` at the install root
  (`app/public/`). The bootstrap forces `DB_HOST` to the LocalWP socket
  (`…/Local/run/Bug2k94Eg/mysql/mysqld.sock`).
- **OOM**: the default 128M limit is exhausted by the active plugins. Always run:
  `php -d memory_limit=-1 /opt/homebrew/Cellar/wp-cli/2.12.0/bin/wp <cmd>`
  (add `-d display_errors=0 -d error_reporting=0` to silence deprecation noise).

## Stack

| Layer  | Package                                             | Version             |
| ------ | --------------------------------------------------- | ------------------- |
| CSS    | Tailwind CSS (preflight OFF) + migrated components  | 3.4.19              |
| JS     | Alpine.js                                           | 3.15                |
| Build  | Vite                                                | 6.4                 |
| CMS    | WordPress                                           | 7.0                 |
| Fields | ACF Pro                                             | active              |
| Font   | Typekit `neue-haas-grotesk-display` (kit `vro0vys`) | —                   |

### Build

```bash
npm run dev    # vite build --watch (development)
npm run build  # one-shot production build
```

Assets ship from `dist/` via `dist/.vite/manifest.json` (read in `functions.php`).
The JS bundle imports `src/css/tailwind.css`. JS is served as `type="module"`
(via the `script_loader_tag` filter).

**Styling approach**: `src/css/tailwind.css` is the single styling entry. It
contains Tailwind directives, the migrated festival normalize/tokens, and the
component styles. Tailwind preflight is disabled so it does not fight the
festival normalize.

## Content model

CPTs: `jahr` (23 years), `artist` (127), `sponsor` (42), `foodtruck` (19).
ACF groups:

- **Artist**: `buhne` (stage), `slot` (datetime), `jahr` (relationship → serialized
  IDs), `gallery`; + `social_media_links` repeater (`link_icon`/`link_label`/`url`).
- **Sponsor**: `jahr`, `website_url`.
- **Foodtruck**: `jahr`, `website_url`, `vegetarisches_angebot`, `veganes_angebot`
  (veg flags are available but currently not displayed).
- **Year**: `poster` (mobile), `poster_desktop`, `logo`, `daten` (repeater of `datum`),
  `background_color`, `primary_color` (+ unused secondary/text/font), `side_events_info`,
  `visual_artist_name/url`, `photographer_credits` (repeater), `gallery`.

Per-year theming: only `background_color` (wrapper bg) and `primary_color`
(→ `--color--primary` + `--primary-hover-color`) are applied.

## Template map

| Route            | Template             | Notes                                                                                                      |
| ---------------- | -------------------- | ---------------------------------------------------------------------------------------------------------- |
| `/`              | `front-page.php`     | most-recent `jahr` → `template-parts/year-content.php`                                                     |
| `/year/{YYYY}`   | `single-jahr.php`    | rewrite in `inc/rewrites.php` resolves the 4-digit **title** (slugs are y2024/2026-2…); 404s unknown years |
| `/artists`       | `archive-artist.php` | CPT `has_archive: "artists"`; Alpine search + live count                                                   |
| `/artist/{slug}` | `single-artist.php`  | full-page overlay modal                                                                                    |
| `/{slug}`        | `page.php`           | title + block content; shortcodes (`[eventadmin]`) render natively                                         |
| 404              | `404.php`            |                                                                                                            |

`header.php` = fixed nav (year selector left, `menu-top` section anchors on
home/year only, `menu-bottom` page links right) + Alpine mobile burger.
`footer.php` only closes `wp_footer()` — the rich festival footer lives inside
`year-content.php`.

## Alpine components (`src/js/app.js`)

`stolzeNav` (mobile drawer), `timetableItem` (random hover-image offset),
`videoSection` (lazy YouTube embed), `artistSearch` (archive filter + count),
`galleryLightbox` (open/next/prev/keyboard).

## PHP conventions

Text domain `stolze`; helpers prefixed `stolze_`. Key files: `inc/helpers.php`
(German UTC date formatting, section-title renderer, year theming),
`inc/data.php` (festival content queries), `inc/rewrites.php`
(`/year/{YYYY}`).

## Implementation notes

- **Grid layout** uses one responsive CSS grid with per-item borders.
- **Internal links localised**: the "Unterstütze uns" and Newsletter buttons point
  to local `/helferanmeldung/` and `/newsletter/`. The Sponsoringbroschüre PDF is
  expected in the local WordPress uploads directory.

## WooCommerce (shop)

Festival-styled, custom template overrides in `woocommerce/`:

- `woocommerce/archive-product.php` — `/shop/`: section-title + the bordered
  grid of product cards (image + name + price bar), rows chunked by 4.
- `woocommerce/single-product.php` — `/produkt/{slug}`: Alpine image gallery
  (main + thumbnail strip), title/price/short-desc, the core WC variation form,
  full description, and a "Zurück zum Shop" back-link.
- **Variation options are rendered as pills**, not dropdowns: the native WC
  `<select>`s stay in the DOM (hidden) so the variation engine still works;
  `initVariationPills()` in `src/js/app.js` mirrors them as `.variation-pill`
  buttons that drive the selects via a dispatched `change`.
- The festival push-button is scoped to **`span.button`** so it never collides
  with WC's `<button class="button">`; the add-to-cart button is styled via
  `.product-summary__cart .single_add_to_cart_button` (specificity beats WC).

Store config set during the build (DB, not theme): `woocommerce_shop_page_id`
= the "Shop" page (1715); currency = **CHF** (left + space, Swiss separators).
Permalinks: product base `produkt`, category base `produkt-kategorie`.

## Out of scope (later)

- **Cart / checkout / my-account** still use WooCommerce's default styling
  (only shop + single product are festival-styled so far).
- **Newsletter submission** (MailPoet) — the section is a link-out for now.
- The helferformular (`[eventadmin]` volunteer plugin) **works** and renders
  natively.

## Todo

See `GO-LIVE-TODO.md` for the current launch checklist.
