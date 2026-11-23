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
	 * Valid hierarchical mappings are kept.
	 *
	 * @return void
	 */
	public function test_sanitize_keeps_valid_mappings() {
		$category     = new \WP_Taxonomy( 'category', true, [ 'post' ] );
		$doc_sections = new \WP_Taxonomy( 'doc-section', true, [ 'docs' ] );

		Monkey\Functions\when( 'get_post_types' )->justReturn( [ 'post' => 'post', 'docs' => 'docs' ] );
		Monkey\Functions\when( 'get_object_taxonomies' )->alias(
			static fn ( $post_type ) => 'docs' === $post_type ? [ $doc_sections ] : [ $category ]
		);
		Monkey\Functions\when( 'get_taxonomy' )->alias(
			static function ( $slug ) use ( $category, $doc_sections ) {
				return 'doc-section' === $slug ? $doc_sections : $category;
			}
		);

		$clean = \BreadcrumbBlock\Settings::sanitize_taxonomies(
			[
				'taxonomies' => [
					'post' => 'category',
					'docs' => 'doc-section',
				],
			]
		);

		$this->assertSame( [ 'post' => 'category', 'docs' => 'doc-section' ], $clean );
	}

	/**
	 * Non-hierarchical, unattached, or unknown taxonomies are dropped.
	 *
	 * @return void
	 */
	public function test_sanitize_drops_invalid_mappings() {
		$post_tag = new \WP_Taxonomy( 'post_tag', false, [ 'post' ] );
		$category = new \WP_Taxonomy( 'category', true, [ 'post' ] );

		Monkey\Functions\when( 'get_post_types' )->justReturn( [ 'post' => 'post', 'docs' => 'docs' ] );
		Monkey\Functions\when( 'get_object_taxonomies' )->alias(
			static fn ( $post_type ) => 'docs' === $post_type ? [] : [ $category, $post_tag ]
		);
		Monkey\Functions\when( 'get_taxonomy' )->alias(
			static function ( $slug ) use ( $category, $post_tag ) {
				return 'category' === $slug ? $category : $post_tag;
			}
		);

		$clean = \BreadcrumbBlock\Settings::sanitize_taxonomies(
			[
				'taxonomies' => [
					'post'       => 'post_tag',
					'docs'       => 'doc-section',
					'uknowntype' => 'category',
				],
			]
		);

		$this->assertSame( [], $clean );
	}
}
