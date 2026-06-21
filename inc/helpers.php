<?php
/**
 * Presentation helpers (German date formatting + per-year theming).
 *
 * All dates are rendered in UTC so the wall-clock value stored in the slot
 * field is shown verbatim.
 *
 * @package stolze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const STOLZE_WEEKDAYS = array( 'Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag' );
const STOLZE_MONTHS   = array( '', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember' );

/**
 * Parse a stored datetime string as UTC.
 *
 * @param string $value Datetime string.
 * @return DateTimeImmutable|null
 */
function stolze_parse_utc( $value ) {
	if ( empty( $value ) ) {
		return null;
	}
	try {
		return new DateTimeImmutable( $value, new DateTimeZone( 'UTC' ) );
	} catch ( Exception $e ) {
		return null;
	}
}

/**
 * "Samstag, 20:00" — weekday + time.
 *
 * @param string $value Datetime string.
 * @return string
 */
function stolze_day_and_time( $value ) {
	$d = stolze_parse_utc( $value );
	if ( ! $d ) {
		return '';
	}
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', trim( (string) $value ) ) ) {
		return STOLZE_WEEKDAYS[ (int) $d->format( 'w' ) ];
	}
	return STOLZE_WEEKDAYS[ (int) $d->format( 'w' ) ] . ', ' . $d->format( 'H:i' );
}

/**
 * "13.06.2026" — numeric date only.
 *
 * @param string $value Datetime string.
 * @return string
 */
function stolze_date_only( $value ) {
	$d = stolze_parse_utc( $value );
	if ( ! $d ) {
		return '';
	}
	return $d->format( 'd.m.Y' );
}

/**
 * Archive mode: full German date if in the past, else weekday + time.
 *
 * @param string $value Datetime string.
 * @return string
 */
function stolze_day_time_year_if_past( $value ) {
	$d = stolze_parse_utc( $value );
	if ( ! $d ) {
		return '';
	}
	$weekday = STOLZE_WEEKDAYS[ (int) $d->format( 'w' ) ];
	$is_past = $d < new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
	if ( $is_past ) {
		$full = (int) $d->format( 'j' ) . '. ' . STOLZE_MONTHS[ (int) $d->format( 'n' ) ] . ' ' . $d->format( 'Y' );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', trim( (string) $value ) ) ) {
			return $weekday . ', ' . $full;
		}
		return $weekday . ', ' . $full . ' ' . $d->format( 'H:i' );
	}
	return $weekday . ', ' . $d->format( 'H:i' );
}

/**
 * "13.06 & 14.06" — the festival dates from the year `daten` repeater.
 *
 * @param array $daten Array of rows each with a `datum` key.
 * @return string
 */
function stolze_format_dates( $daten ) {
	if ( empty( $daten ) || ! is_array( $daten ) ) {
		return '';
	}
	$parts = array();
	foreach ( $daten as $row ) {
		$datum = is_array( $row ) ? ( $row['datum'] ?? '' ) : '';
		$d     = stolze_parse_utc( $datum );
		if ( $d ) {
			$parts[] = $d->format( 'd.m' );
		}
	}
	return implode( ' & ', $parts );
}

/**
 * Resolve a usable image URL from an ACF image return (array | id | url).
 *
 * @param mixed  $img  ACF image value.
 * @param string $size Preferred size.
 * @return string
 */
function stolze_image_url( $img, $size = 'large' ) {
	if ( empty( $img ) ) {
		return '';
	}
	if ( is_array( $img ) ) {
		if ( ! empty( $img['sizes'][ $size ] ) ) {
			return $img['sizes'][ $size ];
		}
		return $img['url'] ?? '';
	}
	if ( is_numeric( $img ) ) {
		$url = wp_get_attachment_image_url( (int) $img, $size );
		return $url ? $url : '';
	}
	return (string) $img;
}

/**
 * Resolve meaningful image alternative text from ACF or attachment metadata.
 *
 * @param mixed  $image    ACF image value or attachment ID.
 * @param string $fallback Fallback text when the media item has no alt text.
 * @return string
 */
function stolze_image_alt( $image, $fallback = '' ) {
	if ( is_array( $image ) ) {
		if ( ! empty( $image['alt'] ) ) {
			return trim( wp_strip_all_tags( $image['alt'] ) );
		}
		$image = $image['ID'] ?? $image['id'] ?? 0;
	}

	if ( is_numeric( $image ) ) {
		$alt = get_post_meta( (int) $image, '_wp_attachment_image_alt', true );
		if ( $alt ) {
			return trim( wp_strip_all_tags( $alt ) );
		}
	}

	return trim( wp_strip_all_tags( $fallback ) );
}

/**
 * Sanitize a color value before using it in inline CSS.
 *
 * ACF currently stores a mix of hex, rgb() and rgba() color strings.
 *
 * @param mixed $value Raw color value.
 * @return string Sanitized CSS color, or empty string.
 */
function stolze_sanitize_color( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}

	$value = trim( $value );
	$hex   = sanitize_hex_color( $value );
	if ( $hex ) {
		return $hex;
	}

	if ( preg_match( '/^rgba?\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})(?:\s*,\s*(0|1|0?\.\d+))?\s*\)$/', $value, $matches ) ) {
		$r = min( 255, max( 0, (int) $matches[1] ) );
		$g = min( 255, max( 0, (int) $matches[2] ) );
		$b = min( 255, max( 0, (int) $matches[3] ) );
		if ( str_starts_with( strtolower( $value ), 'rgba' ) ) {
			$a = isset( $matches[4] ) ? min( 1, max( 0, (float) $matches[4] ) ) : 1;
			return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $a . ')';
		}
		return 'rgb(' . $r . ',' . $g . ',' . $b . ')';
	}

	return '';
}

/**
 * Render a reusable sponsor/food logo grid.
 *
 * @param WP_Post[] $posts         Posts to render.
 * @param string    $wrapper_class Grid wrapper class.
 */
function stolze_partner_grid( $posts, $wrapper_class ) {
	if ( empty( $posts ) ) {
		return;
	}
	?>
	<div class="<?php echo esc_attr( $wrapper_class ); ?>">
		<div class="grid">
			<div class="grid__inner">
				<?php foreach ( array_chunk( $posts, 4 ) as $row ) : ?>
					<div class="grid-row">
						<?php
						foreach ( $row as $post ) :
							$url   = get_field( 'website_url', $post->ID );
							$thumb_id = get_post_thumbnail_id( $post->ID );
							$thumb    = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'large' ) : '';
							$alt      = stolze_image_alt( $thumb_id, $post->post_title . ' Logo' );
							?>
							<?php if ( $url ) : ?>
								<a class="grid-item" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php else : ?>
								<div class="grid-item">
							<?php endif; ?>
								<div class="grid-item__inner">
									<?php if ( $thumb ) : ?>
										<div class="grid-item__content">
											<img class="partner-img" src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $alt ); ?>" />
											<h3><?php echo esc_html( $post->post_title ); ?></h3>
										</div>
									<?php else : ?>
										<p><?php echo esc_html( $post->post_title ); ?></p>
									<?php endif; ?>
								</div>
							<?php if ( $url ) : ?>
								</a>
							<?php else : ?>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Render one festival product card for shop grids.
 *
 * @param WC_Product $product Product object.
 */
function stolze_product_card( $product ) {
	if ( ! $product ) {
		return;
	}

	$img = $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'large' ) : wc_placeholder_img_src( 'large' );
	$alt = stolze_image_alt( $product->get_image_id(), $product->get_name() );
	?>
	<a class="grid-item" href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>">
		<div class="grid-item__inner">
			<div class="product-card">
				<img class="product-card__img" src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $alt ); ?>" />
				<div class="product-card__meta">
					<span class="product-card__name"><?php echo esc_html( $product->get_name() ); ?></span>
					<span class="product-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
				</div>
			</div>
		</div>
	</a>
	<?php
}

/**
 * Render a section title band (logo / wordmark + uppercase heading).
 *
 * The heading id is the lower-cased title so the menu-top anchors (#lineup,
 * #side-events, #sponsoren, #food, #fotos) resolve, matching the frontend.
 *
 * @param string $title    Heading text.
 * @param string $logo_url Year logo URL (falls back to a text wordmark).
 * @param bool   $inverse  Whether to use the inverse (white-on-gradient) style.
 */
function stolze_section_title( $title, $logo_url = '', $inverse = false ) {
	$class = 'section-title' . ( $inverse ? ' section-title--inverse' : '' );
	$id    = strtolower( $title );
	?>
	<div class="<?php echo esc_attr( $class ); ?>">
		<?php
		for ( $i = 0; $i < 2; $i++ ) :
			if ( $logo_url ) :
				?>
				<img class="section-title__image" src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo 0 === $i ? 'Stolze Openair Logo' : ''; ?>"<?php echo 0 === $i ? '' : ' aria-hidden="true"'; ?> />
			<?php else : ?>
				<p class="section-logo-placeholder">Stolze <br /> Openair</p>
				<?php
			endif;
			if ( 0 === $i ) :
				?>
				<h2 class="section-title__title" id="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $title ); ?></h2>
				<?php
			endif;
		endfor;
		?>
	</div>
	<?php
}

/**
 * Build the inline style for the <main> wrapper of a year.
 *
 * Apply the year background and primary color fields as the public theme color
 * surface: background-color inline, plus --color--primary and
 * --primary-hover-color CSS vars.
 *
 * @param array $f get_fields() result for the jahr post.
 * @return string Inline style attribute value (without the attribute name).
 */
function stolze_year_theme_vars( $f ) {
	$out = array();
	$bg  = stolze_sanitize_color( $f['background_color'] ?? '' );
	$pri = stolze_sanitize_color( $f['primary_color'] ?? '' );
	$txt = stolze_sanitize_color( $f['text_color'] ?? '' );
	$inv = stolze_sanitize_color( $f['secondary_text_color'] ?? '' );
	if ( $bg ) {
		$out[] = 'background-color:' . $bg;
		$out[] = '--color--background--main:' . $bg;
	}
	if ( $pri ) {
		$out[] = '--color--primary:' . $pri;
		$out[] = '--primary-hover-color:' . $pri;
	}
	if ( $txt ) {
		$out[] = '--color--text:' . $txt;
	}
	if ( $inv ) {
		$out[] = '--color--text--inverse:' . $inv;
	}
	return implode( ';', $out );
}

/**
 * Global theme vars for pages that are NOT tied to a year (shop, cart, Infos…).
 *
 * Unlike stolze_year_theme_vars() — which sets `background-color` on the year's
 * <main> — this overrides the CSS *variables* on the <body> so every wrapper
 * that reads `var(--color--background--main)` / `var(--color--primary)` (page
 * bodies, the shop, section titles, links) inherits the most recent year's
 * palette automatically. Applied in header.php on non-year views.
 *
 * @return string Inline style attribute value (without the attribute name).
 */
function stolze_global_theme_vars() {
	$year = stolze_latest_year();
	if ( ! $year ) {
		return '';
	}
	$out = array();
	$bg  = stolze_sanitize_color( get_field( 'background_color', $year->ID ) );
	$pri = stolze_sanitize_color( get_field( 'primary_color', $year->ID ) );
	$txt = stolze_sanitize_color( get_field( 'text_color', $year->ID ) );
	$inv = stolze_sanitize_color( get_field( 'secondary_text_color', $year->ID ) );
	if ( $bg ) {
		$out[] = '--color--background--main:' . $bg;
	}
	if ( $pri ) {
		$out[] = '--color--primary:' . $pri;
		$out[] = '--primary-hover-color:' . $pri;
	}
	if ( $txt ) {
		$out[] = '--color--text:' . $txt;
	}
	if ( $inv ) {
		$out[] = '--color--text--inverse:' . $inv;
	}
	return implode( ';', $out );
}
