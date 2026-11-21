<?php
/**
 * Tests for the trail markup of Breadcrumbs::get_breadcrumb_trail().
 *
 * @package BreadcrumbBlock\Tests
 */

namespace BreadcrumbBlock\Tests;

use BreadcrumbBlock\Breadcrumbs;
use Brain\Monkey;

/**
 * Rendered trail HTML for the basic query types.
 */
class TrailMarkupTest extends TestCase {

	/**
	 * Full markup for a two item blog home trail with a separator.
	 *
	 * @return void
	 */
	public function test_trail_markup_with_separator() {
		$this->stub_query_conditionals( [ 'is_home' ] );
		Monkey\Functions\when( 'home_url' )->justReturn( 'http://example.org/' );
		Monkey\Functions\when( 'single_post_title' )->alias(
			static function () {
				return 'Blog';
			}
		);

		$markup = Breadcrumbs::get_instance()->get_breadcrumb_trail( [ 'separator' => '/' ] );

		$expected = '<nav role="navigation" aria-label="Breadcrumbs" class="breadcrumb">'
			. '<ol class="breadcrumb-items">'
			. '<li class="breadcrumb-item breadcrumb-item--parent breadcrumb-item--home">'
			. '<a href="http://example.org/"><span class="breadcrumb-item-name">Home</span></a>'
			. '<span class="sep">/</span></li>'
			. '<li class="breadcrumb-item breadcrumb-item--current" aria-current="page"><span class="breadcrumb-item-name">Blog</span></li>'
			. '</ol></nav>';

		$this->assertSame( $expected, $markup );
	}

	/**
	 * Date trails mark current, parent and home items and never link the last item.
	 *
	 * @return void
	 */
	public function test_date_trail_classes_and_links() {
		$this->stub_query_conditionals( [ 'is_date', 'is_day' ] );
		Monkey\Functions\when( 'home_url' )->justReturn( 'http://example.org/' );
		Monkey\Functions\when( 'get_the_time' )->alias(
			static function ( $format ) {
				$map = [
					'Y' => '2024',
					'F' => 'May',
					'd' => '01',
				];

				return $map[ $format ] ?? '';
			}
		);
		Monkey\Functions\when( 'get_year_link' )->justReturn( 'http://example.org/2024/' );
		Monkey\Functions\when( 'get_month_link' )->justReturn( 'http://example.org/2024/05/' );

		$markup = Breadcrumbs::get_instance()->get_breadcrumb_trail( [] );

		$expected = '<nav role="navigation" aria-label="Breadcrumbs" class="breadcrumb">'
			. '<ol class="breadcrumb-items">'
			. '<li class="breadcrumb-item breadcrumb-item--home">'
			. '<a href="http://example.org/"><span class="breadcrumb-item-name">Home</span></a></li>'
			. '<li class="breadcrumb-item"><a href="http://example.org/2024/"><span class="breadcrumb-item-name">2024</span></a></li>'
			. '<li class="breadcrumb-item breadcrumb-item--parent"><a href="http://example.org/2024/05/"><span class="breadcrumb-item-name">May</span></a></li>'
			. '<li class="breadcrumb-item breadcrumb-item--current"><span class="breadcrumb-item-name">01</span></li>'
			. '</ol></nav>';

		$this->assertSame( $expected, $markup );
	}

	/**
	 * Trail arguments change container and list tags.
	 *
	 * @return void
	 */
	public function test_trail_args_change_container_and_list_tags() {
		$this->stub_query_conditionals( [ 'is_home' ] );
		Monkey\Functions\when( 'home_url' )->justReturn( 'http://example.org/' );
		Monkey\Functions\when( 'single_post_title' )->alias(
			static function () {
				return 'Blog';
			}
		);

		$markup = Breadcrumbs::get_instance()->get_breadcrumb_trail(
			[
				'container' => 'div',
				'list_tag'  => 'ul',
			]
		);

		$this->assertStringContainsString( '<div role="navigation" aria-label="Breadcrumbs" class="breadcrumb">', $markup );
		$this->assertStringContainsString( '<ul class="breadcrumb-items">', $markup );
		$this->assertStringContainsString( '</ul></div>', $markup );
	}
}
