<?php
/**
 * Import the generated Openairguide lineup dataset into WordPress.
 *
 * Usage:
 * wp eval-file scripts/import-openairguide-lineups.php [dry-run|apply] [error|earliest|latest|separate] [with-images]
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	fwrite( STDERR, "Run this file through WP-CLI eval-file.\n" );
	exit( 1 );
}

$mode               = $args[0] ?? 'dry-run';
$duplicate_strategy = $args[1] ?? 'error';
$import_images      = in_array( 'with-images', $args, true );
$dataset_file       = dirname( __DIR__ ) . '/data/openairguide-lineups.json';

// Openairguide serves this generic placeholder when a band has no real photo.
const STOLZE_OPENAIRGUIDE_FALLBACK_IMAGE = 'https://www.openairguide.net/img/openairguide-fallback.jpg';

if ( ! in_array( $mode, array( 'dry-run', 'apply' ), true ) ) {
	WP_CLI::error( 'Mode must be dry-run or apply.' );
}
if ( ! in_array( $duplicate_strategy, array( 'error', 'earliest', 'latest', 'separate' ), true ) ) {
	WP_CLI::error( 'Duplicate strategy must be error, earliest, latest or separate.' );
}
if ( ! is_readable( $dataset_file ) ) {
	WP_CLI::error( 'Dataset not found: ' . $dataset_file );
}

$dataset = json_decode( file_get_contents( $dataset_file ), true );
if ( ! is_array( $dataset ) ) {
	WP_CLI::error( 'Dataset is not valid JSON.' );
}

$icon_for_label = static function ( $label ) {
	$icons = array(
		'Instagram'  => 'dashicons-instagram',
		'Facebook'   => 'dashicons-facebook',
		'YouTube'    => 'dashicons-video-alt3',
		'Spotify'    => 'dashicons-format-audio',
		'Bandcamp'   => 'dashicons-format-audio',
		'SoundCloud' => 'dashicons-format-audio',
		'Website'    => 'dashicons-admin-links',
	);
	return $icons[ $label ] ?? 'dashicons-admin-links';
};

$normalize_name = static function ( $name ) {
	return strtolower( remove_accents( trim( preg_replace( '/\s+/u', ' ', $name ) ) ) );
};

$created = 0;
$updated = 0;
$skipped = 0;

foreach ( $dataset as $year_title => $performances ) {
	if ( (int) $year_title >= 2017 ) {
		continue;
	}

	$year = stolze_year_by_title( $year_title );
	if ( ! $year ) {
		WP_CLI::warning( 'Year post not found: ' . $year_title );
		continue;
	}

	$names_in_dataset = array();
	foreach ( $performances as $performance ) {
		$name_key = $normalize_name( $performance['name'] );
		$names_in_dataset[ $name_key ][] = $performance;
	}
	$duplicates = array_filter(
		$names_in_dataset,
		static function ( $rows ) {
			return count( $rows ) > 1;
		}
	);
	if ( $duplicates && 'error' === $duplicate_strategy ) {
		WP_CLI::error( 'Repeated performances in ' . $year_title . ': ' . implode( ', ', array_keys( $duplicates ) ) . '. Choose earliest or separate.' );
	}
	if ( in_array( $duplicate_strategy, array( 'earliest', 'latest' ), true ) ) {
		$performances = array_map(
			static function ( $rows ) use ( $duplicate_strategy ) {
				usort(
					$rows,
					static function ( $a, $b ) {
						return strcmp( ( $a['date'] ?? '' ) . ' ' . ( $a['time'] ?? '' ), ( $b['date'] ?? '' ) . ' ' . ( $b['time'] ?? '' ) );
					}
				);
				return 'latest' === $duplicate_strategy ? end( $rows ) : reset( $rows );
			},
			$names_in_dataset
		);
	}

	$existing_by_name = array();
	foreach ( stolze_artists_for_year( $year->ID ) as $existing_artist ) {
		$existing_by_name[ $normalize_name( $existing_artist->post_title ) ][] = $existing_artist->ID;
	}

	foreach ( $performances as $performance ) {
		$name            = sanitize_text_field( $performance['name'] ?? '' );
		$date            = sanitize_text_field( $performance['date'] ?? '' );
		$time            = sanitize_text_field( $performance['time'] ?? '' );
		$stage           = sanitize_text_field( $performance['stage'] ?? '' );
		// Keep only proper stage names ("… Bühne"); drop notes like "Stolzewiese (Eintritt frei)".
		if ( $stage && ! preg_match( '/bühne$/iu', $stage ) ) {
			$stage = '';
		}
		$source_id       = sanitize_text_field( $performance['source_id'] ?? '' );
		$source_url      = esc_url_raw( $performance['source_url'] ?? '' );
		$origin          = sanitize_text_field( $performance['origin'] ?? '' );
		$genre           = sanitize_text_field( $performance['genre'] ?? '' );
		$performance_key = hash( 'sha256', implode( '|', array( $year_title, $source_id, $date, $time, $stage ) ) );
		if ( ! $name ) {
			++$skipped;
			continue;
		}

		$existing = get_posts(
			array(
				'post_type'      => 'artist',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_stolze_openairguide_performance_key',
				'meta_value'     => $performance_key,
			)
		);
		$post_id  = $existing ? (int) $existing[0] : 0;
		$name_key = $normalize_name( $name );
		if ( ! $post_id && ! empty( $existing_by_name[ $name_key ] ) && 'separate' !== $duplicate_strategy ) {
			$post_id = (int) reset( $existing_by_name[ $name_key ] );
		}

		$summary = array_filter( array( $genre, $origin ) );
		$content = '';
		if ( $genre ) {
			$content .= '<p><strong>Genre:</strong> ' . esc_html( $genre ) . '</p>';
		}
		if ( $origin ) {
			$content .= '<p><strong>Herkunft:</strong> ' . esc_html( $origin ) . '</p>';
		}

		$action = $post_id ? 'update' : 'create';
		WP_CLI::log( sprintf( '[%s] %s: %s%s', $year_title, $action, $name, $time ? ' @ ' . $time : '' ) );
		if ( 'dry-run' === $mode ) {
			$post_id ? ++$updated : ++$created;
			continue;
		}

		$post_data = array(
			'post_type'    => 'artist',
			'post_status'  => 'publish',
			'post_title'   => $name,
			'post_content' => $content,
			'post_excerpt' => implode( ' · ', $summary ),
		);
		if ( $post_id ) {
			$post_data['ID'] = $post_id;
			$result          = wp_update_post( wp_slash( $post_data ), true );
			++$updated;
		} else {
			$result  = wp_insert_post( wp_slash( $post_data ), true );
			$post_id = is_wp_error( $result ) ? 0 : (int) $result;
			++$created;
		}
		if ( is_wp_error( $result ) || ! $post_id ) {
			WP_CLI::warning( 'Could not import ' . $name . ': ' . ( is_wp_error( $result ) ? $result->get_error_message() : 'unknown error' ) );
			continue;
		}

		$slot = $date ? $date . ( $time ? ' ' . $time . ':00' : '' ) : '';
		update_field( 'jahr', array( $year->ID ), $post_id );
		update_field( 'slot', $slot, $post_id );
		update_field( 'buhne', $stage, $post_id );

		$social_links = array();
		foreach ( (array) ( $performance['social_links'] ?? array() ) as $link ) {
			$label = sanitize_text_field( $link['link_label'] ?? 'Website' );
			$url   = esc_url_raw( $link['url'] ?? '' );
			if ( $url ) {
				$social_links[] = array(
					'link_icon'  => $icon_for_label( $label ),
					'link_label' => $label,
					'url'        => $url,
				);
			}
		}
		update_field( 'social_media_links', $social_links, $post_id );
		update_post_meta( $post_id, '_stolze_openairguide_performance_key', $performance_key );
		update_post_meta( $post_id, '_stolze_openairguide_source_id', $source_id );
		update_post_meta( $post_id, '_stolze_openairguide_source_url', $source_url );
		update_post_meta( $post_id, '_stolze_openairguide_origin', $origin );
		update_post_meta( $post_id, '_stolze_openairguide_genre', $genre );
		$image_url = esc_url_raw( $performance['image_url'] ?? '' );
		if ( STOLZE_OPENAIRGUIDE_FALLBACK_IMAGE === $image_url ) {
			$image_url = '';
		}
		update_post_meta( $post_id, '_stolze_openairguide_image_url', $image_url );
		if ( $import_images && $image_url && ! has_post_thumbnail( $post_id ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$attachment_id = media_sideload_image( $image_url, $post_id, $name, 'id' );
			if ( is_wp_error( $attachment_id ) ) {
				WP_CLI::warning( 'Image import failed for ' . $name . ': ' . $attachment_id->get_error_message() );
			} else {
				set_post_thumbnail( $post_id, $attachment_id );
				update_post_meta( $attachment_id, '_wp_attachment_image_alt', $name );
				update_post_meta( $attachment_id, '_stolze_openairguide_source_url', $image_url );
			}
		}
		$existing_by_name[ $name_key ][] = $post_id;
	}
}

if ( 'apply' === $mode ) {
	stolze_invalidate_data_cache( 0 );
}

WP_CLI::success( sprintf( '%s complete: %d create, %d update, %d skipped.', $mode, $created, $updated, $skipped ) );
