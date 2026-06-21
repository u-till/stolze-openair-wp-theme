<?php
/**
 * Single artist — /artist/{slug}: a full-page overlay modal.
 *
 * @package stolze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$artist_id = get_the_ID();
	$buhne     = get_post_meta( $artist_id, 'buhne', true );
	$slot      = get_post_meta( $artist_id, 'slot', true );
	$bg_url    = get_the_post_thumbnail_url( $artist_id, 'full' );
	$links     = get_field( 'social_media_links', $artist_id );
	$links     = is_array( $links ) ? $links : array();

	$latest_year = stolze_artist_latest_year_title( $artist_id );
	$back_link   = $latest_year ? home_url( '/year/' . rawurlencode( $latest_year ) ) . '#lineup' : home_url( '/' );

	$modal_style = $bg_url ? '--modal-background: url(' . esc_url( $bg_url ) . ')' : '';
	?>
	<div class="artist-overlay" style="<?php echo esc_attr( $modal_style ); ?>">
		<div class="overlay__inner">
			<div class="overlay__content">
				<a href="<?php echo esc_url( $back_link ); ?>" class="back-link">Zurück zum Lineup</a>
				<div class="artist-modal">
					<div class="artist-modal__header"><?php the_title(); ?></div>
					<div class="artist-modal__info">
						<div class="artist-modal__info-item"><?php echo esc_html( $buhne ); ?></div>
						<div class="artist-modal__info-item"><?php echo esc_html( stolze_day_time_year_if_past( $slot ) ); ?></div>
					</div>
					<div class="artist-modal__description"><?php the_content(); ?></div>
					<?php if ( ! empty( $links ) ) : ?>
						<div class="artist-modal__links">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
							<?php
							foreach ( $links as $link ) :
								if ( empty( $link['url'] ) ) {
									continue;
								}
								?>
								<a class="artist-modal__link" href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $link['link_label'] ); ?></a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
	<?php
endwhile;

get_footer();
