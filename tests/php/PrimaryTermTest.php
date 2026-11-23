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
 * Two-level primary term selection: per-post-type taxonomy mapping + per-item primary term.
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
			[
				7 => $news,
				8 => $tech,
			],
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
			[
				5 => $guides,
				7 => $news,
				8 => $tech,
			],
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
	 * The per-post-type taxonomy mapping uses that taxonomy for the trail.
	 *
	 * @return void
	 */
	public function test_mapped_taxonomy_uses_that_taxonomy() {
		$news   = new \WP_Term( 7, 'News', 'category' );
		$region = new \WP_Term( 9, 'Europe', 'region' );
		$this->stub_single_post(
			[ 7 => $news, 9 => $region ],
			[],
			'post',
			[ 'post' => 'region' ]
		);
		Monkey\Functions\when( 'get_the_terms' )->alias(
			static fn ( $post, $taxonomy ) => 'region' === $taxonomy ? [ $region ] : []
		);

		$trail = $this->single_trail();

		$this->assertStringContainsString( '<span class="breadcrumb-item-name">Europe</span>', $trail );
		$this->assertStringNotContainsString( 'News', $trail );
	}

	/**
	 * A mapped taxonomy without assigned terms falls back to the default.
	 *
	 * @return void
	 */
	public function test_mapped_taxonomy_without_terms_falls_back() {
		$news = new \WP_Term( 7, 'News', 'category' );
		$this->stub_single_post(
			[ 7 => $news ],
			[],
			'post',
			[ 'post' => 'region' ]
		);
		Monkey\Functions\when( 'get_the_terms' )->justReturn( [] );

		$trail = $this->single_trail();

		$this->assertStringContainsString( '<span class="breadcrumb-item-name">News</span>', $trail );
	}

	/**
	 * The primary term works within the mapped taxonomy.
	 *
	 * @return void
	 */
	public function test_primary_term_within_the_mapped_taxonomy() {
		$europe = new \WP_Term( 9, 'Europe', 'region' );
		$asia   = new \WP_Term( 10, 'Asia', 'region' );
		$this->stub_single_post(
			[
				9  => $europe,
				10 => $asia,
			],
			[ 'breadcrumb_primary_term' => 10 ],
			'post',
			[ 'post' => 'region' ]
		);
		Monkey\Functions\when( 'get_the_terms' )->alias(
			static fn ( $post, $taxonomy ) => 'region' === $taxonomy ? [ $europe, $asia ] : []
		);

		$trail = $this->single_trail();

		$this->assertStringContainsString( '<span class="breadcrumb-item-name">Asia</span>', $trail );
		$this->assertStringNotContainsString( 'Europe', $trail );
	}

	/**
	 * A primary term from another taxonomy is ignored.
	 *
	 * @return void
	 */
	public function test_stale_primary_term_ignored_when_taxonomy_mismatches() {
		$news   = new \WP_Term( 7, 'News', 'category' );
		$region = new \WP_Term( 9, 'Europe', 'region' );
		$this->stub_single_post(
			[ 7 => $news, 9 => $region ],
			[ 'breadcrumb_primary_term' => 7 ],
			'post',
			[ 'post' => 'region' ]
		);
		Monkey\Functions\when( 'get_the_terms' )->alias(
			static fn ( $post, $taxonomy ) => 'region' === $taxonomy ? [ $region ] : []
		);

		$trail = $this->single_trail();

		$this->assertStringContainsString( '<span class="breadcrumb-item-name">Europe</span>', $trail );
		$this->assertStringNotContainsString( 'News', $trail );
	}

	/**
	 * Products use the product category in auto mode.
	 *
	 * @return void
	 */
	public function test_product_auto_uses_product_category() {
		$ship = new \WP_Term( 11, 'Ships', 'product_cat' );
		$fast = new \WP_Term( 12, 'Fast Shipping', 'product_cat' );
		$this->stub_single_post(
			[ 11 => $ship, 12 => $fast ],
			[],
			'product'
		);
		Monkey\Functions\when( 'wc_get_product_terms' )->alias(
			static fn () => [ $ship, $fast ]
		);
		Monkey\Functions\when( 'wc_get_permalink_structure' )->justReturn( [] );
		Monkey\Functions\when( 'wc_get_page_id' )->justReturn( 0 );

		$trail = $this->single_trail( [], 'product' );

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

		$trail = $this->single_trail( [], 'product' );

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

	/**
	 * A flat taxonomy (post_tag) maps without an ancestor path.
	 *
	 * @return void
	 */
	public function test_tag_mapping_shows_the_tag_without_ancestors() {
		$news = new \WP_Term( 7, 'News', 'category' );
		$tag  = new \WP_Term( 13, 'Featured', 'post_tag' );
		$this->stub_single_post(
			[ 7 => $news, 13 => $tag ],
			[],
			'post',
			[ 'post' => 'post_tag' ]
		);
		Monkey\Functions\when( 'get_the_terms' )->alias(
			static fn ( $post, $taxonomy ) => 'post_tag' === $taxonomy ? [ $tag ] : []
		);

		$trail = $this->single_trail();

		$this->assertStringContainsString( '<span class="breadcrumb-item-name">Featured</span>', $trail );
		$this->assertStringNotContainsString( 'News', $trail );
	}
}
