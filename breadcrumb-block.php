<?php
/**
 * Plugin Name:       Breadcrumb Block
 * Description:       A block to display the breadcrumb trails to your site, supports schema.org microdata
 * Requires at least: 5.8
 * Requires PHP:      7.0
 * Version:           1.0.0
 * Author:            Phi Phan
 * Author URI:        https://boldblocks.net
 *
 * @package BoldBlocks
 * @copyright Copyright(c) 2022, Phi Phan
 */

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function breadcrumb_block_block_init() {
	register_block_type( __DIR__ . '/build' );
}
add_action( 'init', 'breadcrumb_block_block_init' );
