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
	public $post_type = 'post';

	/**
	 * Constructor.
	 *
	 * @param int    $id        Post ID.
	 * @param string $post_type Post type.
	 */
	public function __construct( $id = 0, $post_type = 'post' ) {
		$this->ID        = (int) $id;
		$this->post_type = (string) $post_type;
	}
}

/**
 * Minimal WP_Post_Type stub.
 */
class WP_Post_Type {

	/**
	 * Post type slug.
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * Labels.
	 *
	 * @var object
	 */
	public $labels;

	/**
	 * Constructor.
	 *
	 * @param string $name     Post type slug.
	 * @param string $singular Singular label.
	 */
	public function __construct( $name = '', $singular = '' ) {
		$this->name   = (string) $name;
		$this->labels = (object) [
			'name'          => (string) $name,
			'singular_name' => (string) ( $singular ?: $name ),
		];
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
	 * Term taxonomy.
	 *
	 * @var string
	 */
	public $taxonomy = '';

	/**
	 * Parent term ID.
	 *
	 * @var int
	 */
	public $parent = 0;

	/**
	 * Constructor.
	 *
	 * @param int    $id       Term ID.
	 * @param string $name     Term name.
	 * @param string $taxonomy Term taxonomy.
	 */
	public function __construct( $id = 0, $name = '', $taxonomy = '' ) {
		$this->term_id  = (int) $id;
		$this->name     = (string) $name;
		$this->taxonomy = (string) $taxonomy;
	}
}

/**
 * Minimal WP_Taxonomy stub.
 */
class WP_Taxonomy {

	/**
	 * Taxonomy slug.
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * Whether the taxonomy is hierarchical.
	 *
	 * @var bool
	 */
	public $hierarchical = false;

	/**
	 * Whether the taxonomy is public.
	 *
	 * @var bool
	 */
	public $public = true;

	/**
	 * Post types the taxonomy is attached to.
	 *
	 * @var array
	 */
	public $object_type = [];

	/**
	 * Constructor.
	 *
	 * @param string $name        Taxonomy slug.
	 * @param bool   $hierarchical Whether the taxonomy is hierarchical.
	 * @param array  $object_type Post types the taxonomy is attached to.
	 */
	public function __construct( $name = '', $hierarchical = true, $object_type = [] ) {
		$this->name         = (string) $name;
		$this->hierarchical = (bool) $hierarchical;
		$this->object_type  = (array) $object_type;
		$this->public       = true;
	}
}
