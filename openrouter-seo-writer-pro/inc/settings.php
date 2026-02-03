<?php
/**
 * Admin Settings for OpenRouter SEO Writer Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Settings Page
 */
function or_seo_add_settings_page() {
	add_options_page(
		'OpenRouter SEO Settings',
		'OpenRouter SEO',
		'manage_options',
		'openrouter-seo-settings',
		'or_seo_settings_page_html'
	);
}
add_action( 'admin_menu', 'or_seo_add_settings_page' );

/**
 * Register Settings, Sections, and Fields
 */
function or_seo_register_settings() {
	register_setting( 'or_seo_settings_group', 'or_seo_api_key', array(
		'sanitize_callback' => 'sanitize_text_field',
	) );
	register_setting( 'or_seo_settings_group', 'or_seo_default_model', array(
		'sanitize_callback' => 'sanitize_text_field',
	) );

	add_settings_section(
		'or_seo_main_section',
		'API Configuration',
		null,
		'openrouter-seo-settings'
	);

	add_settings_field(
		'or_seo_api_key_field',
		'OpenRouter API Key',
		'or_seo_api_key_field_render',
		'openrouter-seo-settings',
		'or_seo_main_section'
	);

	add_settings_field(
		'or_seo_default_model_field',
		'Default AI Model',
		'or_seo_default_model_field_render',
		'openrouter-seo-settings',
		'or_seo_main_section'
	);
}
add_action( 'admin_init', 'or_seo_register_settings' );

/**
 * Render API Key Field
 */
function or_seo_api_key_field_render() {
	$api_key = get_option( 'or_seo_api_key' );
	?>
	<input type="password" name="or_seo_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text">
	<?php
}

/**
 * Render Default Model Field
 */
function or_seo_default_model_field_render() {
	$default_model = get_option( 'or_seo_default_model' );
	?>
	<select name="or_seo_default_model" id="or_seo_default_model">
		<option value="<?php echo esc_attr( $default_model ); ?>"><?php echo esc_html( $default_model ? $default_model : 'Enter API Key and Test Connection' ); ?></option>
	</select>
	<button type="button" id="or-seo-test-connection" class="button">Test Connection & Fetch Models</button>
	<span id="or-seo-connection-status"></span>
	<?php
}

/**
 * Settings Page HTML
 */
function or_seo_settings_page_html() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'or_seo_settings_group' );
			do_settings_sections( 'openrouter-seo-settings' );
			submit_button( 'Save Settings' );
			?>
		</form>
	</div>
	<?php
}

/**
 * AJAX Handler: Test Connection and Fetch Models
 */
function or_seo_test_connection_handler() {
	check_ajax_referer( 'or_seo_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Unauthorized' );
	}

	$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( $_POST['api_key'] ) : '';

	if ( empty( $api_key ) ) {
		wp_send_json_error( 'API Key is required' );
	}

	$models = or_seo_fetch_models( $api_key );

	if ( is_wp_error( $models ) ) {
		wp_send_json_error( $models->get_error_message() );
	}

	wp_send_json_success( $models );
}
add_action( 'wp_ajax_test_openrouter_connection', 'or_seo_test_connection_handler' );
