<?php
/**
 * OpenRouter API Logic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetch available models from OpenRouter
 */
function or_seo_fetch_models( $api_key ) {
	$response = wp_remote_get( 'https://openrouter.ai/api/v1/models', array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $api_key,
			'HTTP-Referer'  => get_site_url(),
			'X-Title'       => get_bloginfo( 'name' ),
		),
	) );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( ! isset( $data['data'] ) ) {
		return new WP_Error( 'api_error', 'Invalid response from OpenRouter' );
	}

	$models = array();
	foreach ( $data['data'] as $model ) {
		$models[] = $model['id'];
	}

	return $models;
}

/**
 * Generate content using OpenRouter API
 */
function or_seo_generate_content( $prompt, $system_prompt ) {
	$api_key = get_option( 'or_seo_api_key' );
	$model   = get_option( 'or_seo_default_model', 'openai/gpt-3.5-turbo' );

	if ( empty( $api_key ) ) {
		return new WP_Error( 'missing_api_key', 'OpenRouter API Key is missing.' );
	}

	$response = wp_remote_post( 'https://openrouter.ai/api/v1/chat/completions', array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $api_key,
			'HTTP-Referer'  => get_site_url(),
			'X-Title'       => get_bloginfo( 'name' ),
			'Content-Type'  => 'application/json',
		),
		'body'    => json_encode( array(
			'model'    => $model,
			'messages' => array(
				array( 'role' => 'system', 'content' => $system_prompt ),
				array( 'role' => 'user', 'content' => $prompt ),
			),
		) ),
		'timeout' => 60,
	) );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( isset( $data['error'] ) ) {
		return new WP_Error( 'api_error', $data['error']['message'] );
	}

	if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
		return new WP_Error( 'api_error', 'Unexpected response from AI' );
	}

	return $data['choices'][0]['message']['content'];
}

/**
 * Get the hard-coded System Prompt
 */
function or_seo_get_system_prompt() {
	return "You are a Senior SEO Copywriter. Write in a professional yet conversational tone.
Use HTML formatting (<h2>, <h3>, <ul>, <li>).
Avoid AI clichés like \"In the ever-evolving landscape\" or \"unlock your potential.\"
Ensure the primary keyword appears in the first 100 words.
Return a JSON object at the very end of the response containing: \"seo_title\" (max 60 chars) and \"meta_description\" (max 155 chars).";
}
