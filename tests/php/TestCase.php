<?php
/**
 * Base test case.
 *
 * @package BreadcrumbBlock\Tests
 */

namespace BreadcrumbBlock\Tests;

use BreadcrumbBlock\Breadcrumbs;
use Brain\Monkey;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Wires Brain Monkey and resets the Breadcrumbs singleton between tests.
 */
abstract class TestCase extends PHPUnitTestCase {

	/**
	 * Optional override for apply_filters calls in the current test.
	 *
	 * @var callable|null
	 */
	private $apply_filters_override;

	/**
	 * Set up Brain Monkey and the common WordPress stubs.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Hook registration used by the Breadcrumbs constructor.
		Monkey\Functions\when( 'add_action' )->justReturn( true );
		Monkey\Functions\when( 'add_filter' )->justReturn( true );
		Monkey\Functions\when( 'do_action' )->justReturn( null );

		// Filters pass values through unless a test overrides them.
		$override = function ( $hook_name, $value = null ) {
			if ( $this->apply_filters_override ) {
				return call_user_func( $this->apply_filters_override, $hook_name, $value );
			}

			return $value;
		};
		Monkey\Functions\when( 'apply_filters' )->alias( $override );

		// Small WordPress helpers used by the class under test.
		Monkey\Functions\when( 'wp_parse_args' )->alias(
			static function ( $args, $defaults = [] ) {
				return array_merge( $defaults, (array) $args );
			}
		);
		Monkey\Functions\when( 'wp_strip_all_tags' )->alias(
			static function ( $text ) {
				// This mock implements wp_strip_all_tags(), so strip_tags is the point.
				return trim( strip_tags( $text ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- Mocking the wrapper itself.
			}
		);
		Monkey\Functions\when( 'tag_escape' )->returnArg();
		Monkey\Functions\when( 'user_trailingslashit' )->returnArg();
		Monkey\Functions\when( 'wp_unslash' )->returnArg();
		Monkey\Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Monkey\Functions\when( '_wp_specialchars' )->returnArg();

		Monkey\Functions\stubTranslationFunctions();
		Monkey\Functions\stubEscapeFunctions();

		$this->reset_singleton();
	}

	/**
	 * Reset Brain Monkey and the singleton.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$this->apply_filters_override = null;
		$this->reset_singleton();
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Stub every query conditional the plugin reads, with the given ones true.
	 *
	 * @param array $enabled Conditionals that should return true.
	 * @return void
	 */
	protected function stub_query_conditionals( array $enabled = [] ) {
		$conditionals = [
			'is_front_page',
			'is_paged',
			'is_home',
			'is_404',
			'is_attachment',
			'is_single',
			'is_page',
			'is_post_type_archive',
			'is_category',
			'is_tag',
			'is_author',
			'is_date',
			'is_tax',
			'is_search',
			'is_year',
			'is_month',
			'is_day',
			'get_query_var',
		];

		foreach ( $conditionals as $conditional ) {
			if ( in_array( $conditional, $enabled, true ) ) {
				Monkey\Functions\when( $conditional )->justReturn( true );
			} else {
				Monkey\Functions\when( $conditional )->justReturn( false );
			}
		}
	}

	/**
	 * Override the behavior of apply_filters for the current test.
	 *
	 * @param callable $callback Receives ( $hook_name, $value ) and returns the filtered value.
	 * @return void
	 */
	protected function override_apply_filters( callable $callback ) {
		$this->apply_filters_override = $callback;
	}

	/**
	 * Read a protected or private property from a Breadcrumbs instance.
	 *
	 * @param Breadcrumbs $breadcrumbs Breadcrumbs instance.
	 * @param string      $name        Property name.
	 * @return mixed
	 */
	protected function get_property( Breadcrumbs $breadcrumbs, $name ) {
		$property = new \ReflectionProperty( Breadcrumbs::class, $name );
		$property->setAccessible( true );

		return $property->getValue( $breadcrumbs );
	}

	/**
	 * Stub a single-post context: terms by id, post meta, and the editor globals.
	 *
	 * @param array  $terms_by_id Assigned WP_Term stubs keyed by term id.
	 * @param array  $meta        Post meta values keyed by meta key.
	 * @param string $post_type   The post type of the single post.
	 * @return void
	 */
	protected function stub_single_post(
		array $terms_by_id,
		array $meta = [],
		string $post_type = 'post',
		array $taxonomy_mapping = []
	): void {
		$this->stub_terms( $terms_by_id );
		$this->stub_post_meta( $meta );
		$this->stub_query_conditionals( [ 'is_single' ] );

		Monkey\Functions\when( 'get_option' )->alias(
			static function ( $name ) use ( $taxonomy_mapping ) {
				return 'breadcrumb_block_taxonomies' === $name ? $taxonomy_mapping : '';
			}
		);

		Monkey\Functions\when( 'get_post' )->alias(
			static fn () => new \WP_Post( 42 )
		);
		Monkey\Functions\when( 'home_url' )->justReturn( 'http://example.org/' );
		Monkey\Functions\when( 'get_post_type' )->justReturn( $post_type );
		Monkey\Functions\when( 'get_the_title' )->justReturn( 'Long Post Title' );
		Monkey\Functions\when( 'get_permalink' )->justReturn( 'http://example.org/long-post/' );
		Monkey\Functions\when( 'get_the_category' )->alias(
			static fn () => array_values( $terms_by_id )
		);
		Monkey\Functions\when( 'get_term_link' )->alias(
			static fn ( $term ) => 'http://example.org/term/' . (int) ( $term->term_id ?? 0 ) . '/'
		);
		Monkey\Functions\when( 'get_ancestors' )->justReturn( [] );
		Monkey\Functions\when( 'get_term_meta' )->justReturn( '' );
		Monkey\Functions\when( 'taxonomy_exists' )->justReturn( true );
	}

	/**
	 * Build the single-post trail.
	 *
	 * @param array $args Trail arguments.
	 * @return string
	 */
	protected function single_trail( array $args = [] ): string {
		$GLOBALS['post'] = new \WP_Post( 42 ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulating the WordPress global for the single-post trail.

		$breadcrumbs = Breadcrumbs::get_instance();

		return $breadcrumbs->get_breadcrumb_trail( $args );
	}

	/**
	 * Stub terms keyed by id, and has_term() against the assigned ids.
	 *
	 * @param array $terms_by_id WP_Term stubs keyed by term id.
	 * @return void
	 */
	private function stub_terms( array $terms_by_id ): void {
		Monkey\Functions\when( 'get_term' )->alias(
			static function ( $id ) use ( $terms_by_id ) {
				return $terms_by_id[ (int) $id ] ?? null;
			}
		);
		Monkey\Functions\when( 'has_term' )->alias(
			static fn ( $term ) => array_key_exists( (int) $term, $terms_by_id )
		);
	}

	/**
	 * Stub post meta values keyed by meta key.
	 *
	 * @param array $meta Meta values keyed by meta key.
	 * @return void
	 */
	private function stub_post_meta( array $meta ): void {
		Monkey\Functions\when( 'get_post_meta' )->alias(
			static function ( $id, $key = '' ) use ( $meta ) {
				return $meta[ $key ] ?? '';
			}
		);
	}

	/**
	 * Reset the Breadcrumbs singleton between tests.
	 *
	 * @return void
	 */
	private function reset_singleton() {
		$property = new \ReflectionProperty( Breadcrumbs::class, 'instance' );
		$property->setAccessible( true );
		$property->setValue( null, null );
	}
}
