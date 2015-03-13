<?php

//Initialise the TinyMCE shortcode button handler
function ibv_shortcode_buttons() {

	// Don't bother doing this stuff if the current user lacks permissions
	if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ){
		return;
	}

	// Add only in Rich Editor mode
	if ( 'true' == get_user_option( 'rich_editing' ) ) {
		add_filter( 'mce_external_plugins', 'ibv_add_tinymce_plugin' );
		add_filter( 'mce_buttons', 'ibv_register_buttons' );
		add_filter( 'tiny_mce_before_init', 'ibv_load_editor_stylesheet' );
	}
	
}
add_action( 'init', 'ibv_shortcode_buttons', 20 );


function ibv_load_editor_stylesheet( $editor_styles ){
	if( empty( $editor_styles['content_css'] ) ){
		$editor_styles['content_css'] = '';
	}
	$editor_styles['content_css'] .= ',' . BIBLEVERSE_URL.'assets/css/ibv-font-icons.css';
	
	return $editor_styles;
}

// Register the tinyMCE plug-in
function ibv_add_tinymce_plugin( $plugin_array ) {
	$plugin_array['insert_verse'] = BIBLEVERSE_URL.'assets/js/ibv-tinymce.js';
	
	wp_enqueue_script( 'backbone' );
	wp_enqueue_script( 'underscore' );
	wp_enqueue_script( 'jquery' );
	wp_enqueue_media();
	wp_enqueue_script( 'mce-view' );
	wp_enqueue_script( 'image-edit' );
	
	wp_enqueue_style( 'ibv-tinymce' );
	
	return $plugin_array;
}

// registers the buttons for use
function ibv_register_buttons( $buttons ) {

	// inserts a separator between existing buttons and our new one
	array_push( $buttons, "|", "insert_verse" );
	return $buttons;
}

function bibleverse_verse_selection_dialog( $tinymce ){
	
	$data = json_decode( file_get_contents( BIBLEVERSE_DIR . 'assets/scripture/bible-data.json' ), true );
	
	$output = 'var bibleverse = ' . json_encode( array( 
		'scripture'  => $data,
		'nonce'      => wp_create_nonce( 'bibleverse-shortcode-ui-preview' ),
		'stylesheet' => BIBLEVERSE_URL . 'assets/css/ibv-frontend.css',
	) ) . ';';
	
	echo "<script type='text/javascript'>\n"; // CDATA and type='text/javascript' is not needed for HTML 5
	echo "/* <![CDATA[ */\n";
	echo "$output\n";
	echo "/* ]]> */\n";
	echo "</script>\n";
	?>
		
	<script type="text/html" id="tmpl-bible-verse">
		<div class="media-embed">
			<div class="bible-verse-shortcode-settings">

			<select id="ibv-book-field" class="ibv-book-field">
				<# 
				_.each( data.books, function( book ){ #>
					<option value="{{ book }}" <# if( book == data.model.book ){ #> selected="selected" <# } #> > {{ book }} </option>
				<# }) #>
			</select>
			
			<select class="ibv-chapter-field" id="ibv-chapter-field">
				<# for( var i=1; i <= data.max_chapters; i++ ){ #>
					<option value="{{ i }}" <# if( i == data.model.chapter ){ #> selected="selected" <# } #> > {{ i }} </option>
				<# } #>
			</select>
			<# console.log( data.model ); #>							
			<input type="text" class="ibv-verse-field" id="ibv-verse-field" autocomplete="off" placeholder="verse(s): e.g. 1 or 3-7" value="<#if( data.model.verse ){#>{{data.model.verse}}<#}#>"/>
		</div>
	</script>
	
<?php
}
add_action( 'after_wp_tiny_mce', 'bibleverse_verse_selection_dialog' );

?>