<?php
/**
 * Import the 2016 Stolze Openair line-up.
 *
 * openairguide.net never recorded the 2016 (or 2014) edition, so this dataset
 * was recovered from the festival's own archived site (see data/stolze-2016-lineup.json).
 *
 * Usage: wp eval-file scripts/import-stolze-2016.php [dry-run|apply]
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	fwrite( STDERR, "Run this file through WP-CLI eval-file.\n" );
	exit( 1 );
}

$mode         = $args[0] ?? 'dry-run';
$dataset_file = dirname( __DIR__ ) . '/data/stolze-2016-lineup.json';

if ( ! in_array( $mode, array( 'dry-run', 'apply' ), true ) ) {
	WP_CLI::error( 'Mode must be dry-run or apply.' );
}
if ( ! is_readable( $dataset_file ) ) {
	WP_CLI::error( 'Dataset not found: ' . $dataset_file );
}

$dataset = json_decode( file_get_contents( $dataset_file ), true );
if ( ! is_array( $dataset ) || empty( $dataset['2016'] ) ) {
	WP_CLI::error( 'Dataset is missing the 2016 line-up.' );
}

$source_page = $dataset['_source'] ?? '';

$normalize_name = static function ( $name ) {
	return strtolower( remove_accents( trim( preg_replace( '/\s+/u', ' ', $name ) ) ) );
};

$year = stolze_year_by_title( 2016 );
if ( ! $year ) {
	WP_CLI::error( 'Year post not found: 2016' );
}

$existing_by_name = array();
foreach ( stolze_artists_for_year( $year->ID ) as $existing_artist ) {
	$existing_by_name[ $normalize_name( $existing_artist->post_title ) ] = $existing_artist->ID;
}

$created = 0;
$updated = 0;

foreach ( $dataset['2016'] as $performance ) {
	$name    = sanitize_text_field( $performance['name'] ?? '' );
	$date    = sanitize_text_field( $performance['date'] ?? '' );
	$time    = sanitize_text_field( $performance['time'] ?? '' );
	$stage   = sanitize_text_field( $performance['stage'] ?? '' );
	$origin  = sanitize_text_field( $performance['origin'] ?? '' );
	$tagline = sanitize_text_field( $performance['tagline'] ?? '' );
	if ( ! $name ) {
		continue;
	}

	// Keep only proper stage names ("… Bühne").
	if ( $stage && ! preg_match( '/bühne$/iu', $stage ) ) {
		$stage = '';
	}

	$name_key = $normalize_name( $name );
	$post_id  = $existing_by_name[ $name_key ] ?? 0;

	$content = '';
	if ( $tagline ) {
		$content .= '<p>' . esc_html( $tagline ) . '</p>';
	}
	if ( $origin ) {
		$content .= '<p><strong>Herkunft:</strong> ' . esc_html( $origin ) . '</p>';
	}
	$summary = implode( ' · ', array_filter( array( $tagline, $origin ) ) );

	$action = $post_id ? 'update' : 'create';
	WP_CLI::log( sprintf( '[2016] %s: %s%s%s', $action, $name, $time ? ' @ ' . $time : '', $stage ? ' (' . $stage . ')' : '' ) );
	if ( 'dry-run' === $mode ) {
		$post_id ? ++$updated : ++$created;
		continue;
	}

	$post_data = array(
		'post_type'    => 'artist',
		'post_status'  => 'publish',
		'post_title'   => $name,
		'post_content' => $content,
		'post_excerpt' => $summary,
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

	update_post_meta( $post_id, '_stolze_lineup_source', $source_page );
	$existing_by_name[ $name_key ] = $post_id;
}

if ( 'apply' === $mode ) {
	stolze_invalidate_data_cache( 0 );
}

WP_CLI::success( sprintf( '%s complete: %d create, %d update.', $mode, $created, $updated ) );
