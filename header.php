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
$stolze_year_qv      = get_query_var( 'stolze_year' );
$stolze_is_year_view = is_front_page() || ! empty( $stolze_year_qv );
$stolze_current_year = is_front_page() ? $stolze_latest_title : (string) $stolze_year_qv;

$stolze_menu_bottom = stolze_menu_items( 'menu-bottom' );
$stolze_menu_top    = stolze_menu_items( 'menu-top' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
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
				<?php foreach ( $stolze_menu_bottom as $stolze_item ) : ?>
					<a href="<?php echo esc_url( $stolze_item['url'] ); ?>" @click="close"><?php echo esc_html( $stolze_item['label'] ); ?></a>
				<?php endforeach; ?>
			</nav>

		</div>
	</header>
</div>

