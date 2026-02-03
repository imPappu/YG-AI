<?php
/**
 * SEO Meta Tags and Schema Logic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inject SEO Meta Tags into <head>
 */
function or_seo_inject_meta_tags() {
	if ( ! is_singular() ) {
		return;
	}

	$post_id = get_the_ID();
	$seo_title = get_post_meta( $post_id, '_or_seo_title', true );
	$meta_desc = get_post_meta( $post_id, '_or_meta_desc', true );

	if ( ! empty( $seo_title ) ) {
		echo '<title>' . esc_html( $seo_title ) . '</title>' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $seo_title ) . '">' . "\n";
	}

	if ( ! empty( $meta_desc ) ) {
		echo '<meta name="description" content="' . esc_attr( $meta_desc ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $meta_desc ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'or_seo_inject_meta_tags', 1 );

/**
 * Inject JSON-LD Schema into Footer
 */
function or_seo_inject_schema_markup() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$post = get_post();
	$post_id = $post->ID;

	$schema = array(
		'@context' => 'https://schema.org',
		'@type'    => 'BlogPosting',
		'headline' => get_the_title( $post_id ),
		'author'   => array(
			'@type' => 'Person',
			'name'  => get_the_author_meta( 'display_name', $post->post_author ),
		),
		'datePublished' => get_the_date( 'c', $post_id ),
		'dateModified'  => get_the_modified_date( 'c', $post_id ),
		'description'   => get_post_meta( $post_id, '_or_meta_desc', true ),
		'mainEntityOfPage' => array(
			'@type' => 'WebPage',
			'@id'   => get_permalink( $post_id ),
		),
	);

	echo "\n" . '<script type="application/ld+json">' . "\n";
	echo json_encode( $schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	echo "\n" . '</script>' . "\n";
}
add_action( 'wp_footer', 'or_seo_inject_schema_markup' );
