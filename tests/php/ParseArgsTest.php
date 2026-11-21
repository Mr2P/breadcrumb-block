<?php
/**
 * Tests for Breadcrumbs::parse_args().
 *
 * @package BreadcrumbBlock\Tests
 */

namespace BreadcrumbBlock\Tests;

use BreadcrumbBlock\Breadcrumbs;

/**
 * Trail arguments and label defaults.
 */
class ParseArgsTest extends TestCase {

	/**
	 * All trail argument defaults are applied.
	 *
	 * @return void
	 */
	public function test_parse_args_applies_defaults() {
		$args = Breadcrumbs::get_instance()->parse_args( [] );

		$this->assertSame( 'nav', $args['container'] );
		$this->assertSame( '', $args['before'] );
		$this->assertSame( '', $args['after'] );
		$this->assertSame( 'ol', $args['list_tag'] );
		$this->assertSame( 'li', $args['item_tag'] );
		$this->assertSame( '', $args['separator'] );
		$this->assertSame( 'Breadcrumbs', $args['aria_label'] );
		$this->assertFalse( $args['echo'] );
	}

	/**
	 * All default labels are filled.
	 *
	 * @return void
	 */
	public function test_parse_args_fills_default_labels() {
		$args   = Breadcrumbs::get_instance()->parse_args( [] );
		$labels = $args['labels'];

		$this->assertSame( 'Home', $labels['home'] );
		$this->assertSame( 'Page not found', $labels['error404'] );
		$this->assertSame( 'Search results for &ldquo;%s&rdquo;', $labels['searchResult'] );
		$this->assertSame( 'Posts tagged &ldquo;%s&rdquo;', $labels['postsTagged'] );
		$this->assertSame( 'Author: %s', $labels['author'] );
		$this->assertSame( 'Page %d', $labels['page'] );
		$this->assertSame( 'Products tagged &ldquo;%s&rdquo;', $labels['productsTagged'] );
	}

	/**
	 * Custom labels are merged over the defaults.
	 *
	 * @return void
	 */
	public function test_parse_args_merges_custom_labels() {
		$args = Breadcrumbs::get_instance()->parse_args(
			[
				'separator' => '>',
				'labels'    => [ 'home' => 'Start' ],
			]
		);

		$this->assertSame( '>', $args['separator'] );
		$this->assertSame( 'Start', $args['labels']['home'] );
		$this->assertSame( 'Page not found', $args['labels']['error404'] );
	}
}
