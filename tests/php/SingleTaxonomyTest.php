<?php
/**
 * Tests for single_taxonomy_for() and primary_term_for().
 *
 * @package BreadcrumbBlock\Tests
 */

namespace BreadcrumbBlock\Tests;

use BreadcrumbBlock\Breadcrumbs;
use Brain\Monkey;

/**
 * Taxonomy resolution and primary term meta validation.
 */
class SingleTaxonomyTest extends TestCase {

	/**
	 * A mapped post type returns the mapped taxonomy.
	 *
	 * @return void
	 */
	public function test_mapped_post_type_returns_mapped_taxonomy() {
		$this->stub_taxonomy_mapping( [ 'docs' => 'doc-section' ] );
		Monkey\Functions\when( 'get_post_type' )->justReturn( 'docs' );

		$breadcrumbs = Breadcrumbs::get_instance();
		$method      = new \ReflectionMethod( Breadcrumbs::class, 'single_taxonomy_for' );
		$method->setAccessible( true );

		$post   = $this->make_post( 'docs' );
		$result = $method->invoke( $breadcrumbs, $post );

		$this->assertSame( 'doc-section', $result );
	}

	/**
	 * An unmapped post type falls back to the type default.
	 *
	 * @return void
	 */
	public function test_unmapped_post_type_falls_back_to_default() {
		$this->stub_taxonomy_mapping( [] );
		Monkey\Functions\when( 'get_post_type' )->justReturn( 'post' );

		$breadcrumbs = Breadcrumbs::get_instance();
		$method      = new \ReflectionMethod( Breadcrumbs::class, 'single_taxonomy_for' );
		$method->setAccessible( true );

		$this->assertSame( 'category', $method->invoke( $breadcrumbs, $this->make_post() ) );
	}

	/**
	 * An unknown configured taxonomy falls back to the type default.
	 *
	 * @return void
	 */
	public function test_unknown_taxonomy_falls_back_to_default() {
		$this->stub_taxonomy_mapping( [ 'post' => 'does-not-exist' ] );
		Monkey\Functions\when( 'get_post_type' )->justReturn( 'post' );

		$breadcrumbs = Breadcrumbs::get_instance();
		$method      = new \ReflectionMethod( Breadcrumbs::class, 'single_taxonomy_for' );
		$method->setAccessible( true );

		$this->assertSame( 'category', $method->invoke( $breadcrumbs, $this->make_post() ) );
	}

	/**
	 * A product maps to product_cat.
	 *
	 * @return void
	 */
	public function test_product_maps_to_product_cat() {
		$this->stub_taxonomy_mapping( [] );
		Monkey\Functions\when( 'get_post_type' )->justReturn( 'product' );

		$breadcrumbs = Breadcrumbs::get_instance();
		$method      = new \ReflectionMethod( Breadcrumbs::class, 'single_taxonomy_for' );
		$method->setAccessible( true );

		$this->assertSame( 'product_cat', $method->invoke( $breadcrumbs, $this->make_post( 'product' ) ) );
	}

	/**
	 * A primary term meta matching the resolved taxonomy is returned.
	 *
	 * @return void
	 */
	public function test_primary_term_for_returns_matching_term() {
		$tech = new \WP_Term( 8, 'Tech', 'category' );
		$this->stub_post_meta( [ 'breadcrumb_primary_term' => 8 ] );
		Monkey\Functions\when( 'get_term' )->alias(
			static fn ( $id ) => 8 === (int) $id ? $tech : null
		);
		Monkey\Functions\when( 'has_term' )->alias(
			static fn ( $term, $taxonomy, $post ) => 8 === (int) $term
		);

		$breadcrumbs = Breadcrumbs::get_instance();
		$method      = new \ReflectionMethod( Breadcrumbs::class, 'primary_term_for' );
		$method->setAccessible( true );

		$result = $method->invoke( $breadcrumbs, $this->make_post(), 'category' );

		$this->assertSame( 8, $result->term_id );
	}

	/**
	 * A primary term from a different taxonomy is ignored.
	 *
	 * @return void
	 */
	public function test_primary_term_for_ignores_taxonomy_mismatch() {
		$this->stub_post_meta( [ 'breadcrumb_primary_term' => 7 ] );
		Monkey\Functions\when( 'get_term' )->alias(
			static fn ( $id ) => new \WP_Term( 7, 'News', 'region' )
		);
		Monkey\Functions\when( 'has_term' )->alias(
			static fn ( $term, $taxonomy, $post ) => 7 === (int) $term
		);

		$breadcrumbs = Breadcrumbs::get_instance();
		$method      = new \ReflectionMethod( Breadcrumbs::class, 'primary_term_for' );
		$method->setAccessible( true );

		$this->assertNull( $method->invoke( $breadcrumbs, $this->make_post(), 'category' ) );
	}

	/**
	 * The breadcrumb_block_get_primary_term filter can return null to disable.
	 *
	 * @return void
	 */
	public function test_primary_term_filter_can_disable() {
		$tech = new \WP_Term( 8, 'Tech', 'category' );
		$this->stub_post_meta( [ 'breadcrumb_primary_term' => 8 ] );
		Monkey\Functions\when( 'get_term' )->alias(
			static fn ( $id ) => 8 === (int) $id ? $tech : null
		);
		Monkey\Functions\when( 'has_term' )->alias(
			static fn ( $term, $taxonomy, $post ) => 8 === (int) $term
		);
		$this->override_apply_filters(
			static function ( $hook_name, $value ) {
				if ( 'breadcrumb_block_get_primary_term' === $hook_name ) {
					return null;
				}

				return $value;
			}
		);

		$breadcrumbs = Breadcrumbs::get_instance();
		$method      = new \ReflectionMethod( Breadcrumbs::class, 'primary_term_for' );
		$method->setAccessible( true );

		$this->assertNull( $method->invoke( $breadcrumbs, $this->make_post(), 'category' ) );
	}

	/**
	 * Create a WP_Post stub for the given post type.
	 *
	 * @param string $post_type Post type.
	 * @return \WP_Post
	 */
	private function make_post( string $post_type = 'post' ) {
		$post            = new \WP_Post( 42 );
		$post->post_type = $post_type;

		return $post;
	}

	/**
	 * Helper: stub the taxonomy mapping option.
	 *
	 * @param array $mapping Post type => taxonomy slug.
	 * @return void
	 */
	private function stub_taxonomy_mapping( array $mapping ): void {
		Monkey\Functions\when( 'get_option' )->alias(
			static function ( $name ) use ( $mapping ) {
				return 'breadcrumb_block_taxonomies' === $name ? $mapping : '';
			}
		);
		Monkey\Functions\when( 'taxonomy_exists' )->alias(
			static fn ( $slug ) => in_array( $slug, [ 'category', 'product_cat', 'doc-section', 'region' ], true )
		);
	}
}
