<?php
/**
 * Data access — mirrors the GraphQL queries the Next.js frontend used.
 *
 * @package stolze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The "latest" year — most recently created jahr post.
 *
 * Matches GraphQL `jahre(first: 1)` whose default order is post_date DESC.
 *
 * @return WP_Post|null
 */
function stolze_latest_year() {
	$q = new WP_Query(
		array(
			'post_type'      => 'jahr',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);
	return $q->have_posts() ? $q->posts[0] : null;
}

/**
 * Resolve a jahr post by its 4-digit title (used by the /year/{YYYY} route).
 *
 * @param string $year Four-digit year.
 * @return WP_Post|null
 */
function stolze_year_by_title( $year ) {
	$q = new WP_Query(
		array(
			'post_type'      => 'jahr',
			'title'          => $year,
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		)
	);
	return $q->have_posts() ? $q->posts[0] : null;
}

/**
 * All years for the year-selector, sorted by title descending.
 *
 * @return WP_Post[]
 */
function stolze_all_years() {
	$q = new WP_Query(
		array(
			'post_type'      => 'jahr',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);
	$years = $q->posts;
	usort(
		$years,
		static function ( $a, $b ) {
			return (int) $b->post_title <=> (int) $a->post_title;
		}
	);
	return $years;
}

/**
 * Posts of a CPT linked to a given year via the ACF `jahr` relationship.
 *
 * The relationship is stored as a serialized array of post IDs, so we match
 * the quoted id with a LIKE meta query (same selectivity as WPGraphQL's
 * `where: { jahrId }`).
 *
 * @param string $post_type Post type (artist|sponsor|foodtruck).
 * @param int    $year_id   jahr post ID.
 * @param string $orderby   Optional orderby.
 * @return WP_Post[]
 */
function stolze_by_year( $post_type, $year_id, $orderby = 'date' ) {
	$q = new WP_Query(
		array(
			'post_type'      => $post_type,
			'posts_per_page' => -1,
			'orderby'        => $orderby,
			'order'          => 'ASC',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'     => 'jahr',
					'value'   => '"' . (int) $year_id . '"',
					'compare' => 'LIKE',
				),
			),
		)
	);
	return $q->posts;
}

/**
 * Artists for a year, sorted by slot ascending (the Lineup order).
 *
 * @param int $year_id jahr post ID.
 * @return WP_Post[]
 */
function stolze_artists_for_year( $year_id ) {
	$artists = stolze_by_year( 'artist', $year_id );
	usort(
		$artists,
		static function ( $a, $b ) {
			return strcmp( (string) get_post_meta( $a->ID, 'slot', true ), (string) get_post_meta( $b->ID, 'slot', true ) );
		}
	);
	return $artists;
}

/**
 * Every artist, newest slot first (the /artists archive).
 *
 * @return WP_Post[]
 */
function stolze_all_artists() {
	$q       = new WP_Query(
		array(
			'post_type'      => 'artist',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);
	$artists = $q->posts;
	usort(
		$artists,
		static function ( $a, $b ) {
			return strcmp( (string) get_post_meta( $b->ID, 'slot', true ), (string) get_post_meta( $a->ID, 'slot', true ) );
		}
	);
	return $artists;
}

/**
 * The most recent year title an artist played (for the modal back-link).
 *
 * @param int $artist_id Artist post ID.
 * @return string Four-digit year, or ''.
 */
function stolze_artist_latest_year_title( $artist_id ) {
	$related = get_field( 'jahr', $artist_id );
	if ( empty( $related ) || ! is_array( $related ) ) {
		return '';
	}
	$titles = array();
	foreach ( $related as $node ) {
		$title = is_object( $node ) ? $node->post_title : get_the_title( (int) $node );
		if ( $title ) {
			$titles[] = (int) $title;
		}
	}
	if ( empty( $titles ) ) {
		return '';
	}
	rsort( $titles );
	return (string) $titles[0];
}

/**
 * Flat list of items for a WP menu looked up by slug.
 *
 * @param string $slug Menu slug.
 * @return array<int, array{label:string, url:string}>
 */
function stolze_menu_items( $slug ) {
	$menu = wp_get_nav_menu_object( $slug );
	if ( ! $menu ) {
		return array();
	}
	$items = wp_get_nav_menu_items( $menu->term_id );
	if ( ! $items ) {
		return array();
	}
	$out = array();
	foreach ( $items as $item ) {
		$out[] = array(
			'label' => $item->title,
			'url'   => $item->url,
		);
	}
	return $out;
}

/**
 * The href for a year in the selector: most-recent → '/', otherwise /year/{title}.
 *
 * @param WP_Post $year        jahr post.
 * @param string  $latest_title Title of the most-recent year.
 * @return string
 */
function stolze_year_href( $year, $latest_title ) {
	if ( (string) $year->post_title === (string) $latest_title ) {
		return home_url( '/' );
	}
	return home_url( '/year/' . $year->post_title );
}
