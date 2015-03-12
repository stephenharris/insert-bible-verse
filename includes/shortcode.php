<?php
add_shortcode( 'bible_verse', 'bible_verse_shortcode_handler' );
function bible_verse_shortcode_handler( $atts = array() ){
	
	$atts = shortcode_atts( array(
		'book'        => false,
		'chapter'     => false,
		'verse'       => false,
		'translation' => ibv_get_site_translation(),
	), $atts );
	
	if( !$atts['book'] || !$atts['chapter'] ){
		return;
	}
	
	$translation = $atts['translation'];
	unset( $atts['translation'] );
	
	$verses = explode( '-', $atts['verse'] );
	$verse_start = $verses[0];
	$verse_end = isset( $verses[1] ) ? $verses[1] : false; 
	
	$verse_text = ibv_get_verse( $translation, $atts['book'], $atts['chapter'], $verse_start, $verse_end );
	
	$verse_summary = ibv_get_verse_summary( $atts['book'], $atts['chapter'], $verse_start, $verse_end );
	
	if( !$verse_text ){
		return;
	}
	
	$cite = '<cite>' . $verse_summary . ' (' . $translation . ')</cite>';
	
	$html = "<blockquote class='bible-verse-text'> <p>$verse_text</p> $cite </blockquote>";
	
	wp_enqueue_style( 'ibv-frontend' );
	
	return $html;
}

add_action( 'wp_ajax_bibleverse_do_shortcode', 'bible_verse_shortcode_preview_handler' );
function bible_verse_shortcode_preview_handler(){
	
	$shortcode = ! empty( $_POST['shortcode'] ) ? stripslashes( $_POST['shortcode'] ) : null;
	$post_id   = ! empty( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : null;
	$nonce     = ! empty( $_POST['nonce'] ) ? $_POST['nonce'] : null;
	
	if ( ! current_user_can( 'edit_post', $post_id ) || ! wp_verify_nonce( $nonce, 'bibleverse-shortcode-ui-preview' ) ) {
		echo esc_html__( 'You do not have permission to preview bible verses', 'shortcode-ui' );
		exit;
	}
	
	global $post;
	$post = get_post( $post_id );
	setup_postdata( $post );
	echo do_shortcode( $shortcode );

	exit;
}