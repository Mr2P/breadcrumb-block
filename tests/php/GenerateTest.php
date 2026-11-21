<?php
/**
 * Tests for Breadcrumbs::generate().
 *
 * @package BreadcrumbBlock\Tests
 */

namespace BreadcrumbBlock\Tests;

use BreadcrumbBlock\Breadcrumbs;
use Brain\Monkey;

/**
 * Trail generation for the basic query types.
 */
class GenerateTest extends TestCase {

	/**
	 * Home crumb and posts page title are added on the blog home.
	 *
	 * @return void
	 */
	public function test_generate_builds_home_trail() {
		$this->stub_query_conditionals( [ 'is_home' ] );
		Monkey\Functions\when( 'home_url' )->justReturn( 'http://example.org/' );
		Monkey\Functions\when( 'single_post_title' )->alias(
			static function () {
				return 'Blog';
			}
		);

		$breadcrumbs = Breadcrumbs::get_instance();
		$breadcrumbs->parse_args( [] );
		$items = $breadcrumbs->generate();

		$this->assertSame( 'Home', $items[0][0] );
		$this->assertSame( 'http://example.org/', $items[0][1] );
		$this->assertSame( [ 'rel' => 'home' ], $items[0][2] );
		$this->assertSame( 'Blog', $items[1][0] );
		$this->assertSame( '', $items[1][1] );
		$this->assertSame( [ 'aria-current' => 'page' ], $items[1][2] );
	}

	/**
	 * The whole trail is skipped on the front page.
	 *
	 * @return void
	 */
	public function test_generate_skips_trail_on_front_page() {
		$this->stub_query_conditionals( [ 'is_front_page' ] );

		$breadcrumbs = Breadcrumbs::get_instance();
		$breadcrumbs->parse_args( [] );

		$this->assertSame( [], $breadcrumbs->generate() );
	}

	/**
	 * The 404 label is added on not found pages.
	 *
	 * @return void
	 */
	public function test_generate_builds_404_trail() {
		$this->stub_query_conditionals( [ 'is_404' ] );
		Monkey\Functions\when( 'home_url' )->justReturn( 'http://example.org/' );

		$breadcrumbs = Breadcrumbs::get_instance();
		$breadcrumbs->parse_args( [] );
		$items = $breadcrumbs->generate();

		$this->assertCount( 2, $items );
		$this->assertSame( 'Home', $items[0][0] );
		$this->assertSame( 'Page not found', $items[1][0] );
		$this->assertSame( '', $items[1][1] );
	}

	/**
	 * The breadcrumb_block_get_items filter can replace the items array.
	 *
	 * @return void
	 */
	public function test_generate_applies_get_items_filter() {
		$this->stub_query_conditionals( [ 'is_home' ] );
		Monkey\Functions\when( 'home_url' )->justReturn( 'http://example.org/' );
		Monkey\Functions\when( 'single_post_title' )->alias(
			static function () {
				return 'Blog';
			}
		);
		$this->override_apply_filters(
			static function ( $hook_name, $value ) {
				if ( 'breadcrumb_block_get_items' === $hook_name ) {
					return [];
				}

				return $value;
			}
		);

		$breadcrumbs = Breadcrumbs::get_instance();
		$breadcrumbs->parse_args( [] );

		$this->assertSame( [], $breadcrumbs->generate() );
	}
}
