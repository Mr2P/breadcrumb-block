<?php
/**
 * Plugin Name:       Breadcrumb Block
 * Description:       A simple breadcrumb trail block that supports JSON-LD structured data and is compatible with Woocommerce
 * Requires at least: 7.1
 * Requires PHP:      8.0
 * Version:           1.2.0
 * Author:            Phi Phan
 * Author URI:        https://boldblocks.net
 * Plugin URI:        https://boldblocks.net?utm_source=Breadcrumb+Block&utm_campaign=visit+site&utm_medium=link&utm_content=Plugin+URI
 * License:           GPL-2.0-or-later
 *
 * @package BoldBlocks
 * @copyright Copyright(c) 2022, Phi Phan
 */

namespace BreadcrumbBlock;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Load required files.
require_once __DIR__ . '/includes/short-title.php';
require_once __DIR__ . '/includes/primary-term.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/breadcrumbs.php';

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function breadcrumb_block_block_init() {
	register_block_type( __DIR__ . '/build', [ 'render_callback' => __NAMESPACE__ . '\\breadcrumb_block_render_block' ] );
}
add_action( 'init', __NAMESPACE__ . '\\breadcrumb_block_block_init' );
add_action( 'init', [ Short_Title::class, 'init' ] );
add_action( 'init', [ Primary_Term::class, 'init' ] );
add_action( 'init', [ Settings::class, 'init' ] );

/**
 * Renders the `boldblocks/breadcrumb-block` block on the server.
 *
 * @param  array     $attributes Block attributes.
 * @param  string    $content    Block default content.
 * @param  \WP_Block $block      Block instance.
 * @return string
 */
function breadcrumb_block_render_block( array $attributes, string $content, \WP_Block $block ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Signature required by register_block_type render callbacks.
	// Get labels.
	$labels = $attributes['labels'] ?? [];
	if ( ! empty( $attributes['homeText'] ) && empty( $labels['home'] ) ) {
		$labels['home'] = $attributes['homeText'];
	}

	$content = Breadcrumbs::get_instance()->get_breadcrumb_trail(
		[
			'separator' => $attributes['separator'] ?? '',
			'labels'    => $labels,
		]
	);

	$vars = [];
	if ( isset( $attributes['gap'] ) ) {
		$vars[] = '--bb--crumb-gap:' . $attributes['gap'] . ';';
	}

	$style = [] !== $vars ? implode( '', $vars ) : '';

	$block_classes = [];
	if ( $attributes['hideHomePage'] ?? false ) {
		$block_classes[] = 'hide-home-page';
	}
	if ( $attributes['hideCurrentPage'] ?? false ) {
		$block_classes[] = 'hide-current-page';
	}

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'style' => $style,
			'class' => implode( ' ', $block_classes ),
		)
	);

	return sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );
}
