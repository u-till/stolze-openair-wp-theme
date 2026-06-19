<?php
/**
 * Site footer close. The rich festival footer (Kontakt / Artwork / Socials) is
 * rendered inside the year-content template-part, matching the Next.js layout
 * where <Footer> lives in YearContent rather than the global layout.
 *
 * @package stolze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php wp_footer(); ?>
</body>
</html>
