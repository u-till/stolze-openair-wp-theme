# Go-Live Todo

Priorities are ordered for launch readiness. Keep this list practical: finish P0
before launch, schedule P1 before public promotion, and treat P2 as follow-up
polish unless it blocks a real workflow.

## P0 - Must Finish Before Launch

- [x] Choose the SEO ownership model: either theme-managed meta tags or an SEO
      plugin. Avoid duplicate title, description, canonical, Open Graph and Twitter
      tags.
- [x] Add meta descriptions for the homepage, current year, static pages, artist
      pages and WooCommerce products. Use ACF/page excerpts where available and a
      concise fallback where content is missing.
- [x] Complete social sharing metadata: `og:description`, `og:type`, `og:url`,
      `twitter:title`, `twitter:description` and a tested image for every shareable
      page type. The year poster is already used as the year share image.
- [x] Review document titles for homepage, year pages, artists, shop archive,
      product pages and 404. Make sure they are specific and not all just the site
      name.
- [x] Implement canonical URLs for `/`, `/year/{YYYY}`, `/artists`,
      `/artist/{slug}`, `/shop/` and product pages.
- [ ] Generate and review XML sitemap output. Submit it in Google Search Console
      after launch.
- [ ] Review `robots.txt` and WordPress visibility settings. Production must not
      be blocked from indexing.
- [ ] Add redirects from old public URLs to the new WordPress URLs, especially
      year, artist, helper/newsletter and shop paths.
- [ ] Replace or confirm the sponsoring brochure upload at
      `/wp-content/uploads/2026/05/2026_Sponsoringbroschuere_Stolze_Openair.pdf`.
- [ ] Run checkout end to end in production mode: product selection, cart,
      checkout, payment, order email, admin order view and refund/cancel flow.
- [ ] Confirm WooCommerce payment methods, shipping rules, taxes, currency
      format, legal pages and transactional email sender.
- [ ] Verify all critical forms: helper signup, newsletter signup/contact flow,
      checkout and any embedded plugin forms.
- [ ] Set production analytics, consent/cookie configuration and privacy/legal
      page links.
- [ ] Configure caching, backups and a rollback plan before DNS cutover.

## P1 - Strongly Recommended Before Promotion

- [x] Add Organization and current-year Event structured data after confirming
      the authoritative organizer and venue details. WooCommerce Product and
      BreadcrumbList data are wired through its native structured-data generator.
- [x] Audit image alt text for year posters, artist thumbnails, sponsor logos,
      foodtruck logos, gallery images and product images.
- [ ] Compress and resize large uploads. Check that poster, gallery and product
      images have sensible generated sizes.
- [ ] Style and QA cart, checkout and my-account views so the shop does not fall
      back to raw WooCommerce defaults.
- [ ] Add a shop empty state, category/filter/sort styling and pagination styling
      for larger product sets.
- [ ] Test mobile navigation, gallery lightbox, video embed, artist search and
      variation pills on iOS Safari, Android Chrome, desktop Safari, Chrome and
      Firefox.
- [ ] Run a Lighthouse pass on homepage, current year, artists archive, shop and
      one product page. Track accessibility, SEO and performance regressions.
- [x] Check keyboard navigation and visible focus states for nav, gallery,
      video placeholder, shop forms and checkout.
- [x] Review 404 page, empty states and unavailable product states.
- [ ] Confirm production font loading and fallback behavior.

## P2 - Post-Launch Polish

- [x] Backfill historical artist/year data for older `jahr` entries.
- [ ] Add richer page-specific excerpts or ACF SEO fields where editors need
      control beyond generated meta descriptions.
- [x] Add transient or persistent cache invalidation hooks if object cache is not
      enough for high traffic pages.
- [ ] Add AJAX add-to-cart feedback and cart count behavior QA across cache
      layers.
- [ ] Document the content editing workflow for year setup, lineup publishing,
      sponsors, foodtrucks, gallery credits and shop products.
