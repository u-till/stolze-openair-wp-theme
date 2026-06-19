<?php
/**
 * Plain CMS page — title + block content (mirrors src/app/[...slug]/page.tsx).
 * Shortcodes (e.g. [eventadmin] on Helferanmeldung) render natively here.
 *
 * @package stolze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<main class="page-main">
		<h1><?php the_title(); ?></h1>
		<div class="entry-content"><?php the_content(); ?></div>
	</main>
	<?php
endwhile;

get_footer();
