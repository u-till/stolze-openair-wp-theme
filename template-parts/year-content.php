<?php
/**
 * Year content — the home page and every /year/{YYYY} page.
 *
 * Mirrors src/components/year-content/year-content.tsx: a vertical stack of
 * SectionTitle bands interleaved with hero, lineup, video, side-events,
 * sponsors, food, gallery, newsletter and the festival footer.
 *
 * Expects $args['jahr'] = the jahr WP_Post.
 *
 * @package stolze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$jahr = $args['jahr'] ?? null;
if ( ! $jahr ) {
	return;
}

$f         = get_fields( $jahr->ID );
$logo_url  = stolze_image_url( $f['logo'] ?? null, 'large' );
$poster    = stolze_image_url( $f['poster'] ?? null, 'large' );
$poster_d  = stolze_image_url( $f['poster_desktop'] ?? null, 'full' );
$daten     = $f['daten'] ?? array();
$side      = $f['side_events_info'] ?? '';
$gallery   = $f['gallery'] ?? array();
$credits   = $f['photographer_credits'] ?? array();
$va_name   = $f['visual_artist_name'] ?? '';
$va_url    = $f['visual_artist_url'] ?? '';

$artists    = stolze_artists_for_year( $jahr->ID );
$sponsors   = stolze_by_year( 'sponsor', $jahr->ID );
$foodtrucks = stolze_by_year( 'foodtruck', $jahr->ID );

$fallback_logo = STOLZE_URI . '/assets/stolze_2024_logo.png';
?>
<main class="year-main" style="<?php echo esc_attr( stolze_year_theme_vars( $f ) ); ?>">

	<?php if ( ! empty( $daten ) ) : ?>
		<?php stolze_section_title( stolze_format_dates( $daten ), $logo_url ); ?>
	<?php endif; ?>

	<h1 class="visually-hidden">Stolze Openair <?php echo esc_html( $jahr->post_title ); ?></h1>

	<?php /* Hero */ ?>
	<?php if ( $poster || $poster_d ) : ?>
		<div class="hero">
			<picture>
				<?php if ( $poster_d ) : ?>
					<source media="(min-width: 768px)" srcset="<?php echo esc_url( $poster_d ); ?>" />
				<?php endif; ?>
				<img src="<?php echo esc_url( $poster ? $poster : $poster_d ); ?>" alt="Hero Image" />
			</picture>
		</div>
	<?php endif; ?>

	<?php /* Intro content (the jahr post body) */ ?>
	<?php if ( trim( (string) $jahr->post_content ) !== '' ) : ?>
		<div class="content-section"><?php echo apply_filters( 'the_content', $jahr->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
	<?php endif; ?>

	<?php /* Lineup */ ?>
	<?php if ( ! empty( $artists ) ) : ?>
		<?php stolze_section_title( 'Lineup', $logo_url ); ?>
		<div class="time-table">
			<?php
			foreach ( $artists as $artist ) :
				$slot  = get_post_meta( $artist->ID, 'slot', true );
				$buhne = get_post_meta( $artist->ID, 'buhne', true );
				$img   = get_the_post_thumbnail_url( $artist->ID, 'large' );
				$img   = $img ? $img : $fallback_logo;
				?>
				<a href="<?php echo esc_url( get_permalink( $artist->ID ) ); ?>" class="time-table-item" x-data="timetableItem">
					<span class="time-table-item__name"><?php echo esc_html( $artist->post_title ); ?></span>
					<span class="time-table-item__stage"><?php echo esc_html( $buhne ); ?></span>
					<span class="time-table-item__playtime"><?php echo esc_html( stolze_day_and_time( $slot ) ); ?></span>
					<div class="time-table-item__image-container" :style="`left: ${left}px`">
						<img class="time-table-item__image" src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $artist->post_title ); ?>" />
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php /* Video section */ ?>
	<section class="video-section" x-data="videoSection">
		<?php stolze_section_title( 'Festival Vibes', $logo_url, true ); ?>
		<div class="video-section__content">
			<div class="video-section__placeholder" x-show="!showVideo" @click="load">
				<img class="video-section__thumbnail" src="https://img.youtube.com/vi/nwVeRbBd_hQ/maxresdefault.jpg" alt="Video thumbnail" />
				<div class="video-section__play-button">&#9654;</div>
			</div>
			<template x-if="showVideo">
				<iframe class="video-section__video"
					src="https://www.youtube.com/embed/nwVeRbBd_hQ?si=tR_mo5n5mjoWcNaL&autoplay=1"
					allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
					referrerpolicy="strict-origin-when-cross-origin" allowfullscreen
					title="youtube video stolze documentary"></iframe>
			</template>
			<a href="<?php echo esc_url( home_url( '/helferanmeldung/' ) ); ?>">
				<span class="button"><span class="button__inner">Unterstütze uns</span></span>
			</a>
		</div>
	</section>

	<?php /* Side events */ ?>
	<?php if ( ! empty( $side ) ) : ?>
		<?php stolze_section_title( 'Side-Events', $logo_url ); ?>
		<div class="content-section"><?php echo wp_kses_post( $side ); ?></div>
	<?php endif; ?>

	<?php /* Sponsors */ ?>
	<?php if ( ! empty( $sponsors ) ) : ?>
		<?php stolze_section_title( 'Sponsoren', $logo_url ); ?>
		<div class="sponsors-grid">
			<div class="grid">
				<div class="grid__inner">
					<?php foreach ( array_chunk( $sponsors, 4 ) as $row ) : ?>
						<div class="grid-row">
							<?php
							foreach ( $row as $sponsor ) :
								$url   = get_field( 'website_url', $sponsor->ID );
								$thumb = get_the_post_thumbnail_url( $sponsor->ID, 'large' );
								?>
								<a class="grid-item" <?php echo $url ? 'href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer"' : ''; ?>>
									<div class="grid-item__inner">
										<?php if ( $thumb ) : ?>
											<div class="grid_item__inner">
												<img class="sponsor-img" src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $sponsor->post_title ); ?>" />
												<h3><?php echo esc_html( $sponsor->post_title ); ?></h3>
											</div>
										<?php else : ?>
											<p><?php echo esc_html( $sponsor->post_title ); ?></p>
										<?php endif; ?>
									</div>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endforeach; ?>
				</div>
				<a href="https://new.stolze-openair.ch/wp-content/uploads/2026/05/2026_Sponsoringbroschuere_Stolze_Openair.pdf" target="_blank" rel="noopener noreferrer" download>
					<span class="button"><span class="button__inner">Sponsoringbroschüre</span></span>
				</a>
			</div>
		</div>
	<?php endif; ?>

	<?php /* Food */ ?>
	<?php if ( ! empty( $foodtrucks ) ) : ?>
		<?php stolze_section_title( 'Food', $logo_url ); ?>
		<div class="foodtrucks-grid">
			<div class="grid">
				<div class="grid__inner">
					<?php foreach ( array_chunk( $foodtrucks, 4 ) as $row ) : ?>
						<div class="grid-row">
							<?php
							foreach ( $row as $ft ) :
								$url   = get_field( 'website_url', $ft->ID );
								$thumb = get_the_post_thumbnail_url( $ft->ID, 'large' );
								?>
								<a class="grid-item" <?php echo $url ? 'href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer"' : ''; ?>>
									<div class="grid-item__inner">
										<?php if ( $thumb ) : ?>
											<div class="grid_item__inner">
												<img class="sponsor-img" src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $ft->post_title ); ?>" />
												<h3><?php echo esc_html( $ft->post_title ); ?></h3>
											</div>
										<?php else : ?>
											<p><?php echo esc_html( $ft->post_title ); ?></p>
										<?php endif; ?>
									</div>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<?php /* Gallery */ ?>
	<?php
	$gallery_urls = array();
	if ( ! empty( $gallery ) && is_array( $gallery ) ) {
		foreach ( $gallery as $g ) {
			$gallery_urls[] = stolze_image_url( $g, 'large' );
		}
	}
	if ( ! empty( $gallery_urls ) ) :
		?>
		<?php stolze_section_title( 'Fotos', $logo_url ); ?>
		<div class="gallery" x-data="galleryLightbox" data-images='<?php echo esc_attr( wp_json_encode( $gallery_urls ) ); ?>' @keydown.window="onKey">
			<?php
			// Combine image cells + an optional credits cell, then chunk into rows.
			$cells = array();
			foreach ( $gallery_urls as $i => $g_url ) {
				$cells[] = array(
					'type' => 'img',
					'i'    => $i,
					'url'  => $g_url,
				);
			}
			if ( ! empty( $credits ) && is_array( $credits ) ) {
				$cells[] = array( 'type' => 'credits' );
			}
			?>
			<div class="grid">
				<div class="grid__inner">
					<?php foreach ( array_chunk( $cells, 4 ) as $row ) : ?>
						<div class="grid-row">
							<?php foreach ( $row as $cell ) : ?>
								<?php if ( 'img' === $cell['type'] ) : ?>
									<div class="grid-item">
										<div class="grid-item__inner">
											<img class="gallery-img" src="<?php echo esc_url( $cell['url'] ); ?>" alt="Gallery Image" @click="show(<?php echo (int) $cell['i']; ?>)" />
										</div>
									</div>
								<?php else : ?>
									<div class="grid-item-credits">
										<div class="grid-item__inner">
											<div class="photographer-credits">
												<h3>Fotos von:</h3>
												<div class="credits-list">
													<?php foreach ( $credits as $credit ) : ?>
														<div class="credit-item">
															<?php if ( ! empty( $credit['photographer_url'] ) ) : ?>
																<a href="<?php echo esc_url( $credit['photographer_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $credit['photographer_name'] ); ?></a>
															<?php else : ?>
																<span><?php echo esc_html( $credit['photographer_name'] ); ?></span>
															<?php endif; ?>
														</div>
													<?php endforeach; ?>
												</div>
											</div>
										</div>
									</div>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<template x-if="open">
				<div class="gallery-lightbox" @click="close">
					<div class="lightbox-content" @click.stop>
						<button class="lightbox-close" @click="close">&times;</button>
						<div class="lightbox-image-wrapper">
							<div class="lightbox-prev" @click="prev"></div>
							<img class="lightbox-image" :src="current" alt="Lightbox Image" />
							<div class="lightbox-next" @click="next"></div>
						</div>
					</div>
				</div>
			</template>
		</div>
	<?php endif; ?>

	<?php /* Newsletter */ ?>
	<section class="newsletter-section">
		<?php stolze_section_title( 'Newsletter', $logo_url, true ); ?>
		<div class="newsletter-section__content">
			<a href="<?php echo esc_url( home_url( '/newsletter/' ) ); ?>">
				<span class="button"><span class="button__inner">Für Newsletter Anmelden</span></span>
			</a>
		</div>
	</section>

	<?php /* Festival footer */ ?>
	<footer class="footer">
		<div class="footer__inner">
			<div class="footer__section">
				<h3>Kontakt</h3>
				<div>
					<p>Stolze Openair Zürich</p>
					<p>Verein Stolzewiese</p>
					<p>8000 Zürich</p>
					<a href="mailto:mail@stolze-openair.ch">mail@stolze-openair.ch</a>
					<p>IBAN: CH87 0839 0040 9844 1000 7</p>
				</div>
			</div>

			<div class="footer__section">
				<?php if ( $va_name ) : ?>
					<h3>Artwork</h3>
					<div>
						<?php if ( $va_url ) : ?>
							<a href="<?php echo esc_url( $va_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $va_name ); ?></a>
						<?php else : ?>
							<p><?php echo esc_html( $va_name ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<h3>Website</h3>
				<div>
					<a href="https://utill.ch" target="_blank" rel="noopener noreferrer">utill.ch</a>
					<span> + </span>
					<a href="https://thibaultbadoux.ch/" target="_blank" rel="noopener noreferrer">thibaultbadoux.ch</a>
				</div>
			</div>

			<div class="footer__section">
				<h3>Socials</h3>
				<div class="social-links">
					<a href="https://www.instagram.com/stolze_openair/" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
					</a>
					<a href="https://www.facebook.com/wirsindstolze" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
					</a>
				</div>
				<div style="margin-top:1rem;">
					<a href="<?php echo esc_url( get_post_type_archive_link( 'artist' ) ); ?>">Artist Archive</a>
				</div>
			</div>
		</div>
	</footer>
</main>
