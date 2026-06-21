<?php
/**
 * Shop archive — festival-styled product grid.
 *
 * Overrides WooCommerce's archive-product.php with custom markup (the bordered
 * grid used elsewhere on the site). No DB/content changes.
 *
 * @version 8.6.0
 *
 * @package stolze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

do_action( 'woocommerce_before_main_content' );

// Use the most recent year's logo in the section title, for visual continuity.
$shop_year = stolze_latest_year();
$shop_logo = $shop_year ? stolze_image_url( get_field( 'logo', $shop_year->ID ), 'large' ) : '';

// Collect the queried products so we can chunk them into bordered rows.
$shop_products = array();
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		$shop_products[] = wc_get_product( get_the_ID() );
	}
}
?>
<div class="shop">
	<?php stolze_section_title( woocommerce_page_title( false ) ? woocommerce_page_title( false ) : 'Shop', $shop_logo ); ?>

	<?php if ( ! empty( $shop_products ) ) : ?>
		<div class="shop-grid">
			<div class="grid">
					<div class="grid__inner">
						<?php foreach ( array_chunk( $shop_products, 4 ) as $row ) : ?>
							<div class="grid-row">
								<?php
								foreach ( $row as $product ) {
									stolze_product_card( $product );
								}
								?>
							</div>
						<?php endforeach; ?>
					</div>
			</div>
		</div>

		<div class="shop-pagination">
			<?php
			echo paginate_links(
				array(
					'total'   => wc_get_loop_prop( 'total_pages' ),
					'current' => max( 1, get_query_var( 'paged' ) ),
				)
			);
			?>
		</div>
		<?php do_action( 'woocommerce_after_shop_loop' ); ?>
	<?php else : ?>
		<?php do_action( 'woocommerce_no_products_found' ); ?>
		<div class="empty-state empty-state--large shop-empty">
			<h2>Der Shop ist gerade leer</h2>
			<p>Neue Festivalprodukte erscheinen hier, sobald sie verfügbar sind.</p>
			<a class="empty-state__action" href="<?php echo esc_url( home_url( '/' ) ); ?>">Zum Festival</a>
		</div>
	<?php endif; ?>
</div>
<?php
do_action( 'woocommerce_after_main_content' );

get_footer();
