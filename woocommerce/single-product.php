<?php
/**
 * Single product — festival-styled gallery + variation form.
 *
 * Overrides WooCommerce's single-product.php. Uses core WC functions for the
 * price + add-to-cart form (so variations and the cart keep working) wrapped in
 * our own layout and styling.
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
			);
		}
	}
	$first = ! empty( $gallery ) ? $gallery[0]['full'] : wc_placeholder_img_src( 'large' );
	?>
	<main class="single-product">
		<div class="back-to-shop">
			<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">&larr; Zurück zum Shop</a>
		</div>

		<?php woocommerce_output_all_notices(); ?>

		<div class="product-top">
			<div class="product-gallery" x-data="{ current: '<?php echo esc_url( $first ); ?>' }">
				<div class="product-gallery__main">
					<img :src="current" src="<?php echo esc_url( $first ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" />
				</div>
				<?php if ( count( $gallery ) > 1 ) : ?>
					<div class="product-gallery__thumbs">
						<?php foreach ( $gallery as $g ) : ?>
							<img class="product-gallery__thumb"
								src="<?php echo esc_url( $g['thumb'] ); ?>"
								alt=""
								@click="current='<?php echo esc_url( $g['full'] ); ?>'"
								:class="current==='<?php echo esc_url( $g['full'] ); ?>' ? 'is-active' : ''" />
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
			</div>
		</div>

		<?php if ( $product->get_description() ) : ?>
			<section class="product-description">
				<?php stolze_section_title( 'Details' ); ?>
				<div class="content-section entry-content"><?php echo apply_filters( 'the_content', $product->get_description() ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			</section>
		<?php endif; ?>
	</main>
	<?php
endwhile;

get_footer();
