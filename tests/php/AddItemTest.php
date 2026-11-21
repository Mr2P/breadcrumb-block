<?php
/**
 * Tests for Breadcrumbs::add_item().
 *
 * @package BreadcrumbBlock\Tests
 */

namespace BreadcrumbBlock\Tests;

use BreadcrumbBlock\Breadcrumbs;

/**
 * Behavior of the add_item() method and the breadcrumb_block_get_item filter.
 */
class AddItemTest extends TestCase {

	/**
	 * Items strip tags and keep name, link and attributes.
	 *
	 * @return void
	 */
	public function test_add_item_strips_tags_from_name() {
		$breadcrumbs = Breadcrumbs::get_instance();
		$breadcrumbs->add_item( 'Home<script>', 'http://example.org/', [ 'rel' => 'home' ] );

		$this->assertSame(
			[
				[
					'Home',
					'http://example.org/',
					[ 'rel' => 'home' ],
				],
			],
			$breadcrumbs->get_items()
		);
	}

	/**
	 * Items are removed when the breadcrumb_block_get_item filter returns false.
	 *
	 * @return void
	 */
	public function test_add_item_removes_item_when_filter_returns_false() {
		$this->override_apply_filters(
			static function ( $hook_name, $value ) {
				if ( 'breadcrumb_block_get_item' === $hook_name ) {
					return false;
				}

				return $value;
			}
		);

		$breadcrumbs = Breadcrumbs::get_instance();
		$breadcrumbs->add_item( 'Home', 'http://example.org/' );

		$this->assertSame( [], $breadcrumbs->get_items() );
	}

	/**
	 * Items are modified when the breadcrumb_block_get_item filter returns changes.
	 *
	 * @return void
	 */
	public function test_add_item_allows_filter_to_modify_item() {
		$this->override_apply_filters(
			static function ( $hook_name, $value ) {
				if ( 'breadcrumb_block_get_item' === $hook_name ) {
					return [ 'Changed', $value[1], $value[2] ];
				}

				return $value;
			}
		);

		$breadcrumbs = Breadcrumbs::get_instance();
		$breadcrumbs->add_item( 'Home', 'http://example.org/' );

		$this->assertSame( 'Changed', $breadcrumbs->get_items()[0][0] );
	}
}
