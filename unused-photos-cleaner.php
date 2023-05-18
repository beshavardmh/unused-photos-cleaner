<?php
/**
 * Plugin Name: Unused Photos Cleaner
 * Plugin URI: https://github.com/beshavardmh
 * Description: Deletes unused photos from the uploads folder.
 * Version: 1.0.0
 * Author: MH Beshavard
 * Author URI: https://linkedin.com/in/beshavardmh
 */

if (!defined('ABSPATH')) die('No direct access allowed');

// Add menu item for the plugin
function unused_photos_cleaner_menu() {
	add_menu_page(
		'Unused Photos Cleaner',
		'Unused Photos Cleaner',
		'manage_options',
		'unused-photos-cleaner',
		'unused_photos_cleaner_page',
		'dashicons-editor-unlink',
	);
}

// Callback function to render the plugin page
function unused_photos_cleaner_page() {
	include_once  'includes/menu-page.php';
}

// Enqueue the JavaScript file
function unused_photos_cleaner_enqueue_scripts() {
	// Register plugin js scripts
	wp_enqueue_script( 'unused-photos-cleaner',
		plugin_dir_url( __FILE__ ) . 'assets/js/unused-photos-cleaner.js',
		array( 'jquery' ),
		'1.0',
		true,
	);

	// Register plugin css styles
	wp_enqueue_style( 'unused-photos-cleaner',
		plugin_dir_url( __FILE__ ) . 'assets/css/unused-photos-cleaner.css',
		[],
		'1.0',
	);

	// Localize the AJAX URL
	wp_localize_script( 'unused-photos-cleaner', 'unused_photos_cleaner_ajax', array(
		'ajaxurl' => admin_url( 'admin-ajax.php' )
	) );
}

// Add action hooks
add_action( 'admin_menu', 'unused_photos_cleaner_menu' );
add_action( 'admin_enqueue_scripts', 'unused_photos_cleaner_enqueue_scripts' );

// Include other files
require_once __DIR__ . '/includes/ajax-functions.php';
require_once __DIR__ . '/includes/photo-functions.php';
require_once __DIR__ . '/includes/scan-functions.php';