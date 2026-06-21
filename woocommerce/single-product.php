<?php
/**
 * Single product — festival-styled gallery + variation form.
 *
 * Overrides WooCommerce's single-product.php. Uses core WC functions for the
 * price + add-to-cart form (so variations and the cart keep working) wrapped in
 * our own layout and styling.
 *
 * @version 8.6.0
 *
 * @package stolze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$GLOBALS['product'] = wc_get_product( get_the_ID() );
	$product            = $GLOBALS['product'];
	if ( ! $product ) {
		continue;
	}
	if ( function_exists( 'WC' ) && WC()->structured_data ) {
		WC()->structured_data->generate_product_data( $product );
	}

	// Build the gallery: main image first, then the gallery images.
	$image_ids = array();
	if ( $product->get_image_id() ) {
		$image_ids[] = $product->get_image_id();
	}
	$image_ids = array_merge( $image_ids, $product->get_gallery_image_ids() );

	$gallery = array();
	foreach ( $image_ids as $img_id ) {
		$full  = wp_get_attachment_image_url( $img_id, 'large' );
		$thumb = wp_get_attachment_image_url( $img_id, 'thumbnail' );
		if ( $full ) {
			$gallery[] = array(
				'full'  => $full,
				'thumb' => $thumb ? $thumb : $full,
				'alt'   => stolze_image_alt( $img_id, $product->get_name() ),
			);
		}
	}
	$first = ! empty( $gallery ) ? $gallery[0]['full'] : wc_placeholder_img_src( 'large' );
	$first_alt = ! empty( $gallery ) ? $gallery[0]['alt'] : $product->get_name();
	?>
	<div class="single-product">
		<div class="back-to-shop">
			<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">&larr; Zurück zum Shop</a>
		</div>

		<div class="product-notices">
			<?php do_action( 'woocommerce_before_single_product' ); ?>
		</div>

		<div class="product-top">
			<div class="product-gallery" x-data="{ current: <?php echo esc_attr( wp_json_encode( $first ) ); ?>, currentAlt: <?php echo esc_attr( wp_json_encode( $first_alt ) ); ?> }">
				<div class="product-gallery__main">
					<img :src="current" :alt="currentAlt" src="<?php echo esc_url( $first ); ?>" alt="<?php echo esc_attr( $first_alt ); ?>" />
				</div>
				<?php if ( count( $gallery ) > 1 ) : ?>
					<div class="product-gallery__thumbs">
						<?php foreach ( $gallery as $g ) : ?>
							<button class="product-gallery__thumb"
								type="button"
								@click="current = <?php echo esc_attr( wp_json_encode( $g['full'] ) ); ?>; currentAlt = <?php echo esc_attr( wp_json_encode( $g['alt'] ) ); ?>"
								:class="current === <?php echo esc_attr( wp_json_encode( $g['full'] ) ); ?> ? 'is-active' : ''"
								aria-label="<?php echo esc_attr( $product->get_name() . ' Bild anzeigen' ); ?>">
								<img src="<?php echo esc_url( $g['thumb'] ); ?>" alt="" />
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="product-summary">
				<h1 class="product-summary__title"><?php the_title(); ?></h1>
				<div class="product-summary__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>

				<?php if ( $product->get_short_description() ) : ?>
					<div class="product-summary__short"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div>
				<?php endif; ?>

				<div class="product-summary__cart">
					<?php woocommerce_template_single_add_to_cart(); ?>
				</div>

				<?php if ( wc_get_page_id( 'cart' ) > 0 ) : ?>
					<a class="product-summary__cart-link" href="<?php echo esc_url( wc_get_cart_url() ); ?>">Zum Warenkorb &rarr;</a>
				<?php endif; ?>

				<?php if ( $product->get_description() ) : ?>
					<section class="product-description">
						<h2 class="product-description__title">Details</h2>
						<div class="content-section entry-content"><?php echo apply_filters( 'the_content', $product->get_description() ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					</section>
				<?php endif; ?>
			</div>
		</div>

		<?php
		$stolze_sp_year = stolze_latest_year();
		$stolze_sp_logo = $stolze_sp_year ? stolze_image_url( get_field( 'logo', $stolze_sp_year->ID ), 'large' ) : '';
		$stolze_related = function_exists( 'wc_get_products' ) ? wc_get_products(
			array(
				'limit'   => 4,
				'exclude' => array( $product->get_id() ),
				'orderby' => 'date',
				'order'   => 'DESC',
				'status'  => 'publish',
			)
		) : array();
		if ( ! empty( $stolze_related ) ) :
			?>
			<section class="product-related">
				<?php stolze_section_title( 'Weitere Produkte', $stolze_sp_logo ); ?>
				<div class="shop-grid">
					<div class="grid">
						<div class="grid__inner">
								<div class="grid-row">
									<?php
									foreach ( $stolze_related as $stolze_rp ) {
										stolze_product_card( $stolze_rp );
									}
									?>
								</div>
						</div>
					</div>
				</div>
			</section>
			<?php
		endif;
		?>
	</div>
	<?php
endwhile;

do_action( 'woocommerce_after_single_product' );
do_action( 'woocommerce_after_main_content' );

get_footer();
