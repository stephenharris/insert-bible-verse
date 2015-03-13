<?php

/**
 * Activate the plugin
 */
function ibv_activate() {

	// First load the init scripts in case any rewrite functionality is being loaded
	ibv_init();
	
	ibv_install_table();
	
	//Add manage_bible_translations to any user with manage_options cap
	global $wp_roles;
	foreach( $wp_roles->roles as $role_name => $display_name ){
		$role = $wp_roles->get_role( $role_name );
		if( $role->has_cap( 'manage_options' ) ){
			$role->add_cap( 'manage_bible_translations' );
		}		
	}
	
}


/**
 * Uninstall the plug-in
 */
function ibv_uninstall() {
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->bible}" );
	
	//Remove manage_bible_translations
	global $wp_roles;
	foreach( $wp_roles->roles as $role_name => $display_name ){
		$role = $wp_roles->get_role( $role_name );
		$role->remove_cap( 'manage_bible_translations' );
	}
	
}

function ibv_install_table(){

	global $wpdb;

	$charset_collate = '';
	if ( ! empty($wpdb->charset) ){
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	}
	if ( ! empty($wpdb->collate) ){
		$charset_collate .= " COLLATE $wpdb->collate";
	}

	//Create table
	$create_bible_table = 'CREATE TABLE ' .$wpdb->bible. ' (
	id bigint(20) NOT NULL AUTO_INCREMENT,
	translation varchar(20) NOT NULL,
	book varchar(20),
	chapter int,
	verse int,
	verse_text text,
	PRIMARY KEY  (id),
	KEY translation (translation),
	KEY book (book),
	KEY chapter (chapter),
	KEY verse (verse)
	)'.$charset_collate;

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $create_bible_table );

}


function ibv_load_bible_manual( $key, $offset, $batch_size ){

	global $wpdb;

	$translations = ibv_get_registered_translations();
	$key          = strtolower( $key );
	$translation  = $translations[$key];

	$start_row = $offset + 1;
	$end_row   = $offset + $batch_size;

	$row = 0;
	$run_query = false;

	$sql = "INSERT INTO {$wpdb->bible} (`translation`, `book`, `chapter`, `verse`, `verse_text`) VALUES ";

	if( ( $handle = fopen( $translation['src'], "r" ) ) !== false ) {

		while( ( $verse = fgets( $handle ) ) !== false ) {

			$verse = explode( "\t", $verse );

			$row++;

			if( $row < $start_row ){
				continue;
			}

			if( $row > $end_row ){
				break;
			}
			
			if( empty( $verse[3] ) ){
				continue;
			}

			$sql .= $wpdb->prepare(
				'( %s, %s, %d, %d, %s ),',
				$key,
				strtolower( $verse[0] ),
				$verse[1],
				$verse[2],
				$verse[3]
			);

			$run_query = true;
		}

		fclose( $handle );
			
	}

	if( $run_query ){
		$sql = rtrim( $sql, ',' ) . ';';
		$wpdb->query( $sql );
	}
	
	return array(
		'book'    => strtolower( $verse[0] ),
		'chapter' => $verse[2],
		'verse'   => $verse[3],
	);
}

function _ibv_update_translation_cache(){

	global $wpdb;

	$translations = array();

	$all = ibv_get_registered_translations();

	foreach( $all as $key => $translation ){
		if( !$translation['local'] ){
			$translations[]  = $key;
		}else{
			$verses = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->bible} WHERE translation = %s ", strtolower( $key ) )
			);

			//Check if it is fully installed
			if( $verses >= $translation['verses'] ){
				$translations[]  = $key;
			}
		}
	}

	set_site_transient( 'ibv_translations', $translations );

	return $translations;
}
