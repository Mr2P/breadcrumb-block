<?php
/**
 * Tests for the breadcrumb short title feature.
 *
 * @package BreadcrumbBlock\Tests
 */

namespace BreadcrumbBlock\Tests;

use BreadcrumbBlock\Breadcrumbs;
use Brain\Monkey;

/**
 * Short title meta overrides item names in the trail and JSON-LD.
 */
class ShortTitleTest extends TestCase {

	/**
	 * A post meta short title replaces the item name.
	 *
	 * @return void
	 */
	public function test_add_item_uses_post_short_title() {
		Monkey\Functions\when( 'get_post_meta' )->alias(
			static function ( $id ) {
				return 42 === $id ? 'Short Post' : '';
			}
		);

		$breadcrumbs = Breadcrumbs::get_instance();
		$breadcrumbs->add_item(
			'Long Post Title',
			'http://example.org/post/',
			[],
			[
				'type'   => 'single',
				'object' => new \WP_Post( 42 ),
			]
		);

		$this->assertSame( 'Short Post', $breadcrumbs->get_items()[0][0] );
	}

	/**
	 * A term meta short title replaces the item name.
	 *
	 * @return void
	 */
	public function test_add_item_uses_term_short_title() {
		Monkey\Functions\when( 'get_term_meta' )->alias(
			static function ( $id ) {
				return 7 === $id ? 'Short Term' : '';
			}
		);

		$breadcrumbs = Breadcrumbs::get_instance();
		$breadcrumbs->add_item(
			'Long Term Name',
			'http://example.org/term/',
			[],
			[
				'type'   => 'term',
				'object' => new \WP_Term( 7, 'Long Term Name' ),
			]
		);

		$this->assertSame( 'Short Term', $breadcrumbs->get_items()[0][0] );
	}

	/**
	 * Items without a short title keep the real name.
	 *
	 * @return void
	 */
	public function test_add_item_falls_back_without_short_title() {
		Monkey\Functions\when( 'get_post_meta' )->justReturn( '' );

		$breadcrumbs = Breadcrumbs::get_instance();
		$breadcrumbs->add_item(
			'Long Post Title',
			'http://example.org/post/',
			[],
			[
				'type'   => 'single',
				'object' => new \WP_Post( 42 ),
			]
		);

		$this->assertSame( 'Long Post Title', $breadcrumbs->get_items()[0][0] );
	}

	/**
	 * The breadcrumb_block_get_short_title filter overrides the meta value.
	 *
	 * @return void
	 */
	public function test_short_title_filter_overrides_meta() {
		Monkey\Functions\when( 'get_post_meta' )->alias(
			static function ( $id ) {
				return 42 === $id ? 'Short Post' : '';
			}
		);
		$this->override_apply_filters(
			static function ( $hook_name, $value ) {
				if ( 'breadcrumb_block_get_short_title' === $hook_name ) {
					return 'Filtered Short';
				}

				return $value;
			}
		);

		$breadcrumbs = Breadcrumbs::get_instance();
		$breadcrumbs->add_item(
			'Long Post Title',
			'http://example.org/post/',
			[],
			[
				'type'   => 'single',
				'object' => new \WP_Post( 42 ),
			]
		);

		$this->assertSame( 'Filtered Short', $breadcrumbs->get_items()[0][0] );
	}

	/**
	 * The breadcrumb_block_get_short_title filter can disable the short title.
	 *
	 * @return void
	 */
	public function test_short_title_filter_can_disable() {
		Monkey\Functions\when( 'get_post_meta' )->alias(
			static function ( $id ) {
				return 42 === $id ? 'Short Post' : '';
			}
		);
		$this->override_apply_filters(
			static function ( $hook_name, $value ) {
				if ( 'breadcrumb_block_get_short_title' === $hook_name ) {
					return '';
				}

				return $value;
			}
		);

		$breadcrumbs = Breadcrumbs::get_instance();
		$breadcrumbs->add_item(
			'Long Post Title',
			'http://example.org/post/',
			[],
			[
				'type'   => 'single',
				'object' => new \WP_Post( 42 ),
			]
		);

		$this->assertSame( 'Long Post Title', $breadcrumbs->get_items()[0][0] );
	}

	/**
	 * The single post trail shows the short title in markup and JSON-LD.
	 *
	 * @return void
	 */
	public function test_short_title_in_trail_and_jsonld() {
		$this->stub_query_conditionals( [ 'is_single' ] );
		Monkey\Functions\when( 'home_url' )->justReturn( 'http://example.org/' );
		Monkey\Functions\when( 'get_post' )->justReturn( new \WP_Post( 42 ) );
		Monkey\Functions\when( 'get_post_type' )->justReturn( 'post' );
		Monkey\Functions\when( 'get_the_title' )->justReturn( 'Long Post Title' );
		Monkey\Functions\when( 'get_permalink' )->justReturn( 'http://example.org/long-post/' );
		Monkey\Functions\when( 'get_the_category' )->justReturn( [ new \WP_Term( 7, 'News' ) ] );
		Monkey\Functions\when( 'get_term_link' )->justReturn( 'http://example.org/category/news/' );
		Monkey\Functions\when( 'get_ancestors' )->justReturn( [] );
		Monkey\Functions\when( 'get_post_meta' )->alias(
			static function ( $id ) {
				return 42 === $id ? 'Short Post' : '';
			}
		);
		Monkey\Functions\when( 'get_term_meta' )->justReturn( '' );
		Monkey\Functions\when( 'get_option' )->justReturn( [] );

		$GLOBALS['post'] = new \WP_Post( 42 ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulating the WordPress global for the single-post trail.

		$breadcrumbs = Breadcrumbs::get_instance();
		$breadcrumbs->parse_args( [] );
		$trail = $breadcrumbs->get_breadcrumb_trail( [] );

		$this->assertStringContainsString( '<span class="breadcrumb-item-name">Short Post</span>', $trail );
		$this->assertStringNotContainsString( 'Long Post Title', $trail );
		$this->assertStringContainsString( '<span class="breadcrumb-item-name">News</span>', $trail );

		$breadcrumbs->generate_breadcrumb_structured_data( $breadcrumbs );
		$data = $this->get_property( $breadcrumbs, 'structured_data' );

		$this->assertSame( 'Short Post', $data['itemListElement'][2]['item']['name'] );
	}
}
