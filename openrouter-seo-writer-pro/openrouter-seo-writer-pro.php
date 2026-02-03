<?php
/**
 * Plugin Name: OpenRouter SEO Writer Pro
 * Description: AI-powered SEO content generator using OpenRouter API.
 * Version: 1.0.0
 * Author: Jules
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define Constants
define( 'OR_SEO_PATH', plugin_dir_path( __FILE__ ) );
define( 'OR_SEO_URL', plugin_dir_url( __FILE__ ) );
define( 'OR_SEO_VERSION', '1.0.0' );

// Include Modular Files
require_once OR_SEO_PATH . 'inc/api.php';
require_once OR_SEO_PATH . 'inc/settings.php';
require_once OR_SEO_PATH . 'inc/meta-box.php';
require_once OR_SEO_PATH . 'inc/seo.php';

/**
 * Enqueue Admin Assets
 */
function or_seo_enqueue_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'settings_page_openrouter-seo-settings' ), true ) ) {
		return;
	}

	wp_enqueue_style( 'or-seo-admin-style', OR_SEO_URL . 'assets/css/admin.css', array(), OR_SEO_VERSION );
	wp_enqueue_script( 'or-seo-admin-script', OR_SEO_URL . 'assets/js/admin.js', array( 'jquery' ), OR_SEO_VERSION, true );

	wp_localize_script( 'or-seo-admin-script', 'or_seo_vars', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'or_seo_nonce' ),
	) );
}
add_action( 'admin_enqueue_scripts', 'or_seo_enqueue_admin_assets' );
