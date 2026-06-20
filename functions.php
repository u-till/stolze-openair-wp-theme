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
 * Festival favicon set (ported from the Next.js layout head).
 */
function stolze_favicons() {
	$base = STOLZE_URI . '/assets/favicon';
	echo '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url( $base . '/apple-touch-icon.png' ) . '">' . "\n";
	echo '<link rel="icon" type="image/png" sizes="32x32" href="' . esc_url( $base . '/favicon-32x32.png' ) . '">' . "\n";
	echo '<link rel="icon" type="image/png" sizes="16x16" href="' . esc_url( $base . '/favicon-16x16.png' ) . '">' . "\n";
}
add_action( 'wp_head', 'stolze_favicons', 2 );

/**
 * Inline [x-cloak] before wp_head so Alpine doesn't flash uninitialised UI.
 */
function stolze_xcloak() {
	echo "<style>[x-cloak]{display:none !important;}</style>\n";
}
add_action( 'wp_head', 'stolze_xcloak', 1 );
