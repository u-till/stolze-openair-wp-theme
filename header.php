<?php
/**
 * Site header + fixed navigation.
 *
 * @package stolze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Determine the "current" year (for active states) and whether the section
// anchors (menu-top) should show — only on the home + year pages.
$stolze_years        = stolze_all_years();
$stolze_latest_title = ! empty( $stolze_years ) ? $stolze_years[0]->post_title : '';
// The /year/{YYYY} route rewrites to `index.php?stolze_year=…`, which is still
// the home query — so is_front_page() is also true there. The year query var
// must therefore win when deciding which selector entry is active, otherwise
// every /year page would highlight the latest year.
$stolze_year_qv      = (string) get_query_var( 'stolze_year' );
$stolze_is_year_view = is_front_page() || '' !== $stolze_year_qv;
if ( '' !== $stolze_year_qv ) {
	$stolze_current_year = $stolze_year_qv;
} elseif ( is_singular( 'jahr' ) ) {
	$stolze_current_year = (string) get_the_title();
} elseif ( is_front_page() ) {
	$stolze_current_year = $stolze_latest_title;
} else {
	$stolze_current_year = '';
}

$stolze_menu_bottom = stolze_menu_items( 'menu-bottom' );
$stolze_menu_top    = stolze_menu_items( 'menu-top' );

// Pages that aren't a year view (shop, cart, Infos, artists, 404) inherit the
// most recent year's palette via CSS-variable overrides on <body>. Year pages
// (front page, /year/{YYYY}, single jahr) theme their own <main> instead.
$stolze_is_year_page = $stolze_is_year_view || is_singular( 'jahr' );
$stolze_body_style   = $stolze_is_year_page ? '' : stolze_global_theme_vars();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?><?php echo $stolze_body_style ? ' style="' . esc_attr( $stolze_body_style ) . '"' : ''; ?>>
<?php wp_body_open(); ?>

<div x-data="stolzeNav">
	<button class="burger-icon" x-show="!open" @click="toggle" aria-label="Open menu">&#9776;</button>
	<button class="close-icon" x-cloak x-show="open" @click="toggle" aria-label="Close menu">&#10005;</button>

	<header class="page-header site-header">
		<div class="nav-content" :class="open ? 'show' : ''">

			<nav class="years-navigation">
				<?php
				foreach ( $stolze_years as $stolze_year_post ) :
					$stolze_href   = stolze_year_href( $stolze_year_post, $stolze_latest_title );
					$stolze_active = ( (string) $stolze_year_post->post_title === $stolze_current_year ) ? ' active' : '';
					?>
					<a href="<?php echo esc_url( $stolze_href ); ?>" class="<?php echo esc_attr( trim( $stolze_active ) ); ?>" @click="close">
						<?php echo esc_html( $stolze_year_post->post_title ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<?php if ( $stolze_is_year_view && ! empty( $stolze_menu_top ) ) : ?>
				<nav class="menu-top">
					<?php foreach ( $stolze_menu_top as $stolze_item ) : ?>
						<a href="<?php echo esc_url( $stolze_item['url'] ); ?>" @click="close"><?php echo esc_html( $stolze_item['label'] ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>

			<nav class="menu-bottom">
				<?php
				$stolze_shop_url = function_exists( 'wc_get_page_permalink' ) ? untrailingslashit( (string) wc_get_page_permalink( 'shop' ) ) : '';
				foreach ( $stolze_menu_bottom as $stolze_item ) :
					$stolze_is_shop = $stolze_shop_url && untrailingslashit( $stolze_item['url'] ) === $stolze_shop_url;
					if ( $stolze_is_shop ) :
						?>
						<span class="menu-shop">
							<a href="<?php echo esc_url( $stolze_item['url'] ); ?>" @click="close"><?php echo esc_html( $stolze_item['label'] ); ?></a>
							<a class="cart-contents" href="<?php echo esc_url( wc_get_cart_url() ); ?>" @click="close" aria-label="Warenkorb">
								<svg class="cart-icon" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
								<?php echo stolze_cart_count_badge(); // phpcs:ignore WordPress.Security.EscapeOutput -- markup escaped in helper. ?>
							</a>
						</span>
						<?php
					else :
						?>
						<a href="<?php echo esc_url( $stolze_item['url'] ); ?>" @click="close"><?php echo esc_html( $stolze_item['label'] ); ?></a>
						<?php
					endif;
				endforeach;
				?>
			</nav>

		</div>
	</header>
</div>

