<?php
function insertbibleverse_admin_init(){

	register_setting( 'writing', 'ibv_translations' );
	
	register_setting( 'writing', 'ibv_translation' );
		
	add_settings_section( 'insertbibleverse_section', 'Insert Bible Verse', 'ibv_settings_fields_intro', 'writing' );

	add_settings_field( 
		'ibv_translations', 
		__( 'Installed translation:', 'insertbibleverse' ), 
		'ibv_translations_settings_field', 
		'writing', 
		'insertbibleverse_section'
	);
	
	add_settings_field(
		'ibv_translation',
		__( 'Translation:', 'insertbibleverse' ),
		'insertbibleverse_default_translation_settings_field',
		'writing', 
		'insertbibleverse_section'
	);
		
}
add_action( 'admin_init', 'insertbibleverse_admin_init' );

function ibv_settings_fields_intro(){
	//Something to target
	echo '<div id="ibv-settings"></div>';
	
}

function ibv_translations_settings_field(){
	
	$translations = ibv_get_registered_translations();
	$available    = ibv_get_available_translations();

	foreach( $translations as $key => $translation ){
		
		if( !$translation['local'] ){
			printf( 
				'<p>%s <span class="description">%s</span></p>',
				$translation['label'],
				'Cannot be installed.'
			);
		}else{
			if( in_array( $key, $available ) ){
				printf(
					'<p>%s <span class="description">%s | <a href="#" class="ibv-uninstall-bible" data-translation="%s" data-nonce="%s">%s</a></span></p>',
					$translation['label'],
					__( 'Installed.', '' ),
					esc_attr( $key ),
					esc_attr( wp_create_nonce( 'uninstall-translation-'.$key ) ),
					__( 'Uninstall.', '' )
				);
			}else{
				global $wpdb;
				$verses = (int) $wpdb->get_var(
					$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->bible} WHERE translation = %s ", strtolower( $key ) )
				);
				
				$percent = min( 100, floor( ( 100 * $verses ) / $translation['verses'] ) );
				
				printf(
					'<p>
						%s 
						<span class="description">
							<a href="#" class="ibv-install-bible" data-translation="%s" data-percent="%s" data-nonce="%s">%s</a>
						</span>
						<span class="description" style="display:block">%s</span>
					</p>',
					$translation['label'],
					esc_attr( strtolower( $key ) ),
					esc_attr( $percent ),
					esc_attr( wp_create_nonce( 'install-translation-'.$key ) ),
					__( 'Install.', '' ),
					__( 'This may take a few minutes. If you close the window you can always continue it later.', '' )
				);
			}
			
			wp_enqueue_script( 'ibv-install-bible' );
			wp_enqueue_style( 'ibv-install-bible' );
		}
	}
	
}


function insertbibleverse_default_translation_settings_field(){
	
	$all       = ibv_get_registered_translations();
	$available = ibv_get_available_translations();
	$current   = ibv_get_site_translation();

	echo '<select name="ibv_translation">';
	
	foreach( $available as $key ){

		if( !isset( $all[$key] ) ){
			continue;
		}
		
		printf( 
			'<option value="%s" %s>%s</option>', 
			esc_attr( $key ), 
			selected( $key, $current, false ),
			esc_html( $all[$key]['label'] )
		);
		
	}
	
	echo '</select>';
	
}


// Add settings link on plugin page
function ibv_plugin_settings_link( $links ) {
	$settings_link = sprintf( '<a href="options-writing.php#ibv-settings">%s</a>', __( 'Settings', '' ) );
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_insert-bible-verse/insert-bible-verse.php', 'ibv_plugin_settings_link' );
