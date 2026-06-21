<?php
/**
 * Parse downloaded Openairguide Stolze Openair history pages into JSON.
 *
 * Usage: php scripts/parse-openairguide-history.php /tmp output.json
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$input_dir   = $argv[1] ?? '/tmp';
$output_file = $argv[2] ?? '/tmp/stolze-lineups.json';
$detail_dir  = $argv[3] ?? rtrim( $input_dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'stolze-artists';
$years       = array( 2015, 2013, 2012, 2011, 2010 );
$output      = array();

libxml_use_internal_errors( true );

foreach ( $years as $year ) {
	$file = rtrim( $input_dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'stolze-' . $year . '.html';
	if ( ! is_readable( $file ) ) {
		fwrite( STDERR, "Missing source file: {$file}\n" );
		exit( 1 );
	}

	$dom = new DOMDocument();
	$dom->loadHTMLFile( $file, LIBXML_NOERROR | LIBXML_NOWARNING );
	$xpath = new DOMXPath( $dom );
	$rows  = array();

	$date_nodes = $xpath->query( '//div[contains(concat(" ", normalize-space(@class), " "), " ro-date ")]' );
	foreach ( $date_nodes as $date_node ) {
		$date_id = $date_node->getAttribute( 'id' );
		if ( ! preg_match( '/^(\d{2})(\d{2})$/', $date_id, $date_parts ) ) {
			continue;
		}
		$day   = (int) $date_parts[1];
		$month = (int) $date_parts[2];
		$date  = $day && $month ? sprintf( '%04d-%02d-%02d', $year, $month, $day ) : '';

		$cards = $xpath->query(
			'.//div[contains(concat(" ", normalize-space(@class), " "), " teaser-s ")][.//span[contains(concat(" ", normalize-space(@class), " "), " teaser-festivals ")]]',
			$date_node
		);
		foreach ( $cards as $card ) {
			$name_node = $xpath->query( './/span[contains(concat(" ", normalize-space(@class), " "), " teaser-festivals ")]', $card )->item( 0 );
			$link_node = $xpath->query( './/a[.//span[contains(concat(" ", normalize-space(@class), " "), " teaser-festivals ")]]', $card )->item( 0 );
			if ( ! $name_node ) {
				continue;
			}

			$time        = '';
			$stage_parts = array();
			$meta_spans  = $xpath->query( './/div[contains(concat(" ", normalize-space(@class), " "), " card-text ")]/span', $card );
			foreach ( $meta_spans as $span ) {
				$value = trim( preg_replace( '/\s+/u', ' ', $span->textContent ) );
				if ( preg_match( '/^(\d{1,2}:\d{2})$/', $value, $time_match ) ) {
					$time = $time_match[1];
				} elseif ( '' !== $value ) {
					$stage_parts[] = $value;
				}
			}

			$band_node = $xpath->query( './/*[@data-bandinfoid]', $card )->item( 0 );
			$href      = $link_node ? $link_node->getAttribute( 'href' ) : '';
			$href      = preg_replace( '#^\./#', 'https://www.openairguide.net/', $href );
			$source_id = $band_node ? $band_node->getAttribute( 'data-bandinfoid' ) : '';
			$details   = array(
				'origin'       => '',
				'genre'        => '',
				'social_links' => array(),
				'image_url'    => '',
			);

			$detail_file = rtrim( $detail_dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . $source_id . '.html';
			if ( $source_id && is_readable( $detail_file ) ) {
				$detail_dom = new DOMDocument();
				$detail_dom->loadHTMLFile( $detail_file, LIBXML_NOERROR | LIBXML_NOWARNING );
				$detail_xpath = new DOMXPath( $detail_dom );
				$origin_node  = $detail_xpath->query( '//*[@id="bandinfo"]//h4[contains(normalize-space(.), "Herkunft")]/following-sibling::span[1]' )->item( 0 );
				$genre_node   = $detail_xpath->query( '//*[@id="bandinfo"]//h4[contains(normalize-space(.), "Genre")]/following-sibling::span[1]' )->item( 0 );
				$image_node   = $detail_xpath->query( '//meta[@property="og:image"]/@content' )->item( 0 );
				$details['origin']    = $origin_node ? trim( preg_replace( '/\s+/u', ' ', $origin_node->textContent ) ) : '';
				$details['genre']     = $genre_node ? trim( preg_replace( '/\s+/u', ' ', $genre_node->textContent ) ) : '';
				$details['image_url'] = $image_node ? trim( $image_node->nodeValue ) : '';

				$link_nodes = $detail_xpath->query( '//*[@id="bandinfo"]//h4[contains(normalize-space(.), "Website")]/following-sibling::div[contains(concat(" ", normalize-space(@class), " "), " some ")]//a[@href]' );
				foreach ( $link_nodes as $social_node ) {
					$url = trim( $social_node->getAttribute( 'href' ) );
					if ( ! preg_match( '#^https?://#i', $url ) ) {
						continue;
					}
					$host  = strtolower( (string) parse_url( $url, PHP_URL_HOST ) );
					$label = 'Website';
					foreach ( array( 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'facebook' => 'Facebook', 'spotify' => 'Spotify', 'bandcamp' => 'Bandcamp', 'soundcloud' => 'SoundCloud' ) as $needle => $platform ) {
						if ( str_contains( $host, $needle ) ) {
							$label = $platform;
							break;
						}
					}
					$details['social_links'][] = array(
						'link_icon'  => '',
						'link_label' => $label,
						'url'        => $url,
					);
				}
			}

			$rows[]    = array(
				'name'       => html_entity_decode( trim( preg_replace( '/\s+/u', ' ', $name_node->textContent ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
				'date'       => $date,
				'time'       => $time,
				'stage'      => implode( ' / ', $stage_parts ),
				'source_url' => $href,
				'source_id'  => $source_id,
				'origin'     => $details['origin'],
				'genre'      => $details['genre'],
				'social_links' => $details['social_links'],
				'image_url'  => $details['image_url'],
			);
		}
	}

	$output[ (string) $year ] = $rows;
}

$json = json_encode( $output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
if ( false === $json || false === file_put_contents( $output_file, $json . "\n" ) ) {
	fwrite( STDERR, "Could not write dataset: {$output_file}\n" );
	exit( 1 );
}

foreach ( $output as $year => $rows ) {
	echo $year . ': ' . count( $rows ) . " artists\n";
}
