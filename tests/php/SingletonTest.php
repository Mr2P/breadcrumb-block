<?php
/**
 * Tests for the Breadcrumbs singleton accessor.
 *
 * @package BreadcrumbBlock\Tests
 */

namespace BreadcrumbBlock\Tests;

use BreadcrumbBlock\Breadcrumbs;

/**
 * Singleton behavior.
 */
class SingletonTest extends TestCase {

	/**
	 * The same Breadcrumbs instance is always returned.
	 *
	 * @return void
	 */
	public function test_get_instance_returns_shared_instance() {
		$first  = Breadcrumbs::get_instance();
		$second = Breadcrumbs::get_instance();

		$this->assertInstanceOf( Breadcrumbs::class, $first );
		$this->assertSame( $first, $second );
	}

	/**
	 * The instance items are cleared by reset().
	 *
	 * @return void
	 */
	public function test_reset_clears_items() {
		$breadcrumbs = Breadcrumbs::get_instance();
		$breadcrumbs->add_item( 'Home', 'http://example.org/' );
		$breadcrumbs->reset();

		$this->assertSame( [], $breadcrumbs->get_items() );
	}
}
