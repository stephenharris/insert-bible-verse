<?php
/**
 * Plugin Name: Insert Bible Verse
 * Plugin URI:  http://stephenharris.info
 * Description: Allows you to insert any bible verse via a tinyMCE button
 * Version:     0.1.0
 * Author:      Stephen Harris
 * Author URI:  http://stephenharris.info
 * License:     GPLv2+
 * Text Domain: insert-bible-verse
 * Domain Path: /languages
 */ 
/**
 * Copyright (c) 2014 Stephen Harris (email : contact@stephenharris.info)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// Useful global constants
define( 'BIBLEVERSE_VERSION', '0.1.0' );
define( 'BIBLEVERSE_URL', plugin_dir_url( __FILE__ ) );
define( 'BIBLEVERSE_DIR', plugin_dir_path( __FILE__ ) );

define( 'BIBLEVERSE_TRANSLATION', 'ESV' );

/****** Install, activation & deactivation******/
require_once( BIBLEVERSE_DIR . 'includes/install.php' );

register_activation_hook( __FILE__, 'ibv_activate' );
register_deactivation_hook( __FILE__, 'ibv_deactivate' );
register_uninstall_hook( __FILE__, 'ibv_uninstall' );


/**
 * Default initialization for the plugin:
 * - Registers the default textdomain.
 * - Registers scripts/styles
 */
function ibv_init() {
	
	global $wpdb;

	$wpdb->bible = "{$wpdb->prefix}bible";

	$version = defined( 'BIBLEVERSE_VERSION' ) ? BIBLEVERSE_VERSION : false;
	$ext     = (defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG) ? '' : '.min';

	load_plugin_textdomain( 'insert-bible-verse', false, BIBLEVERSE_DIR . '/languages/' );

	//Register styles
	wp_register_style( 'ibv-font-icons', BIBLEVERSE_URL . "assets/css/ibv-font-icons{$ext}.css", array(), $version );
	wp_register_style( 'ibv-tinymce', BIBLEVERSE_URL ."assets/css/ibv-tinymce{$ext}.css", array( 'ibv-font-icons' ), $version );
	wp_register_style( 'ibv-frontend', BIBLEVERSE_URL . "assets/css/ibv-frontend{$ext}.css", array(), $version );
	wp_register_style( 'ibv-install-bible', BIBLEVERSE_URL . "assets/css/ibv-install-bible{$ext}.css", array(), $version );
	
	//Register scripts
	wp_register_script( 'ibv-tinymce', BIBLEVERSE_URL . "assets/js/ibv-tinymce{$ext}.js", array( 'jquery' ), $version );
	wp_register_script( 'ibv-install-bible', BIBLEVERSE_URL . "assets/js/ibv-install-bible{$ext}.js", array( 'jquery' ), $version );
	
}
add_action( 'init', 'ibv_init' );

require_once( BIBLEVERSE_DIR . 'includes/functions.php' );
require_once( BIBLEVERSE_DIR . 'includes/tinymce.php' );
require_once( BIBLEVERSE_DIR . 'includes/shortcode.php' );

if( is_admin() ){
	require_once( BIBLEVERSE_DIR . 'includes/admin.php' );
	require_once( BIBLEVERSE_DIR . 'includes/admin-actions.php' );
}

function ibv_get_site_translation(){
	return get_option( 'ibv_translation', BIBLEVERSE_TRANSLATION );
}

function ibv_get_registered_translations(){
	return apply_filters( 'ibv_registered_translations', array(
		'esv' => array(
			'label'  => 'English Standard Version',
			'src'    => false,
			'verses' => 31085,
			'local'  => false,
		),	
		'net' => array(
			'label'  => 'New English Translation',
			'src'    => false,
			'verses' => 31101,
			'local'  => false,
		),
		'web' => array(
			'label'  => 'World English Bible',
			'src'    => BIBLEVERSE_DIR . 'assets/scripture/web.csv',
			'verses' => 31100,
			'local'  => true,
		),
		'kjv' => array(
			'label'  => 'King James Bible',
			'src'    => BIBLEVERSE_DIR . 'assets/scripture/kjv.csv',
			'verses' => 31102,
			'local'  => true,
		),
		'asv' => array(
			'label'  => 'American Standard Version',
			'src'    => BIBLEVERSE_DIR . 'assets/scripture/asv.csv',
			'verses' => 31102,
			'local'  => true,
		),
		'wbt' => array(
			'label'  => 'Webster Bible Translation',
			'src'    => BIBLEVERSE_DIR . 'assets/scripture/wbt.csv',
			'verses' => 31102,
			'local'  => true,
		),
		'ylt' => array(
			'label'  => 'Young Literal Translation',
			'src'    => BIBLEVERSE_DIR . 'assets/scripture/ylt.csv',
			'verses' => 31102,
			'local'  => true,
		),
	));
}

function ibv_get_available_translations(){
	
	$translations = get_site_transient( 'ibv_translations' );
	
	if( false === $translations ){		
		$translations = _ibv_update_translation_cache();
	}
	
	return $translations;	
}

?>