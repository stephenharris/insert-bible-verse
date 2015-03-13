<?php
add_action( 'wp_ajax_bv-load-bible', 'ibv_load_bible_ajax_handler' );
function ibv_load_bible_ajax_handler(){

	global $wpdb;
	
	if( !current_user_can( 'manage_bible_translations' ) ){
		wp_send_json_error(array(
			'message' => __( 'You do not have permission to do install bible translations', 'insert-bible-verse' )
		));
	}
	
	$nonce = isset( $_POST['data']['nonce'] )  ? $_POST['data']['nonce'] : false; 
	$key   = strtolower( $_POST['data']['translation'] );
	
	if( !wp_verify_nonce( $nonce, 'install-translation-'.$key ) ){
		wp_send_json_error(array(
			'message' => __( 'Are you sure?', 'insert-bible-verse' )
		));		
	}

	$translations = ibv_get_registered_translations();
	$lock         = 'ibv_bible_install_lock_' . $key;
	
	if( !isset( $translations[$key] ) ){
		wp_send_json_error(array(
			'message' => __( 'Translation not found', 'insert-bible-verse' )
		));
	}
	
	$translation  = $translations[$key];
	
	delete_site_option( $lock );
		
	if( get_site_option( $lock ) ){
		wp_send_json_error(array(
				'message'   => __( 'Locked', 'insert-bible-verse' )
		));
	}

	update_site_option( $lock, 1 );

	$verses = $translation['verses'];
	$offset = (int) $wpdb->get_var(
		$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->bible} WHERE translation = %s ", strtolower( $key ) )
	);

	$batch_size    = 300;

	if( !file_exists( $translation['src'] ) ){
		delete_site_option( $lock );
		wp_send_json_error(array(
			'message'   => __( 'Source file not found', 'insert-bible-verse' ),
		));
	}
	
	if( !is_readable( $translation['src'] ) ){
		delete_site_option( $lock );
		wp_send_json_error(array(
			'message'   => __( 'Source file is not readable', 'insert-bible-verse' ),
		));
	}

	$last = ibv_load_bible_manual( $key, $offset, $batch_size );

	$complete = $offset + $batch_size;
	$percent = min( 100, floor( ( 100 * $complete ) / $verses ) );

	if( 100 == $percent ){
		_ibv_update_translation_cache();
	}

	update_site_option( $lock, 0 );
	
	_ibv_update_translation_cache();
	
	wp_send_json_success(array(
		'verses'   => ( $offset + $batch_size ),
		'percent'  => $percent,
		'feedback' => sprintf( __( 'Installing %s...', 'insert-bible-verse' ), ucwords( $last['book'] ) )
	));

}


add_action( 'wp_ajax_bv-uninstall-bible', 'ibv_uninstall_bible_ajax_handler' );
function ibv_uninstall_bible_ajax_handler(){

	global $wpdb;
	
	if( !current_user_can( 'manage_bible_translations' ) ){
		wp_send_json_error(array(
			'message' => __( 'You do not have permission to do uninstall bible translations', 'insert-bible-verse' )
		));
	}
	
	$nonce = isset( $_POST['data']['nonce'] )  ? $_POST['data']['nonce'] : false;
	$key   = strtolower( $_POST['data']['translation'] );
	
	if( !wp_verify_nonce( $nonce, 'uninstall-translation-'.$key ) ){
		wp_send_json_error(array(
				'message' => __( 'Are you sure?', 'insert-bible-verse' )
		));
	}

	$translations = ibv_get_registered_translations();
	$lock         = 'ibv_bible_install_lock_' . $key;
	
	if( !isset( $translations[$key] ) ){
		wp_send_json_error(array(
			'message' => __( 'Translation not found', 'insert-bible-verse' )
		));
	}
	
	$translation  = $translations[$key];
	
	if( get_site_option( $lock ) ){
		wp_send_json_error(array(
				'message'   => __( 'Locked', 'insert-bible-verse' )
		));
	}

	update_site_option( $lock, 1 );
	
	$wpdb->delete( $wpdb->bible, array( 'translation' => $key ) );

	_ibv_update_translation_cache();

	update_site_option( $lock, 0 );

	wp_send_json_success();

}