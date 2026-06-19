<?php
/**
 * Artist archive — /artists (AllArtistsList): searchable list of every artist,
 * newest slot first, rendered in time-table archive mode (name / stage / date).
 *
 * @package stolze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$artists = stolze_all_artists();
$total   = count( $artists );
?>
<div class="artists-archive" x-data="artistSearch(<?php echo (int) $total; ?>)">
	<div class="artists-archive__wrapper">
		<div class="list-header">
			<h1>All <span x-text="count"><?php echo (int) $total; ?></span> Artists</h1>
			<input type="text" placeholder="Search artists..." x-model="query" />
		</div>
		<div class="time-table">
			<?php
			foreach ( $artists as $artist ) :
				$slot  = get_post_meta( $artist->ID, 'slot', true );
				$buhne = get_post_meta( $artist->ID, 'buhne', true );
				$name  = strtolower( $artist->post_title );
				?>
				<a href="<?php echo esc_url( get_permalink( $artist->ID ) ); ?>"
					class="time-table-item"
					data-artist-name="<?php echo esc_attr( $name ); ?>"
					x-show="matches('<?php echo esc_js( $name ); ?>')">
					<span class="time-table-item__name"><?php echo esc_html( $artist->post_title ); ?></span>
					<span class="time-table-item__stage"><?php echo esc_html( $buhne ); ?></span>
					<span class="time-table-item__playtime"><?php echo esc_html( stolze_date_only( $slot ) ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</div>
<?php
get_footer();
