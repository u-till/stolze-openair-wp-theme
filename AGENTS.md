# STOLZE OPENAIR — WP Theme

Native WordPress theme for the Stolze Openair festival (Zürich). Ported from the
headless Next.js frontend (`github.com/u-till/stolze-openair-website`) to render
the **existing** content model directly — visual + content parity with the
former GraphQL-driven site.

## Environment

- **Local URL**: http://localhost:10058 (LocalWP, site "Stolze Openair")
- **WP-CLI**: `wp-cli.yml` + `.wp-cli-bootstrap.php` at the install root
  (`app/public/`). The bootstrap forces `DB_HOST` to the LocalWP socket
  (`…/Local/run/Bug2k94Eg/mysql/mysqld.sock`).
- **OOM**: the default 128M limit is exhausted by the active plugins. Always run:
  `php -d memory_limit=-1 /opt/homebrew/Cellar/wp-cli/2.12.0/bin/wp <cmd>`
  (add `-d display_errors=0 -d error_reporting=0` to silence deprecation noise).
- **Next.js source**: cloned to `/tmp/stolze-src` during the port (design reference).

## Stack

| Layer  | Package                                             | Version             |
| ------ | --------------------------------------------------- | ------------------- |
| CSS    | Tailwind CSS (preflight OFF) + ported SCSS          | 3.4.19 / sass 1.101 |
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
The JS bundle imports `src/css/tailwind.css` + `src/scss/app.scss`. JS is served
as `type="module"` (via the `script_loader_tag` filter).

**Styling approach**: the Next.js design system (`src/styles/*`) is ported into
`src/scss/tokens/` (colors, sizes, typography, breakpoints, normalize, utils) and
`src/scss/app.scss` (component styles). The three near-identical grids
(gallery / sponsors / foodtrucks) are scoped by an ancestor wrapper since the CSS
module class names (`.grid`, `.grid-item`, …) become global. Tailwind preflight
is disabled so it doesn't fight the ported normalize/design.

## Content model (existing — no DB changes)

CPTs: `jahr` (23 years), `artist` (127), `sponsor` (42), `foodtruck` (19).
ACF groups:

- **Artist**: `buhne` (stage), `slot` (datetime), `jahr` (relationship → serialized
  IDs), `gallery`; + `social_media_links` repeater (`link_icon`/`link_label`/`url`).
- **Sponsor**: `jahr`, `website_url`.
- **Foodtruck**: `jahr`, `website_url`, `vegetarisches_angebot`, `veganes_angebot`
  (veg flags are not displayed — the frontend never showed them).
- **Year**: `poster` (mobile), `poster_desktop`, `logo`, `daten` (repeater of `datum`),
  `background_color`, `primary_color` (+ unused secondary/text/font), `side_events_info`,
  `visual_artist_name/url`, `photographer_credits` (repeater), `gallery`.

Per-year theming: only `background_color` (wrapper bg) and `primary_color`
(→ `--color--primary` + `--primary-hover-color`) are applied — matching the
Next.js `YearContent`, which fetched but never used text/secondary colors.

## Template map (Next.js route → WP)

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
`year-content.php` (as in the Next layout).

## Alpine components (`src/js/app.js`)

`stolzeNav` (mobile drawer), `timetableItem` (random hover-image offset),
`videoSection` (lazy YouTube embed), `artistSearch` (archive filter + count),
`galleryLightbox` (open/next/prev/keyboard).

## PHP conventions

Text domain `stolze`; helpers prefixed `stolze_`. Key files: `inc/helpers.php`
(German UTC date formatting mirroring `formatDate.tsx`, section-title renderer,
year theming), `inc/data.php` (queries mirroring the GraphQL), `inc/rewrites.php`
(`/year/{YYYY}`).

## Deliberate deviations from the live Next.js site

- **Grid layout** uses one responsive CSS grid (per-item borders) instead of the
  Next.js JS window-size row-chunking — same bordered-grid look, no client JS.
- **Internal links localised**: the "Unterstütze uns" and Newsletter buttons point
  to local `/helferanmeldung/` and `/newsletter/` (which exist here) rather than the
  `new.stolze-openair.ch` URLs the old frontend hard-coded. The Sponsoringbroschüre
  PDF stays external (only hosted there).

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

## Todo (another day)

### WooCommerce (do before the lineup import)

- **Style cart / checkout / my-account** to match the festival look (currently
  WooCommerce defaults).
- **Shop archive polish**: product category filter / sorting, pagination styling
  once there are more products, and an empty/coming-soon state.
- **Cart UX**: AJAX add-to-cart + "added" confirmation, and review the
  add-to-cart → cart → checkout flow end-to-end.
- Decide on payments/shipping config before going live (store currency is CHF;
  shop page + permalinks are already wired).

### Content

- **Import old lineups from openairguide.net** — backfill historical artist /
  year data for the older `jahr` entries from openairguide.net.
