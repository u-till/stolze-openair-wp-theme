<?php
/**
 * Plain CMS page — title + block content (mirrors src/app/[...slug]/page.tsx).
 * Shortcodes (e.g. [eventadmin] on Helferanmeldung) render natively here.
 *
 * @package stolze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// WooCommerce cart / checkout / my-account also render through this template
// (they are plain pages holding the WC blocks / shortcode); they get an extra
// .woo-page class for the WC-specific styling.
$stolze_is_woo_page = function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() );

// Every plain page wears the festival section-title band with the most recent
// year's logo (matching the shop), so non-year pages read as part of the site.
$stolze_page_year = stolze_latest_year();
$stolze_page_logo = $stolze_page_year ? stolze_image_url( get_field( 'logo', $stolze_page_year->ID ), 'large' ) : '';

while ( have_posts() ) :
	the_post();
	?>
	<main class="page-main<?php echo $stolze_is_woo_page ? ' woo-page' : ''; ?>">
		<?php stolze_section_title( get_the_title(), $stolze_page_logo ); ?>
		<div class="entry-content"><?php the_content(); ?></div>
	</main>
	<?php
endwhile;

get_footer();
