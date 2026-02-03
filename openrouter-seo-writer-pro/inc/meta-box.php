<?php
/**
 * Meta Box for Content Generation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add Meta Box to Post Edit Screen
 */
function or_seo_add_meta_box() {
	$screens = array( 'post', 'page' );
	foreach ( $screens as $screen ) {
		add_meta_box(
			'or_seo_content_generator',
			'OpenRouter SEO Writer Pro',
			'or_seo_meta_box_html',
			$screen,
			'normal',
			'high'
		);
	}
}
add_action( 'add_meta_boxes', 'or_seo_add_meta_box' );

/**
 * Meta Box HTML
 */
function or_seo_meta_box_html( $post ) {
	?>
	<div class="or-seo-meta-box-container">
		<p>
			<label for="or_seo_primary_keyword"><strong>Primary Keyword:</strong></label><br>
			<input type="text" id="or_seo_primary_keyword" class="large-text" placeholder="e.g., Best WordPress Hosting 2024">
		</p>

		<p>
			<button type="button" id="or-seo-generate-outline" class="button button-primary">Stage 1: Generate Outline</button>
		</p>

		<div id="or-seo-outline-container" style="display:none;">
			<p><label for="or_seo_outline"><strong>Edit Outline (H2/H3 tags):</strong></label></p>
			<textarea id="or_seo_outline" class="large-text" rows="10"></textarea>
			<p>
				<button type="button" id="or-seo-generate-draft" class="button button-primary">Stage 2: Generate Full Draft</button>
			</p>
		</div>

		<div id="or-seo-status-message"></div>
		<div id="or-seo-spinner" class="spinner" style="float:none;"></div>
	</div>
	<?php
}

/**
 * AJAX Handler: Generate Outline (Stage 1)
 */
function or_seo_generate_outline_handler() {
	check_ajax_referer( 'or_seo_nonce', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( 'Unauthorized' );
	}

	$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( $_POST['keyword'] ) : '';

	if ( empty( $keyword ) ) {
		wp_send_json_error( 'Keyword is required' );
	}

	$prompt = "Generate a comprehensive HTML outline for an article about '$keyword'. Use only <h2> and <h3> tags. Do not include any other text.";
	$system_prompt = or_seo_get_system_prompt();

	$outline = or_seo_generate_content( $prompt, $system_prompt );

	if ( is_wp_error( $outline ) ) {
		wp_send_json_error( $outline->get_error_message() );
	}

	wp_send_json_success( $outline );
}
add_action( 'wp_ajax_generate_outline', 'or_seo_generate_outline_handler' );

/**
 * AJAX Handler: Generate Full Draft (Stage 2)
 */
function or_seo_generate_draft_handler() {
	check_ajax_referer( 'or_seo_nonce', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( 'Unauthorized' );
	}

	$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( $_POST['keyword'] ) : '';
	$outline = isset( $_POST['outline'] ) ? wp_kses_post( $_POST['outline'] ) : '';
	$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

	if ( empty( $keyword ) || empty( $outline ) ) {
		wp_send_json_error( 'Keyword and Outline are required' );
	}

	$prompt = "Write a full SEO-optimized article based on this outline for the keyword '$keyword':\n\n$outline\n\nRemember to include the JSON object with seo_title and meta_description at the very end.";
	$system_prompt = or_seo_get_system_prompt();

	$content = or_seo_generate_content( $prompt, $system_prompt );

	if ( is_wp_error( $content ) ) {
		wp_send_json_error( $content->get_error_message() );
	}

	// Extract JSON for SEO Title and Meta Description
	$seo_data = array(
		'seo_title' => '',
		'meta_description' => '',
		'clean_content' => $content
	);

	if ( preg_match( '/\{(?:[^{}]|(?R))*\}/s', $content, $matches ) ) {
		$json_str = $matches[0];
		$decoded = json_decode( $json_str, true );
		if ( $decoded ) {
			$seo_data['seo_title'] = isset( $decoded['seo_title'] ) ? sanitize_text_field( $decoded['seo_title'] ) : '';
			$seo_data['meta_description'] = isset( $decoded['meta_description'] ) ? sanitize_text_field( $decoded['meta_description'] ) : '';
			$seo_data['clean_content'] = trim( str_replace( $json_str, '', $content ) );

			// Save to Post Meta
			if ( $post_id ) {
				update_post_meta( $post_id, '_or_seo_title', $seo_data['seo_title'] );
				update_post_meta( $post_id, '_or_meta_desc', $seo_data['meta_description'] );
			}
		}
	}

	wp_send_json_success( $seo_data );
}
add_action( 'wp_ajax_generate_draft', 'or_seo_generate_draft_handler' );
