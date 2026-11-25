<?php
/**
 * Breadcrumb primary term: meta registration.
 *
 * @package   BreadcrumbBlock
 * @author    Phi Phan <mrphipv@gmail.com>
 * @copyright Copyright (c) 2022, Phi Phan
 */

namespace BreadcrumbBlock;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( Primary_Term::class ) ) {
	/**
	 * Registers the primary term meta.
	 */
	class Primary_Term {
		/**
		 * The meta key used to store the primary term id on posts.
		 *
		 * @var string
		 */
		public const META_KEY = '_breadcrumb_primary_term';

		/**
		 * Register meta.
		 *
		 * @return void
		 */
		public static function init(): void {
			// Post meta for every public post type.
			foreach ( get_post_types( [ 'public' => true ] ) as $post_type ) {
				$post_type_object = get_post_type_object( $post_type );

				register_post_meta(
					$post_type,
					self::META_KEY,
					[
						'type'              => 'integer',
						'single'            => true,
						'default'           => 0,
						'sanitize_callback' => 'absint',
						'show_in_rest'      => true,
						'auth_callback'     => static fn (): bool => (bool) ( $post_type_object && current_user_can( $post_type_object->cap->edit_posts ) ),
					]
				);
			}
		}
	}
}
