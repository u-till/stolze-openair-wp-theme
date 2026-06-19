# Stolze Openair — WordPress Theme

A native WordPress theme for the **Stolze Openair** festival (Zürich) — the
largest free open-air in the city, held each June on the Stolzewiese (Kreis 6).

It was ported from a headless Next.js frontend to render the festival's existing
content model directly in WordPress (no GraphQL layer), with visual + content
parity. The whole site is organised around festival **years**: the homepage
shows the latest edition, and every past year has its own page with lineup,
sponsors, food, side-events and a photo gallery.

## Stack

| Layer | Tech |
|---|---|
| Templating | WordPress (classic PHP templates) |
| Fields | Advanced Custom Fields (ACF) Pro |
| Styling | SCSS design system + Tailwind CSS 3 (utilities; preflight off) |
| Interactivity | Alpine.js 3 |
| Build | Vite 6 |
| Shop | WooCommerce |
| Type | Neue Haas Grotesk Display (Adobe Fonts / Typekit) |

## Getting started

```bash
npm install      # install build tooling
npm run dev      # watch + rebuild during development (vite build --watch)
npm run build    # one-shot production build
```

The build emits to `dist/` (git-ignored) and is loaded through the Vite
manifest by `functions.php`. After cloning, run `npm install && npm run build`
before activating the theme, otherwise no CSS/JS is enqueued.

The theme expects the festival content (the `jahr`, `artist`, `sponsor`,
`foodtruck` custom post types and their ACF fields) to already exist in the
WordPress install — it renders that data, it does not register it.

## Routes

| URL | Renders |
|---|---|
| `/` | the most recent festival year |
| `/year/{YYYY}` | a specific year (lineup, sponsors, food, gallery, …) |
| `/artists` | searchable archive of every artist across all years |
| `/artist/{slug}` | single artist (overlay modal) |
| `/shop/`, `/produkt/{slug}` | WooCommerce shop + product (festival-styled) |
| `/{slug}` | standard CMS pages (Infos, Mithelfen, …) |

## Project structure

```
functions.php            theme setup, asset enqueue (Vite manifest), WC support
inc/                     helpers, data queries, /year/{YYYY} rewrite
template-parts/          year-content (the home/year page composition)
woocommerce/             custom shop + single-product templates
src/scss/                ported design system (tokens + components)
src/js/app.js            Alpine components + WooCommerce variation pills
assets/                  favicon, fallback logo, Gutenberg block CSS
```

See `CLAUDE.md` for the detailed content-model map, conventions, and the
remaining to-do list.

## Credits

Festival website by [utill.ch](https://utill.ch) · artwork by various festival
visual artists. Built for [stolze-openair.ch](https://stolze-openair.ch).
