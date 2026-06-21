<?php
/**
 * Stolze Openair theme bootstrap.
 *
 * @package stolze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'STOLZE_DIR', get_template_directory() );
define( 'STOLZE_URI', get_template_directory_uri() );

require_once STOLZE_DIR . '/inc/helpers.php';
require_once STOLZE_DIR . '/inc/data.php';
require_once STOLZE_DIR . '/inc/rewrites.php';

/**
 * Theme supports + nav menus.
 */
function stolze_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );

	// Menus are looked up by slug (menu-top / menu-bottom already exist in the DB),
	// but we register locations too so they are manageable.
	register_nav_menus(
		array(
			'menu-top'    => 'Menu Top (section anchors)',
			'menu-bottom' => 'Menu Bottom (page links)',
		)
	);

	// WooCommerce: declare support so the shop runs without the "incompatible
	// theme" notice. Product markup lives in our own woocommerce/ templates.
	add_theme_support( 'woocommerce' );
}
add_action( 'after_setup_theme', 'stolze_setup' );

/**
 * Read the Vite build manifest.
 *
 * @return array
 */
function stolze_manifest() {
	static $manifest = null;
	if ( null !== $manifest ) {
		return $manifest;
	}
	$path     = STOLZE_DIR . '/dist/.vite/manifest.json';
	$manifest = file_exists( $path ) ? json_decode( file_get_contents( $path ), true ) : array();
	return $manifest;
}

/**
 * Enqueue built CSS/JS from dist/ via the manifest, plus the Typekit font.
 */
function stolze_assets() {
	// Typekit (neue-haas-grotesk-display).
	wp_enqueue_style( 'stolze-typekit', 'https://use.typekit.net/vro0vys.css', array(), null );

	$manifest = stolze_manifest();
	$entry    = isset( $manifest['src/js/app.js'] ) ? $manifest['src/js/app.js'] : null;

	if ( $entry ) {
		if ( ! empty( $entry['css'] ) ) {
			foreach ( $entry['css'] as $i => $css_file ) {
				wp_enqueue_style( 'stolze-app-' . $i, STOLZE_URI . '/dist/' . $css_file, array( 'stolze-typekit' ), null );
			}
		}
		wp_enqueue_script( 'stolze-app', STOLZE_URI . '/dist/' . $entry['file'], array(), null, true );
	}

	// Gutenberg block-content styles for the WYSIWYG / page bodies.
	wp_enqueue_style( 'stolze-gutenberg', STOLZE_URI . '/assets/gutenberg.css', array(), '1.0.0' );
}
add_action( 'wp_enqueue_scripts', 'stolze_assets' );

/**
 * Serve the app bundle as an ES module.
 */
function stolze_module_type( $tag, $handle ) {
	if ( 'stolze-app' === $handle ) {
		$tag = str_replace( '<script ', '<script type="module" ', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'stolze_module_type', 10, 2 );

/**
 * The cart-count badge markup for the header cart icon.
 *
 * Shared by header.php and the AJAX fragment so they stay byte-identical; the
 * `is-empty` class hides the badge (in CSS) when the cart is empty.
 *
 * @return string
 */
function stolze_cart_count_badge() {
	$count = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
	$class = 'cart-contents-count' . ( $count > 0 ? '' : ' is-empty' );
	return '<span class="' . esc_attr( $class ) . '">' . esc_html( $count ) . '</span>';
}

/**
 * Keep the header cart-count badge in sync after AJAX add-to-cart.
 *
 * @param array $fragments Cart fragments keyed by selector.
 * @return array
 */
function stolze_cart_count_fragment( $fragments ) {
	$fragments['span.cart-contents-count'] = stolze_cart_count_badge();
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'stolze_cart_count_fragment' );

/**
 * Festival favicon set.
 */
function stolze_favicons() {
	$base = STOLZE_URI . '/assets/favicon';
	echo '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url( $base . '/apple-touch-icon.png' ) . '">' . "\n";
	echo '<link rel="icon" type="image/png" sizes="32x32" href="' . esc_url( $base . '/favicon-32x32.png' ) . '">' . "\n";
	echo '<link rel="icon" type="image/png" sizes="16x16" href="' . esc_url( $base . '/favicon-16x16.png' ) . '">' . "\n";
}
add_action( 'wp_head', 'stolze_favicons', 2 );

/**
 * Resolve the year represented by the current request for social metadata.
 *
 * @return WP_Post|null
 */
function stolze_current_year_for_meta() {
	$year_qv = (string) get_query_var( 'stolze_year' );
	if ( '' !== $year_qv ) {
		return stolze_year_by_title( $year_qv );
	}

	if ( is_singular( 'jahr' ) ) {
		$year = get_queried_object();
		return $year instanceof WP_Post ? $year : null;
	}

	if ( is_front_page() ) {
		return stolze_latest_year();
	}

	return null;
}

// Document titles are managed by RankMath — configure title formats there.
// The jahr CPT is registered with "Stolze Openair %title%" in RankMath's
// Titles & Meta → Post Types → Jahr settings.

// ---------------------------------------------------------------------------
// SEO — RankMath owns meta tags, OG, Twitter, sitemap, titles and breadcrumb
// schema. The theme only contributes what RankMath cannot generate on its own:
//   • Correct canonical for /year/{YYYY}/ (CPT slugs are y20XX, not the URL)
//   • Event schema with festival dates, venue and free-admission flag
// ---------------------------------------------------------------------------

/**
 * Override RankMath's canonical for year/front-page views so it points to
 * the /year/{YYYY}/ rewrite URL rather than the raw CPT slug.
 *
 * @param string $canonical Canonical URL computed by RankMath.
 * @return string
 */
function stolze_rankmath_canonical( $canonical ) {
	$year = stolze_current_year_for_meta();
	if ( ! $year ) {
		return $canonical;
	}
	if ( is_front_page() && '' === (string) get_query_var( 'stolze_year' ) ) {
		return home_url( '/' );
	}
	return home_url( '/year/' . rawurlencode( get_the_title( $year ) ) . '/' );
}
add_filter( 'rank_math/frontend/canonical', 'stolze_rankmath_canonical' );

/**
 * Fix sitemap URLs for the `jahr` CPT: use /year/{YYYY}/ instead of the raw
 * CPT slug (y2024, 2026-2, …) that get_permalink() would return.
 *
 * @param array  $entry     Sitemap entry array with a 'loc' key.
 * @param string $post_type Post type being sitemapped.
 * @param object $post      Post object.
 * @return array
 */
function stolze_rankmath_sitemap_entry( $entry, $post_type, $post ) {
	if ( 'jahr' !== $post_type ) {
		return $entry;
	}
	$entry['loc'] = home_url( '/year/' . rawurlencode( $post->post_title ) . '/' );
	return $entry;
}
add_filter( 'rank_math/sitemap/entry', 'stolze_rankmath_sitemap_entry', 10, 3 );

/**
 * Supply the festival year poster as the RankMath OG image for year pages and
 * the homepage, so social shares use the poster instead of a generic image.
 *
 * @param string $image_url OG image URL computed by RankMath.
 * @return string
 */
function stolze_rankmath_og_image( $image_url ) {
	$year = stolze_current_year_for_meta();
	if ( ! $year ) {
		return $image_url;
	}
	$poster = stolze_image_url( get_field( 'poster_desktop', $year->ID ), 'full' )
		?: stolze_image_url( get_field( 'poster', $year->ID ), 'full' );
	return $poster ?: $image_url;
}
add_filter( 'rank_math/opengraph/facebook/image', 'stolze_rankmath_og_image' );
add_filter( 'rank_math/opengraph/twitter/image', 'stolze_rankmath_og_image' );

/**
 * Output Event structured data for year pages. RankMath handles Organization,
 * BreadcrumbList, WooCommerce Product and WebSite schemas itself.
 */
function stolze_event_schema() {
	if ( is_404() || is_search() ) {
		return;
	}

	$year = stolze_current_year_for_meta();
	if ( ! $year ) {
		return;
	}

	$fields = get_fields( $year->ID );
	$dates  = array();
	foreach ( (array) ( $fields['daten'] ?? array() ) as $row ) {
		$date = stolze_parse_utc( is_array( $row ) ? ( $row['datum'] ?? '' ) : '' );
		if ( $date ) {
			$dates[] = $date;
		}
	}
	usort( $dates, static function ( $a, $b ) { return $a <=> $b; } );

	if ( ! $dates ) {
		return;
	}

	if ( is_front_page() && '' === (string) get_query_var( 'stolze_year' ) ) {
		$page_url = home_url( '/' );
	} else {
		$page_url = home_url( '/year/' . rawurlencode( get_the_title( $year ) ) . '/' );
	}

	$images = array_values( array_filter( array(
		stolze_image_url( $fields['poster_desktop'] ?? null, 'full' ),
		stolze_image_url( $fields['poster'] ?? null, 'full' ),
	) ) );

	$event = array(
		'@context'            => 'https://schema.org',
		'@type'               => 'Event',
		'@id'                 => $page_url . '#event',
		'name'                => 'Stolze Openair ' . get_the_title( $year ),
		'startDate'           => reset( $dates )->format( 'Y-m-d' ),
		'endDate'             => end( $dates )->format( 'Y-m-d' ),
		'eventStatus'         => 'https://schema.org/EventScheduled',
		'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
		'isAccessibleForFree' => true,
		'url'                 => $page_url,
		'organizer'           => array(
			'@type' => 'Organization',
			'name'  => 'Stolze Openair',
			'url'   => home_url( '/' ),
		),
		'location'            => array(
			'@type'   => 'Place',
			'name'    => 'Stolzewiese',
			'address' => array(
				'@type'           => 'PostalAddress',
				'addressLocality' => 'Zürich',
				'addressCountry'  => 'CH',
			),
		),
	);
	if ( $images ) {
		$event['image'] = $images;
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'stolze_event_schema', 6 );

/**
 * Inline [x-cloak] before wp_head so Alpine doesn't flash uninitialised UI.
 */
function stolze_xcloak() {
	echo "<style>[x-cloak]{display:none !important;}</style>\n";
}
add_action( 'wp_head', 'stolze_xcloak', 1 );
