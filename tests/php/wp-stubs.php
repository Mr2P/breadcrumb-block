<?php
/**
 * Minimal stubs of WordPress data objects for pure unit tests.
 *
 * phpcs:ignoreFile Generic.Files.OneObjectStructurePerFile.MultipleFound -- Test stubs live together in one file.
 *
 * @package BreadcrumbBlock\Tests
 */

/**
 * Minimal WP_Post stub.
 */
class WP_Post {

	/**
	 * Post ID.
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * Post type.
	 *
	 * @var string
	 */
	public $post_type = '';

	/**
	 * Constructor.
	 *
	 * @param int $id Post ID.
	 */
	public function __construct( $id = 0 ) {
		$this->ID = (int) $id;
	}
}

/**
 * Minimal WP_Term stub.
 */
class WP_Term {

	/**
	 * Term ID.
	 *
	 * @var int
	 */
	public $term_id = 0;

	/**
	 * Term name.
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * Constructor.
	 *
	 * @param int    $id   Term ID.
	 * @param string $name Term name.
	 */
	public function __construct( $id = 0, $name = '' ) {
		$this->term_id = (int) $id;
		$this->name    = (string) $name;
	}
}
