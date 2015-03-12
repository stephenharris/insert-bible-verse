<?php
function ibv_get_verse( $translation, $book, $chapter, $verse_start, $verse_end = false ){

	$translation  = strtolower( $translation );
	$translations = ibv_get_registered_translations();

	$verse_end    = $verse_end ? $verse_end : $verse_start;

	if( !isset( $translations[$translation] ) ){
		return false;
	}

	$local = $translations[$translation]['local'];

	if( !$local ){
		return ibv_remote_get_text( $translation, $book, $chapter, $verse_start, $verse_end );

	}else{
		global $wpdb;

		$wpdb->query( 'SET SESSION group_concat_max_len = 1000000;' );

		$sql = $wpdb->prepare(
			"SELECT GROUP_CONCAT( CONCAT( '<span class=\'verse-num\'>', verse, '</span>' , verse_text ) SEPARATOR ' ')
			FROM {$wpdb->bible}
			WHERE translation=%s
			AND book=%s
			AND chapter=%s
			AND verse BETWEEN %d AND %d
			ORDER BY verse;",
			strtolower( $translation ),
			strtolower( $book ),
			$chapter,
			$verse_start,
			$verse_end
		);

		return $wpdb->get_var( $sql );
	}

}

function ibv_get_verse_summary( $book, $chapter = false, $verse_start = false, $verse_end = false ){

	$summary = ucwords( $book );

	if( $chapter && intval( $chapter ) > 0 ){
		$summary .= ' ' . intval( $chapter );

		if( $verse_start && intval( $verse_start ) > 0 ){
			$summary .= ':' . intval( $verse_start );
				
			if( $verse_end && intval( $verse_end ) > intval( $verse_start ) ){
				$summary .= '-' . intval( $verse_end );
			}
		}
	}

	return $summary;

}


function ibv_remote_get_text( $translation, $book, $chapter, $verse_start, $verse_end, $args = array() ){
	
	$passage  = $book . ' ' . $chapter . ':' . $verse_start;
	if( $verse_start != $verse_end ){
		$passage .= '-'.$verse_end;
	}
	
	$translation   = strtolower( $translation );
	$transient_key = 'bibleverse_' . $translation . md5( $passage );
	$passage_text  = get_site_transient( $transient_key );

	if( !$passage_text ){

		switch( $translation ){
				
			case 'esv':
				$args = wp_parse_args($args, array(
					'include-footnotes'          => 0,
					'include-audio-link'         => 0,
					'include-passage-references' => 0,
					'include-headings'           => 0,
					'include-subheadings'        => 0,
					'include-audio-link'         => 0,
					'include-word-ids'           => 0,
					'include-short-copyright'    => 0,
					'include-copyright'          => 0,
				));

				$api_url = 'http://www.esvapi.org/v2/rest/';
				$key     = 'IP';
				$passage = urlencode( $passage );

				$url = add_query_arg(
					compact( 'passage', 'key' ),
					$api_url . 'passageQuery/'
				);
				$url = add_query_arg( $args, $url );

				$response     = wp_remote_get( $url );
				$passage_text = wp_remote_retrieve_body( $response );
				break;

			case 'net':

				$api_url = 'http://labs.bible.org/api/';

				$url = add_query_arg(
					array(
						'passage'    => urlencode( $passage ),
						'formatting' => 'plain',
						'type'       => 'json',
					),
					$api_url
				);

				$response     = wp_remote_get( $url );
				$passage_json = wp_remote_retrieve_body( $response );
				$passage_json = json_decode( $passage_json, true );

				$passage_text = false;

				if( $passage_json ){
					foreach( $passage_json as $verse ){
						$passage_text .= sprintf( '<span class=\'verse-num\'>%s</span>%s', $verse['verse'], $verse['text'] );
					}
				}
				break;

			case 'kjv':
			case 'web':
			case 'asv':
			case 'ylt':
			case 'dby':
			default:
				$passage_text = apply_filters( 'ibv_remote_get_passge', $passage_text, $translation, $book, $chapter, $verse_start, $verse_end, $args );
				break;
		}

		if( $passage_text ){
			set_site_transient( $transient_key, $passage_text, 0 );
		}
	}

	return $passage_text;
}


function ibv_get_books(){

	$books = array(
		'genesis'         => array( 'genesis', 'gen', 'ge', 'gn' ),
		'exodus'          => array( 'exodux', 'exo', 'ex', 'exod' ),
		'leviticus'       => array( 'leviticus', 'lev', 'le', 'lv' ),
		'numbers'         => array( 'numbers', 'lev', 'le', 'lv' ),
		'deuteronomy'     => array( 'deuteronomy', 'deut', 'dt', 'de', 'deu' ),
		'joshua'          => array( 'joshua', 'josh', 'jos', 'jsh' ),
		'judges'          => array( 'judges', 'judg', 'jdg', 'jg', 'jdgs', 'jug' ),
		'ruth'            => array( 'ruth', 'rth', 'ru', 'rut' ),
		'1 samuel'        => array( '1 samuel', '1 sam', '1 sa', '1samuel', '1s', 'i sa', '1 sm', '1sa', 'i sam', '1sam', 'i samuel', '1st samuel', 'first samuel' ),
		'2 samuel'        => array( '2 samuel', '2 sam', '2 sa', '2s', 'ii sa', '2 sm', '2sa', 'ii sam', '2sam', 'ii samuel', '2samuel', '2nd samuel', 'second samuel' ),
		'1 kings'         => array( '1 kings', '1 kgs', '1 ki', '1k', 'i kgs', '1kgs', 'i ki', '1ki', 'i kings', '1kings', '1st kgs', '1st kings', 'first kings', 'first kgs', '1kin' ),
		'2 kings'         => array( '2 kings', '2 kgs', '2 ki', '2k', 'ii kgs', '2kgs', 'ii ki', '2ki', 'ii kings', '2kings', '2nd kgs', '2nd kings', 'second kings', 'second kgs', '2kin' ),
		'1 chronicles'    => array( '1 chronicles', '1 chron', '1 ch', 'i ch', '1ch', '1 chr', 'i chr', '1chr', 'i chron', '1chron', 'i chronicles', '1chronicles', '1st chronicles', 'first chronicles' ),
		'2 chronicles'    => array( '2 chronicles', '2 chron', '2 ch', 'ii ch', '2ch', 'ii chr', '2chr', 'ii chron', '2chron', 'ii chronicles', '2chronicles', '2nd chronicles', 'second chronicles' ),
		'ezra'            => array( 'ezra', 'ezr' ),
		'nehemiah'        => array( 'nehemiah', 'neh', 'ne' ),
		'esther'          => array( 'esther', 'esth', 'es', 'est' ),
		'job'             => array( 'job', 'jb' ),
		'psalms'          => array( 'psalms', 'pslm', 'ps', 'psalm', 'psa', 'psm', 'pss' ),
		'proverbs'        => array( 'proverbs', 'prov', 'pr', 'prv', 'pro' ),
		'ecclesiastes'    => array( 'ecclesiastes', 'eccles', 'ec', 'ecc' ),
		'song of solomon' => array( 'song of solomon', 'song', 'so', 'song of songs', 'sos', 'son' ),
		'isaiah'          => array( 'isaiah', 'isa', 'is' ),
		'jeremiah'        => array( 'jeremiah', 'jer', 'je', 'jr' ),
		'lamentations'    => array( 'lamentations', 'lam', 'la' ),
		'ezekiel'         => array( 'ezekiel', 'ezek', 'eze', 'ezk' ),
		'daniel'          => array( 'daniel', 'dan', 'da', 'dn' ),
		'hosea'           => array( 'hosea', 'hos', 'ho' ),
		'joel'            => array( 'joel', 'joe', 'jl' ),
		'amos'            => array( 'amos', 'am', 'amo' ),
		'obadiah'         => array( 'obadiah', 'obad', 'ob', 'oba' ),
		'jonah'           => array( 'jonah', 'jnh', 'jon' ),
		'micah'           => array( 'micah', 'mic' ),
		'nahum'           => array( 'nahum', 'nah', 'na' ),
		'habakkuk'        => array( 'habakkuk', 'hab', 'ha' ),
		'zephaniah'       => array( 'zephaniah', 'zeph', 'zep', 'zp' ),
		'haggai'          => array( 'haggai', 'hag', 'hg' ),
		'zechariah'       => array( 'zechariah', 'zech', 'zec', 'zc' ),
		'malachi'         => array( 'malachi', 'mal', 'ml' ),
		'matthew'         => array( 'matthew', 'matt', 'mt', 'mat' ),
		'mark'            => array( 'mark', 'mrk', 'mk', 'mr', 'mak' ),
		'luke'            => array( 'luke', 'luk', 'lk', 'lu' ),
		'john'            => array( 'john', 'jn', 'jhn', 'joh' ),
		'acts'            => array( 'acts', 'ac', 'act' ),
		'romans'          => array( 'romans', 'rom', 'ro', 'rm' ),
		'1 corinthians'   => array( '1 corinthians', '1 cor', '1 co', 'i co', '1co', 'i cor', '1cor', 'i corinthians', '1corinthians', '1st corinthians', 'first corinthians', '1 corin' ),
		'2 corinthians'   => array( '2 corinthians', '2 cor', '2 co', 'ii co', '2co', 'ii cor', '2cor', 'ii corinthians', '2corinthians', '2nd corinthians', 'second corinthians', '2 corin' ),
		'galatians'       => array( 'galatians', 'gal', 'ga' ),
		'ephesians'       => array( 'ephesians', 'ephes', 'eph' ),
		'philippians'     => array( 'philippians', 'phil', 'php', 'phl' ),
		'colossians'      => array( 'colossians', 'col' ),
		'1 thessalonians' => array( '1 thessalonians', '1 thess', '1 th', 'i th', '1th', 'i thes', '1thes', 'i thess', '1thess', 'i thessalonians', '1thessalonians', '1st thessalonians', 'first thessalonians', '1ts' ),
		'2 thessalonians' => array( '2 thessalonians', '2 thess', '2 th', 'ii th', '2th', 'ii thes', '2thes', 'ii thess', '2thess', 'ii thessalonians', '2thessalonians', '2nd thessalonians', 'second thessalonians', '2ts' ),
		'1 timothy'       => array( '1 timothy', '1 tim', '1 ti', 'i ti', '1ti', 'i tim', '1tim', 'i timothy', '1timothy', '1st timothy', 'first timothy' ),
		'2 timothy'       => array( '2 timothy', '2 tim', '2 ti', 'ii ti', '2ti', 'ii tim', '2tim', 'ii timothy', '2timothy', '2nd timothy', 'second timothy' ),
		'titus'           => array( 'titus', 'tit', 'ti' ),
		'philemon'        => array( 'philemon', 'philem', 'phm', 'phlm' ),
		'hebrews'         => array( 'hebrews', 'heb' ),
		'james'           => array( 'james', 'jas', 'jm' ),
		'1 peter'         => array( '1 peter', '1 pet', '1 pe', 'i pe', '1pe', 'i pet', '1pet', 'i pt', '1 pt', '1pt', 'i peter', '1peter', '1st peter', 'first peter' ),
		'2 peter'         => array( '2 peter', '2 pet', '2 pe', 'ii pe', '2pe', 'ii pet', '2pet', 'ii pt', '2 pt', '2pt', 'ii peter', '2peter', '2nd peter', 'second peter' ),
		'1 john'          => array( '1 john', '1 jn', 'i jn', '1jn', 'i jo', '1jo', 'i joh', '1joh', 'i jhn', '1 jhn', '1jhn', 'i john', '1john', '1st john', 'first john' ),
		'2 john'          => array( '2 john', '2 jn', 'ii jn', '2jn', 'ii jo', '2jo', 'ii joh', '2joh', 'ii jhn', '2 jhn', '2jhn', 'ii john', '2john', '2nd john', 'second john' ),
		'3 john'          => array( '3 john', '3 jn', 'iii jn', '3jn', 'iii jo', '3jo', 'iii joh', '3joh', 'iii jhn', '3 jhn', '3jhn', 'iii john', '3john', '3rd john', 'third john' ),
		'jude'            => array( 'jude', 'jud' ),
		'revelation'      => array( 'revelation', 'rev', 're' ),
	);

	return $books;
}

function ibv_find_book( $needle ){

	$books = ibv_get_books();
	foreach( $books as $book => $synoms ){
		if( in_array( strtolower( $needle ), $synoms ) ){
			return $book;
		}
	}

	return false;
}