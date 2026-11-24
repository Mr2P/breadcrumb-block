<?php
/**
 * Tests for the BreadcrumbList structured data.
 *
 * @package BreadcrumbBlock\Tests
 */

namespace BreadcrumbBlock\Tests;

use BreadcrumbBlock\Breadcrumbs;

/**
 * Schema.org BreadcrumbList generation and output.
 */
class StructuredDataTest extends TestCase {

	/**
	 * Structured data maps items to positions, names and ids.
	 *
	 * @return void
	 */
	public function test_structured_data_builds_breadcrumblist() {
		$breadcrumbs = Breadcrumbs::get_instance();
		$breadcrumbs->parse_args( [] );
		$breadcrumbs->add_item( 'Home', 'http://example.org/', [ 'rel' => 'home' ], [ 'type' => 'front_page' ] );
		$breadcrumbs->add_item( 'Current', 'http://example.org/current/' );
		$breadcrumbs->generate_breadcrumb_structured_data( $breadcrumbs );

		$data = $this->get_property( $breadcrumbs, 'structured_data' );

		$this->assertSame( 'http://schema.org', $data['@context'] );
		$this->assertSame( 'BreadcrumbList', $data['@type'] );
		$this->assertCount( 2, $data['itemListElement'] );
		$this->assertSame( 1, $data['itemListElement'][0]['position'] );
		$this->assertSame( 'Home', $data['itemListElement'][0]['item']['name'] );
		$this->assertSame( 'http://example.org/', $data['itemListElement'][0]['item']['@id'] );
		$this->assertSame( 2, $data['itemListElement'][1]['position'] );
		$this->assertSame( 'Current', $data['itemListElement'][1]['item']['name'] );
		$this->assertSame( 'http://example.org/current/', $data['itemListElement'][1]['item']['@id'] );
	}

	/**
	 * Names are stored with HTML entities for safe output; the schema name
	 * must show the text the user sees, so entities are decoded.
	 *
	 * @return void
	 */
	public function test_structured_data_decodes_entities_in_names() {
		$breadcrumbs = Breadcrumbs::get_instance();
		$breadcrumbs->parse_args( [] );
		$breadcrumbs->add_item( 'Search results for &ldquo;travel&rdquo;', 'http://example.org/?s=travel' );
		$breadcrumbs->add_item( 'Are Apples & Yogurt Healthy?', 'http://example.org/apples/' );
		$breadcrumbs->generate_breadcrumb_structured_data( $breadcrumbs );

		$data = $this->get_property( $breadcrumbs, 'structured_data' );

		$this->assertSame( 'Search results for “travel”', $data['itemListElement'][0]['item']['name'] );
		$this->assertSame( 'Are Apples & Yogurt Healthy?', $data['itemListElement'][1]['item']['name'] );
	}

	/**
	 * Linkless items fall back to the current request URL.
	 *
	 * @return void
	 */
	public function test_structured_data_uses_current_url_for_linkless_items() {
		$_SERVER['HTTP_HOST']   = 'example.org';
		$_SERVER['REQUEST_URI'] = '/current/';
		$_SERVER['HTTPS']       = 'on';

		$breadcrumbs = Breadcrumbs::get_instance();
		$breadcrumbs->parse_args( [] );
		$breadcrumbs->add_item( 'Current', '' );
		$breadcrumbs->generate_breadcrumb_structured_data( $breadcrumbs );

		$data = $this->get_property( $breadcrumbs, 'structured_data' );

		$this->assertSame( 'https://example.org/current/', $data['itemListElement'][0]['item']['@id'] );
	}

	/**
	 * The structured data prints as a JSON-LD script in the footer.
	 *
	 * @return void
	 */
	public function test_output_structured_data_prints_jsonld() {
		$breadcrumbs = Breadcrumbs::get_instance();
		$breadcrumbs->parse_args( [] );
		$breadcrumbs->add_item( 'Home', 'http://example.org/' );
		$breadcrumbs->generate_breadcrumb_structured_data( $breadcrumbs );

		ob_start();
		$breadcrumbs->output_structured_data();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<script type="application/ld+json">', $output );
		$this->assertStringContainsString( '"@type":"BreadcrumbList"', $output );
		$this->assertStringContainsString( '"position":1', $output );
	}
}
