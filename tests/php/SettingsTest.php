<?php
/**
 * Tests for the settings sanitization.
 *
 * @package BreadcrumbBlock\Tests
 */

namespace BreadcrumbBlock\Tests;

use Brain\Monkey;

/**
 * The per-post-type taxonomy mapping sanitizer.
 */
class SettingsTest extends TestCase {

	/**
	 * Valid mappings are kept - flat taxonomies like post_tag included.
	 *
	 * @return void
	 */
	public function test_sanitize_keeps_valid_mappings() {
		$category     = new \WP_Taxonomy( 'category', true, [ 'post' ] );
		$post_tag     = new \WP_Taxonomy( 'post_tag', false, [ 'post' ] );
		$doc_sections = new \WP_Taxonomy( 'doc-section', true, [ 'docs' ] );

		Monkey\Functions\when( 'get_post_types' )->justReturn(
			[
				'post' => new \WP_Post_Type( 'post', 'Posts' ),
				'docs' => new \WP_Post_Type( 'docs', 'Docs' ),
			]
		);
		Monkey\Functions\when( 'get_object_taxonomies' )->alias(
			static fn ( $post_type ) => 'docs' === $post_type ? [ $doc_sections ] : [ $category, $post_tag ]
		);
		Monkey\Functions\when( 'get_taxonomy' )->alias(
			static function ( $slug ) use ( $category, $doc_sections, $post_tag ) {
				if ( 'doc-section' === $slug ) {
					return $doc_sections;
				}

				if ( 'post_tag' === $slug ) {
					return $post_tag;
				}

				return $category;
			}
		);
		Monkey\Functions\when( 'sanitize_key' )->returnArg();
		Monkey\Functions\when( 'wp_unslash' )->returnArg();

		$clean = \BreadcrumbBlock\Settings::sanitize_taxonomies(
			[
				'taxonomies' => [
					'post' => 'post_tag',
					'docs' => 'doc-section',
				],
			]
		);

		$this->assertSame(
			[
				'post' => 'post_tag',
				'docs' => 'doc-section',
			],
			$clean
		);
	}

	/**
	 * Unattached, unknown, or non-public taxonomies are dropped.
	 *
	 * @return void
	 */
	public function test_sanitize_drops_invalid_mappings() {
		$category     = new \WP_Taxonomy( 'category', true, [ 'post' ] );
		$doc_sections = new \WP_Taxonomy( 'doc-section', true, [ 'docs' ] );

		Monkey\Functions\when( 'get_post_types' )->justReturn(
			[
				'post' => new \WP_Post_Type( 'post', 'Posts' ),
				'docs' => new \WP_Post_Type( 'docs', 'Docs' ),
			]
		);
		Monkey\Functions\when( 'get_object_taxonomies' )->alias(
			static fn ( $post_type ) => 'docs' === $post_type ? [ $doc_sections ] : [ $category ]
		);
		Monkey\Functions\when( 'get_taxonomy' )->alias(
			static function ( $slug ) use ( $category, $doc_sections ) {
				return 'doc-section' === $slug ? $doc_sections : $category;
			}
		);
		Monkey\Functions\when( 'sanitize_key' )->returnArg();
		Monkey\Functions\when( 'wp_unslash' )->returnArg();

		$clean = \BreadcrumbBlock\Settings::sanitize_taxonomies(
			[
				'taxonomies' => [
					'docs'       => 'doc-section',
					'uknowntype' => 'category',
				],
			]
		);

		$this->assertSame( [ 'docs' => 'doc-section' ], $clean );
	}

	/**
	 * A non-public taxonomy mapping is dropped.
	 *
	 * @return void
	 */
	public function test_sanitize_drops_non_public_taxonomy() {
		$category         = new \WP_Taxonomy( 'category', true, [ 'post' ] );
		$category->public = false;

		Monkey\Functions\when( 'get_post_types' )->justReturn(
			[ 'post' => new \WP_Post_Type( 'post', 'Posts' ) ]
		);
		Monkey\Functions\when( 'get_object_taxonomies' )->alias(
			static fn ( $post_type ) => [ $category ]
		);
		Monkey\Functions\when( 'get_taxonomy' )->alias(
			static fn () => $category
		);
		Monkey\Functions\when( 'sanitize_key' )->returnArg();
		Monkey\Functions\when( 'wp_unslash' )->returnArg();

		$clean = \BreadcrumbBlock\Settings::sanitize_taxonomies(
			[ 'taxonomies' => [ 'post' => 'category' ] ]
		);

		$this->assertSame( [], $clean );
	}
}
