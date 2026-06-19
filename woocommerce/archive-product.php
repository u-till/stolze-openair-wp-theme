<?php
/**
 * Shop archive — festival-styled product grid.
 *
 * Overrides WooCommerce's archive-product.php with custom markup (the bordered
 * grid used elsewhere on the site). No DB/content changes.
 *
 * @package stolze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

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
<main class="shop">
	<?php stolze_section_title( woocommerce_page_title( false ) ? woocommerce_page_title( false ) : 'Shop', $shop_logo ); ?>

	<?php if ( ! empty( $shop_products ) ) : ?>
		<div class="shop-grid">
			<div class="grid">
				<div class="grid__inner">
					<?php foreach ( array_chunk( $shop_products, 4 ) as $row ) : ?>
						<div class="grid-row">
							<?php
							foreach ( $row as $product ) :
								if ( ! $product ) {
									continue;
								}
								$img = $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'large' ) : wc_placeholder_img_src( 'large' );
								?>
								<a class="grid-item" href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>">
									<div class="grid-item__inner">
										<div class="product-card">
											<img class="product-card__img" src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" />
											<div class="product-card__meta">
												<span class="product-card__name"><?php echo esc_html( $product->get_name() ); ?></span>
												<span class="product-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
											</div>
										</div>
									</div>
								</a>
							<?php endforeach; ?>
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
	<?php else : ?>
		<p class="shop-empty">Zurzeit sind keine Produkte verfügbar.</p>
	<?php endif; ?>
</main>
<?php
get_footer();
