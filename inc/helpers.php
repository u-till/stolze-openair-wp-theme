<?php
/**
 * Presentation helpers (German date formatting + per-year theming).
 *
 * Mirrors src/utils/formatDate.tsx from the Next.js frontend. All dates are
 * rendered in UTC (the frontend used `timeZone: 'UTC'`) so the wall-clock value
 * stored in the slot field is shown verbatim.
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
 * Sanitize a color value before using it in inline CSS.
 *
 * ACF color picker fields should return hex colors. If an editor/plugin returns
 * anything else, drop it instead of allowing arbitrary CSS into a style attr.
 *
 * @param mixed $value Raw color value.
 * @return string Sanitized hex color, or empty string.
 */
function stolze_sanitize_color( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}

	$color = sanitize_hex_color( trim( $value ) );
	return $color ? $color : '';
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
				<img class="section-title__image" src="<?php echo esc_url( $logo_url ); ?>" alt="stolze logo" />
			<?php else : ?>
				<p class="section-logo_placeholder">Stolze <br /> Openair</p>
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
 * The Next.js YearContent only ever applies the year `backgroundColor` (on the
 * wrapper) and `primaryColor` (passed to section titles, gradients and grid
 * hovers). It deliberately leaves text/secondary colors untouched, so we mirror
 * exactly that: background-color inline, plus --color--primary and
 * --primary-hover-color CSS vars that the ported component CSS reads.
 *
 * @param array $f get_fields() result for the jahr post.
 * @return string Inline style attribute value (without the attribute name).
 */
function stolze_year_theme_vars( $f ) {
	$out = array();
	$bg  = stolze_sanitize_color( $f['background_color'] ?? '' );
	$pri = stolze_sanitize_color( $f['primary_color'] ?? '' );
	if ( $bg ) {
		$out[] = 'background-color:' . $bg;
	}
	if ( $pri ) {
		$out[] = '--color--primary:' . $pri;
		$out[] = '--primary-hover-color:' . $pri;
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
	if ( $bg ) {
		$out[] = '--color--background--main:' . $bg;
	}
	if ( $pri ) {
		$out[] = '--color--primary:' . $pri;
		$out[] = '--primary-hover-color:' . $pri;
	}
	return implode( ';', $out );
}
