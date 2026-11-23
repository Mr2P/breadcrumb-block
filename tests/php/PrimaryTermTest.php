<?php
/**
 * Tests for the primary term selection feature.
 *
 * @package BreadcrumbBlock\Tests
 */

namespace BreadcrumbBlock\Tests;

use BreadcrumbBlock\Breadcrumbs;
use Brain\Monkey;

/**
 * Two-level primary term selection: block taxonomy setting + per-item primary term.
 */
class PrimaryTermTest extends TestCase {

	/**
	 * The primary term meta moves the trail to that term.
	 *
	 * @return void
	 */
	public function test_primary_term_moves_the_trail() {
		$news = new \WP_Term( 7, 'News', 'category' );
		$tech = new \WP_Term( 8, 'Tech', 'category' );
		$this->stub_single_post(
			[ 7 => $news, 8 => $tech ],
			[ 'breadcrumb_primary_term' => 8 ]
		);

		$trail = $this->single_trail();

		$this->assertStringContainsString( '<span class="breadcrumb-item-name">Tech</span>', $trail );
		$this->assertStringNotContainsString( 'News', $trail );
	}

	/**
	 * The primary term path includes its ancestors.
	 *
	 * @return void
	 */
	public function test_primary_term_includes_ancestors() {
		$guides = new \WP_Term( 5, 'Guides', 'category' );
		$news   = new \WP_Term( 7, 'News', 'category' );
		$tech   = new \WP_Term( 8, 'Tech', 'category' );
		$this->stub_single_post(
			[ 5 => $guides, 7 => $news, 8 => $tech ],
			[ 'breadcrumb_primary_term' => 8 ]
		);
		Monkey\Functions\when( 'get_ancestors' )->alias(
			static fn ( $id, $taxonomy ) => 8 === (int) $id ? [ 5 ] : []
		);

		$trail = $this->single_trail();

		$this->assertStringContainsString( '<span class="breadcrumb-item-name">Guides</span>', $trail );
		$this->assertStringContainsString( '<span class="breadcrumb-item-name">Tech</span>', $trail );
		$this->assertTrue( strpos( $trail, 'Guides' ) < strpos( $trail, 'Tech' ) );
	}

	/**
	 * A primary term that is not assigned to the post is ignored.
	 *
	 * @return void
	 */
	public function test_primary_term_ignored_when_not_assigned() {
		$news = new \WP_Term( 7, 'News', 'category' );
		$this->stub_single_post(
			[ 7 => $news ],
			[ 'breadcrumb_primary_term' => 99 ]
		);

		$trail = $this->single_trail();

		$this->assertStringContainsString( '<span class="breadcrumb-item-name">News</span>', $trail );
	}

	/**
	 * The block taxonomy setting uses that taxonomy for the trail.
	 *
	 * @return void
	 */
	public function test_block_taxonomy_setting_uses_that_taxonomy() {
		$news   = new \WP_Term( 7, 'News', 'category' );
		$region = new \WP_Term( 9, 'Europe', 'region' );
		$this->stub_single_post( [ 7 => $news, 9 => $region ] );
		Monkey\Functions\when( 'get_the_terms' )->alias(
			static fn ( $post, $taxonomy ) => 'region' === $taxonomy ? [ $region ] : []
		);

		$trail = $this->single_trail( [ 'taxonomy' => 'region' ] );

		$this->assertStringContainsString( '<span class="breadcrumb-item-name">Europe</span>', $trail );
		$this->assertStringNotContainsString( 'News', $trail );
	}

	/**
	 * A configured taxonomy without assigned terms falls back to the default.
	 *
	 * @return void
	 */
	public function test_configured_taxonomy_without_terms_falls_back() {
		$news = new \WP_Term( 7, 'News', 'category' );
		$this->stub_single_post( [ 7 => $news ] );
		Monkey\Functions\when( 'get_the_terms' )->justReturn( [] );

		$trail = $this->single_trail( [ 'taxonomy' => 'region' ] );

		$this->assertStringContainsString( '<span class="breadcrumb-item-name">News</span>', $trail );
	}

	/**
	 * The per-item primary term overrides the block taxonomy setting.
	 *
	 * @return void
	 */
	public function test_primary_term_overrides_the_block_taxonomy() {
		$news   = new \WP_Term( 7, 'News', 'category' );
		$region = new \WP_Term( 9, 'Europe', 'region' );
		$this->stub_single_post(
			[ 7 => $news, 9 => $region ],
			[ 'breadcrumb_primary_term' => 7 ]
		);
		Monkey\Functions\when( 'get_the_terms' )->alias(
			static fn ( $post, $taxonomy ) => 'region' === $taxonomy ? [ $region ] : []
		);

		$trail = $this->single_trail( [ 'taxonomy' => 'region' ] );

		$this->assertStringContainsString( '<span class="breadcrumb-item-name">News</span>', $trail );
		$this->assertStringNotContainsString( 'Europe', $trail );
	}

	/**
	 * Products use the product category in auto mode.
	 *
	 * @return void
	 */
	public function test_product_auto_uses_product_category() {
		$ship = new \WP_Term( 11, 'Ships', 'product_cat' );
		$fast = new \WP_Term( 12, 'Fast Shipping', 'product_cat' );
		$this->stub_single_post( [ 11 => $ship, 12 => $fast ], [], 'product' );
		Monkey\Functions\when( 'wc_get_product_terms' )->alias(
			static fn () => [ $ship, $fast ]
		);
		Monkey\Functions\when( 'wc_get_permalink_structure' )->justReturn( [] );
		Monkey\Functions\when( 'wc_get_page_id' )->justReturn( 0 );

		$trail = $this->single_trail();

		$this->assertStringContainsString( '<span class="breadcrumb-item-name">Ships</span>', $trail );
	}

	/**
	 * The primary term works for products too.
	 *
	 * @return void
	 */
	public function test_primary_term_for_products() {
		$ship = new \WP_Term( 11, 'Ships', 'product_cat' );
		$fast = new \WP_Term( 12, 'Fast Shipping', 'product_cat' );
		$this->stub_single_post(
			[ 11 => $ship, 12 => $fast ],
			[ 'breadcrumb_primary_term' => 12 ],
			'product'
		);
		Monkey\Functions\when( 'wc_get_product_terms' )->alias(
			static fn () => [ $ship, $fast ]
		);
		Monkey\Functions\when( 'wc_get_permalink_structure' )->justReturn( [] );
		Monkey\Functions\when( 'wc_get_page_id' )->justReturn( 0 );

		$trail = $this->single_trail();

		$this->assertStringContainsString( '<span class="breadcrumb-item-name">Fast Shipping</span>', $trail );
		$this->assertStringNotContainsString( 'Ships', $trail );
	}

	/**
	 * The primary term filter can disable the primary selection.
	 *
	 * @return void
	 */
	public function test_primary_term_filter_can_disable() {
		$news = new \WP_Term( 7, 'News', 'category' );
		$tech = new \WP_Term( 8, 'Tech', 'category' );
		$this->stub_single_post(
			[ 7 => $news, 8 => $tech ],
			[ 'breadcrumb_primary_term' => 8 ]
		);
		$this->override_apply_filters(
			static function ( $hook_name, $value ) {
				if ( 'breadcrumb_block_get_primary_term' === $hook_name ) {
					return null;
				}

				return $value;
			}
		);

		$trail = $this->single_trail();

		$this->assertStringContainsString( '<span class="breadcrumb-item-name">News</span>', $trail );
	}
}
