<?php
/**
 * The Breadcrumbs
 *
 * @package   BreadcrumbBlock
 * @author    Phi Phan <mrphipv@gmail.com>
 * @copyright Copyright (c) 2022, Phi Phan
 */

namespace BreadcrumbBlock;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( Breadcrumbs::class ) ) {
	/**
	 * The Breadcrumbs class.
	 * Adapted from WC_Breadcrumb and https://github.com/justintadlock/breadcrumb-trail
	 */
	class Breadcrumbs {
		/**
		 * All items belonging to the current breadcrumb trail.
		 *
		 * @var array
		 */
		protected array $items = [];

		/**
		 * Arguments to build the breadcrumb trail.
		 *
		 * @var array
		 */
		protected array $args = [];

		/**
		 * Structured data (BreadcrumbList markup array).
		 *
		 * @var array<string, mixed>|null
		 */
		protected ?array $structured_data = null;

		/**
		 * Plugin instance
		 *
		 * @var Breadcrumbs|null
		 */
		private static ?Breadcrumbs $instance = null;

		/**
		 * The constructor
		 */
		private function __construct() {
			// Generate structured data.
			add_action( 'breadcrumb_block_render_breadcrumb_trail', [ $this, 'generate_breadcrumb_structured_data' ], 10 );

			// Output the structured data to the front end.
			add_action( 'wp_footer', [ $this, 'output_structured_data' ] );
		}

		/**
		 * Initialize the instance.
		 *
		 * @return Breadcrumbs
		 */
		public static function get_instance(): Breadcrumbs {
			if ( null === self::$instance ) {
				self::$instance = new Breadcrumbs();
			}

			return self::$instance;
		}

		/**
		 * Add an item.
		 *
		 * @param string $name    Item name.
		 * @param string $link    Item link.
		 * @param array  $attrs   Extra HTML attributes for the item.
		 * @param array  $context Context of the item: type and object.
		 */
		public function add_item( string $name, string $link = '', array $attrs = [], array $context = [] ): void {
			// Short title from the item meta, when set.
			$name = $this->apply_short_title( wp_strip_all_tags( $name ), $context );

			// Allow changing the item.
			$item = apply_filters( 'breadcrumb_block_get_item', [ $name, $link, $attrs ], $context, $this );

			// Add the ability to remove an item from the crumbs.
			if ( $item ) {
				$this->items[] = $item;
			}
		}

		/**
		 * Replace the item name with its short title meta, when set.
		 *
		 * @param string $name    Item name.
		 * @param array  $context Context of the item: type and object.
		 * @return string
		 */
		protected function apply_short_title( string $name, array $context ): string {
			$object = $context['object'] ?? null;

			$short_title = '';
			if ( $object instanceof \WP_Post ) {
				$short_title = (string) get_post_meta( $object->ID, Short_Title::META_KEY, true );
			} elseif ( $object instanceof \WP_Term ) {
				$short_title = (string) get_term_meta( $object->term_id, Short_Title::META_KEY, true );
			}

			$short_title = (string) apply_filters( 'breadcrumb_block_get_short_title', $short_title, $object );

			return '' !== $short_title ? $short_title : $name;
		}

		/**
		 * Build the breadcrumb items
		 *
		 * @return array
		 */
		public function generate(): array {
			$conditionals = [
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
			];

			$is_woocommerce_activated = $this->is_woocommerce_activated();
			if ( $is_woocommerce_activated ) {
				array_splice(
					$conditionals,
					4,
					0,
					[
						'is_product_category',
						'is_product_tag',
						'is_shop',
					]
				);
			}

			$skip_front_page_trail = ! is_front_page() && ( ! $is_woocommerce_activated || ! ( is_post_type_archive() && (int) get_option( 'page_on_front' ) === wc_get_page_id( 'shop' ) ) );

			if ( $skip_front_page_trail || is_paged() ) {
				$this->add_crumbs_front_page();

				foreach ( $conditionals as $conditional ) {
					if ( $conditional() ) {
						$this->{ 'add_crumbs_' . substr( $conditional, 3 ) }();
						break;
					}
				}

				$this->search_trail();
				$this->paged_trail();
			}

			// Filter the items array.
			$this->items = apply_filters( 'breadcrumb_block_get_items', $this->items, $this );

			return $this->get_items();
		}

		/**
		 * Get the breadcrumb items.
		 *
		 * @return array
		 */
		public function get_items(): array {
			return $this->items;
		}

		/**
		 * Reset the breadcrumb items.
		 *
		 * @return void
		 */
		public function reset(): void {
			$this->items = [];
		}

		/**
		 * Parse the arguments with the defaults.
		 *
		 * @param array $args Trail arguments to parse.
		 * @return array
		 */
		public function parse_args( array $args ): array {
			// Parse args.
			$this->args = wp_parse_args(
				$args,
				[
					'container'   => 'nav',
					'before'      => '',
					'after'       => '',
					'list_tag'    => 'ol',
					'item_tag'    => 'li',
					'item_before' => '',
					'item_after'  => '',
					'separator'   => '',
					'aria_label'  => esc_attr_x( 'Breadcrumbs', 'breadcrumb aria label', 'breadcrumb-block' ),
					'labels'      => [],
					'echo'        => false,
				]
			);

			// Parse labels.
			$this->args['labels'] = $this->parse_labels( $this->args['labels'] );

			return apply_filters( 'breadcrumb_block_get_args', $this->args );
		}

		/**
		 * Parse labels
		 *
		 * @param array $labels Labels to merge with the defaults.
		 * @return array
		 */
		private function parse_labels( array $labels ): array {
			$default_labels = [
				'home'           => __( 'Home', 'breadcrumb-block' ),
				'error404'       => __( 'Page not found', 'breadcrumb-block' ),
				/* translators: %s: search term */
				'searchResult'   => __( 'Search results for &ldquo;%s&rdquo;', 'breadcrumb-block' ),
				/* translators: %s: tag name */
				'postsTagged'    => __( 'Posts tagged &ldquo;%s&rdquo;', 'breadcrumb-block' ),
				/* translators: %s: author name */
				'author'         => __( 'Author: %s', 'breadcrumb-block' ),
				/* translators: %d: page number */
				'page'           => __( 'Page %d', 'breadcrumb-block' ),
				/* translators: %s: product tag */
				'productsTagged' => __( 'Products tagged &ldquo;%s&rdquo;', 'breadcrumb-block' ),
			];

			foreach ( $default_labels as $key => $default_label ) {
				if ( empty( $labels[ $key ] ) ) {
					$labels[ $key ] = $default_label;
				}
			}

			return $labels;
		}

		/**
		 * Get the breadcrumb trail.
		 *
		 * @param array $args Optional. Trail arguments.
		 * @return string The breadcrumb trail markup.
		 */
		public function get_breadcrumb_trail( array $args = [] ): string {
			// Parse args.
			$args = $this->parse_args( $args );

			// Clear old items.
			$this->reset();

			// Generate the breadcrumbs.
			$this->generate();

			$breadcrumb = '';
			$item_count = count( $this->items );

			// Build the breadcrumb trail if has some items.
			if ( 0 < $item_count ) {
				// Before.
				$breadcrumb .= $args['before'];

				// Open the unordered list.
				$breadcrumb .= sprintf( '<%s class="breadcrumb-items">', tag_escape( $args['list_tag'] ) );

				// Loop through the items and add them to the list.
				foreach ( $this->items as $key => $crumb ) {
					$breadcrumb .= $this->get_breadcrumb_item( $crumb, $key, $item_count, $args );
				}

				// Close the unordered list.
				$breadcrumb .= sprintf( '</%s>', tag_escape( $args['list_tag'] ) );

				// After.
				$breadcrumb .= $args['after'];

				// Wrap the breadcrumb trail.
				$breadcrumb = sprintf(
					'<%1$s role="navigation" aria-label="%2$s" class="breadcrumb">%3$s</%1$s>',
					tag_escape( $args['container'] ),
					esc_attr( $args['aria_label'] ),
					$breadcrumb
				);
			}

			// Allow the ability to remove the shortcodes from the breadcrumb.
			if ( apply_filters( 'breadcrumb_block_strip_shortcodes', false, $this ) ) {
				$breadcrumb = strip_shortcodes( $breadcrumb );
			}

			// Allow third-party to filter the breadcrumb trail HTML.
			$breadcrumb = apply_filters( 'breadcrumb_block_get_breadcrumb_trail', $breadcrumb, $args, $this );

			// Run hooks.
			do_action( 'breadcrumb_block_render_breadcrumb_trail', $this );

			if ( false === $args['echo'] ) {
				return $breadcrumb;
			}

			echo $breadcrumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return '';
		}

		/**
		 * Build the markup of a single breadcrumb item.
		 *
		 * @param array $crumb      The crumb: [ name, link, attrs ].
		 * @param int   $key        Zero-based item index.
		 * @param int   $item_count Total number of items in the trail.
		 * @param array $args       Parsed trail arguments.
		 * @return string The list item markup.
		 */
		private function get_breadcrumb_item( array $crumb, int $key, int $item_count, array $args ): string {
			// Item position.
			$item_position = $key + 1;

			$item = '';

			if ( ! empty( $crumb[1] ) && $item_count !== $item_position ) {
				$item .= sprintf( '<a href="%1$s"><span class="breadcrumb-item-name">%2$s</span></a>', esc_url( $crumb[1] ), esc_html( $crumb[0] ) );
			} else {
				$item .= sprintf( '<span class="breadcrumb-item-name">%1$s</span>', esc_html( $crumb[0] ) );
			}

			if ( $item_count !== $item_position && $args['separator'] ) {
				$item .= sprintf( '<span class="sep">%s</span>', $args['separator'] );
			}

			// Add list item classes.
			$item_classes = [ 'breadcrumb-item' ];

			if ( $item_count === $item_position ) {
				$item_classes[] = 'breadcrumb-item--current';
			} elseif ( $item_count - 1 === $item_position ) {
				$item_classes[] = 'breadcrumb-item--parent';
			}

			// Create list item attributes.
			$attributes = '';

			if ( ! empty( $crumb[2] ) ) {
				$attrs = $crumb[2];
				if ( 'home' === ( $attrs['rel'] ?? '' ) ) {
					$item_classes[] = 'breadcrumb-item--home';
					unset( $attrs['rel'] );
				}

				$attributes .= array_reduce(
					array_keys( $attrs ),
					static fn ( string $carry, string $attr_key ): string => $carry . ' ' . $attr_key . '="' . htmlspecialchars( $attrs[ $attr_key ] ) . '"',
					''
				);
			}

			$attributes = sprintf( 'class="%1$s"%2$s', implode( ' ', $item_classes ), $attributes );

			// Build the list item.
			return sprintf( '<%1$s %2$s>%3$s</%1$s>', tag_escape( $args['item_tag'] ), $attributes, $item );
		}

		/**
		 * Front page trail.
		 */
		protected function add_crumbs_front_page(): void {
			$this->add_item( apply_filters( 'breadcrumb_block_home_text', $this->args['labels']['home'] ), esc_url( user_trailingslashit( apply_filters( 'breadcrumb_block_home_url', home_url() ) ) ), [ 'rel' => 'home' ], [ 'type' => 'front_page' ] );
		}

		/**
		 * Is home trail.
		 */
		protected function add_crumbs_home(): void {
			$title = (string) single_post_title( '', false );

			// The block can render before the loop (block themes with the block in a
			// template part), and paged contexts have no queried post: single_post_title()
			// is empty there. Fall back to the posts page title, then the site title.
			if ( '' === $title ) {
				$page_for_posts = (int) get_option( 'page_for_posts' );
				$title          = $page_for_posts
				? (string) get_post_field( 'post_title', $page_for_posts )
				: (string) get_bloginfo( 'name' );
			}

			$this->add_item( $title, '', [ 'aria-current' => 'page' ], [ 'type' => 'home' ] );
		}

		/**
		 * 404 trail.
		 */
		protected function add_crumbs_404(): void {
			$this->add_item( $this->args['labels']['error404'], '', [ 'aria-current' => 'page' ], [ 'type' => '404' ] );
		}

		/**
		 * Attachment trail.
		 */
		protected function add_crumbs_attachment(): void {
			global $post;

			$this->add_crumbs_single( $post->post_parent, get_permalink( $post->post_parent ) );
			$this->add_item(
				get_the_title(),
				get_permalink(),
				[ 'aria-current' => 'page' ],
				[
					'type'   => 'attachment',
					'object' => $post,
				]
			);
		}

		/**
		 * Single post trail.
		 *
		 * @param int    $post_id   Post ID.
		 * @param string $permalink Post permalink.
		 */
		protected function add_crumbs_single( int $post_id = 0, string $permalink = '' ): void {
			if ( ! $post_id ) {
				global $post;
			} else {
				$post = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}

			if ( ! $permalink ) {
				$permalink = get_permalink( $post );
			}

			// Allow adding items at the start of the single item.
			do_action( 'breadcrumb_block_single_prepend', $post, $this );

			$is_product = 'product' === get_post_type( $post );

			if ( $is_product ) {
				$this->prepend_shop_page();
			}

			// The main term: the per-item primary term wins, otherwise the block taxonomy (or the type default).
			$main_term = $this->get_single_main_term( $post );

			if ( $main_term ) {
				$this->term_ancestors( $main_term->term_id, $main_term->taxonomy );
				$this->add_item(
					$main_term->name,
					get_term_link( $main_term ),
					[],
					[
						'type'   => 'term',
						'object' => $main_term,
					]
				);
			} elseif ( ! $is_product && 'post' !== get_post_type( $post ) ) {
				$post_type = get_post_type_object( get_post_type( $post ) );

				// Allow the ability to remove the post type name from the breadcrumb.
				if ( apply_filters( 'breadcrumb_block_add_post_type_name', true, $post, $this ) ) {
					// If the post doesn't have a parent, get its hierarchy based off the post type.
					if ( ! empty( $post_type->has_archive ) ) {
						$this->add_item(
							$post_type->labels->name,
							get_post_type_archive_link( get_post_type( $post ) ),
							[],
							[
								'type'   => 'post_type_archive',
								'object' => $post_type,
							]
						);
					}
				}

				// If the post has a parent, follow the parent trail.
				$this->add_parent_crumbs( $post->post_parent );
			}

			// Allow adding items right before the current item.
			// General hook.
			do_action( 'breadcrumb_block_single', $post, $this );

			// With post type name.
			do_action( 'breadcrumb_block_single_' . $post->post_type, $post, $this );

			$this->add_item(
				get_the_title( $post ),
				$permalink,
				[ 'aria-current' => 'page' ],
				[
					'type'   => 'single',
					'object' => $post,
				]
			);
		}

		/**
		 * Resolve the main term for a single item.
		 *
		 * The per-item primary term wins when set; otherwise the block-configured
		 * taxonomy, falling back to the type default (product category, category).
		 *
		 * @param \WP_Post $post The single post.
		 * @return \WP_Term|null
		 */
		protected function get_single_main_term( \WP_Post $post ): ?\WP_Term {
			// The taxonomy shown for single items: the block setting, or the type default.
			$taxonomy = $this->single_taxonomy_for( $post );

			if ( '' === $taxonomy ) {
				return null;
			}

			// The per-item primary term, when selected and valid for the taxonomy.
			$primary = $this->primary_term_for( $post, $taxonomy );

			if ( $primary ) {
				return $primary;
			}

			return $this->default_main_term( $post, $taxonomy );
		}

		/**
		 * The taxonomy shown for single items: the per-post-type mapping from the
		 * settings, or the type default.
		 *
		 * @param \WP_Post $post The single post.
		 * @return string
		 */
		protected function single_taxonomy_for( \WP_Post $post ): string {
			$mappings   = (array) get_option( Settings::OPTION_KEY, [] );
			$configured = $mappings[ $post->post_type ] ?? '';

			if ( '' !== $configured && taxonomy_exists( $configured ) ) {
				return $configured;
			}

			if ( 'product' === get_post_type( $post ) ) {
				return 'product_cat';
			}

			if ( 'post' === get_post_type( $post ) ) {
				return 'category';
			}

			return '';
		}

		/**
		 * Resolve the default main term from the block taxonomy or the type default.
		 *
		 * @param \WP_Post $post     The single post.
		 * @param string   $taxonomy The taxonomy shown for single items.
		 * @return \WP_Term|null
		 */
		protected function default_main_term( \WP_Post $post, string $taxonomy ): ?\WP_Term {
			// The type default sources preserve the legacy ordering.
			if ( 'product' === get_post_type( $post ) && 'product_cat' === $taxonomy ) {
				return $this->main_term_from_terms(
					wc_get_product_terms(
						$post->ID,
						'product_cat',
						apply_filters(
							'breadcrumb_block_product_terms_args',
							[
								'orderby' => 'parent',
								'order'   => 'DESC',
							]
						)
					),
					'product_cat'
				);
			}

			if ( 'post' === get_post_type( $post ) && 'category' === $taxonomy ) {
				return $this->main_term_from_terms( get_the_category( $post ), 'category' );
			}

			// An explicitly configured taxonomy: the assigned terms of that taxonomy.
			$main_term = $this->main_term_from_terms( get_the_terms( $post, $taxonomy ), $taxonomy );

			if ( $main_term ) {
				return $main_term;
			}

			// The configured taxonomy has no assigned terms: fall back to the type default.
			if ( 'product' === get_post_type( $post ) ) {
				return $this->main_term_from_terms(
					wc_get_product_terms(
						$post->ID,
						'product_cat',
						apply_filters(
							'breadcrumb_block_product_terms_args',
							[
								'orderby' => 'parent',
								'order'   => 'DESC',
							]
						)
					),
					'product_cat'
				);
			}

			if ( 'post' === get_post_type( $post ) ) {
				return $this->main_term_from_terms( get_the_category( $post ), 'category' );
			}

			return null;
		}

		/**
		 * Pick the main term from a list of assigned terms.
		 *
		 * @param array  $terms    The assigned terms.
		 * @param string $taxonomy The taxonomy of the terms.
		 * @return \WP_Term|null
		 */
		protected function main_term_from_terms( $terms, string $taxonomy ): ?\WP_Term {
			if ( is_wp_error( $terms ) || ! $terms ) {
				return null;
			}

			return apply_filters( 'breadcrumb_block_main_term', $terms[0], $terms, $taxonomy );
		}

		/**
		 * Resolve the item's primary term meta, when set and assigned.
		 *
		 * @param \WP_Post $post     The single post.
		 * @param string   $taxonomy The taxonomy shown for single items.
		 * @return \WP_Term|null
		 */
		protected function primary_term_for( \WP_Post $post, string $taxonomy ): ?\WP_Term {
			$primary_id = (int) get_post_meta( $post->ID, Primary_Term::META_KEY, true );

			if ( ! $primary_id ) {
				return null;
			}

			$term = get_term( $primary_id );

			if ( ! $term instanceof \WP_Term || is_wp_error( $term ) || $term->taxonomy !== $taxonomy || ! has_term( $primary_id, $taxonomy, $post ) ) {
				return null;
			}

			return apply_filters( 'breadcrumb_block_get_primary_term', $term, $post );
		}

		/**
		 * Product category trail.
		 */
		protected function add_crumbs_product_category(): void {
			$current_term = $GLOBALS['wp_query']->get_queried_object();

			$this->prepend_shop_page();
			$this->term_ancestors( $current_term->term_id, 'product_cat' );
			$this->add_item(
				$current_term->name,
				get_term_link( $current_term, 'product_cat' ),
				[],
				[
					'type'   => 'term',
					'object' => $current_term,
				]
			);
		}

		/**
		 * Product tag trail.
		 */
		protected function add_crumbs_product_tag(): void {
			$current_term = $GLOBALS['wp_query']->get_queried_object();

			$this->prepend_shop_page();

			$this->add_item(
				/* translators: %s: product tag */
				sprintf( $this->args['labels']['productsTagged'], $current_term->name ),
				get_term_link( $current_term, 'product_tag' ),
				[],
				[
					'type'   => 'term',
					'object' => $current_term,
				]
			);
		}

		/**
		 * Shop breadcrumb.
		 */
		protected function add_crumbs_shop(): void {
			if ( (int) get_option( 'page_on_front' ) === wc_get_page_id( 'shop' ) ) {
				return;
			}

			$shop_page_id = wc_get_page_id( 'shop' );
			$_name        = $shop_page_id ? get_the_title( $shop_page_id ) : '';

			if ( ! $_name ) {
				$product_post_type = get_post_type_object( 'product' );
				$_name             = $product_post_type->labels->name;
			}

			$this->add_item( $_name, get_post_type_archive_link( 'product' ), [], [ 'type' => 'shop' ] );
		}

		/**
		 * Prepend the shop page to shop breadcrumbs.
		 */
		protected function prepend_shop_page(): void {
			$permalinks   = wc_get_permalink_structure();
			$shop_page_id = wc_get_page_id( 'shop' );
			$shop_page    = get_post( $shop_page_id );

			// If permalinks contain the shop page in the URI prepend the breadcrumb with shop.
			if ( $shop_page_id && $shop_page && isset( $permalinks['product_base'] ) && strstr( $permalinks['product_base'], '/' . $shop_page->post_name ) && (int) get_option( 'page_on_front' ) !== $shop_page_id ) {
				$this->add_item(
					get_the_title( $shop_page ),
					get_permalink( $shop_page ),
					[],
					[
						'type'   => 'shop',
						'object' => $shop_page,
					]
				);
			}
		}

		/**
		 * Page trail.
		 */
		protected function add_crumbs_page(): void {
			global $post;

			// Add parent pages if any.
			$this->add_parent_crumbs( $post->post_parent );

			$this->add_item(
				get_the_title(),
				get_permalink(),
				[ 'aria-current' => 'page' ],
				[
					'type'   => 'page',
					'object' => $post,
				]
			);
		}

		/**
		 * Add parent crumbs for page and other hierarchical post types.
		 *
		 * @param int $parent_id Parent post ID.
		 *
		 * @return void
		 */
		protected function add_parent_crumbs( int $parent_id ): void {
			if ( $parent_id ) {
				$parent_crumbs = [];

				while ( $parent_id ) {
					$post = get_post( $parent_id );

					// Ignore home page.
					if ( 'page' === $post->post_type && (int) get_option( 'page_on_front' ) === $parent_id ) {
						break;
					}

					$parent_crumbs[] = [
						get_the_title( $post->ID ),
						get_permalink( $post->ID ),
						[
							'type'   => 'page' === $post->post_type ? 'page' : 'post',
							'object' => $post,
						],
					];

					$parent_id = $post->post_parent;
				}

				$parent_crumbs = array_reverse( $parent_crumbs );

				foreach ( $parent_crumbs as $crumb ) {
					$this->add_item( $crumb[0], $crumb[1], [], $crumb[2] );
				}
			}
		}

		/**
		 * Post type archive trail.
		 */
		protected function add_crumbs_post_type_archive(): void {
			$post_type = get_post_type_object( get_post_type() );

			if ( $post_type ) {
				$this->add_item(
					$post_type->labels->name,
					get_post_type_archive_link( get_post_type() ),
					[],
					[
						'type'   => 'post_type_archive',
						'object' => $post_type,
					]
				);
			}
		}

		/**
		 * Category trail.
		 */
		protected function add_crumbs_category(): void {
			$this_category = get_category( $GLOBALS['wp_query']->get_queried_object() );

			if ( 0 !== (int) $this_category->parent ) {
				$this->term_ancestors( $this_category->term_id, 'category' );
			}

			$this->add_item(
				single_cat_title( '', false ),
				get_category_link( $this_category->term_id ),
				[],
				[
					'type'   => 'term',
					'object' => $this_category,
				]
			);
		}

		/**
		 * Tag trail.
		 */
		protected function add_crumbs_tag(): void {
			$queried_object = $GLOBALS['wp_query']->get_queried_object();

			$this->add_item(
				/* translators: %s: tag name */
				sprintf( $this->args['labels']['postsTagged'], single_tag_title( '', false ) ),
				get_tag_link( $queried_object->term_id ),
				[],
				[
					'type'   => 'term',
					'object' => $queried_object,
				]
			);
		}

		/**
		 * Add crumbs for date based archives.
		 */
		protected function add_crumbs_date(): void {
			if ( is_year() || is_month() || is_day() ) {
				$this->add_item( get_the_time( 'Y' ), get_year_link( get_the_time( 'Y' ) ), [], [ 'type' => 'date_year' ] );
			}
			if ( is_month() || is_day() ) {
				$this->add_item( get_the_time( 'F' ), get_month_link( get_the_time( 'Y' ), get_the_time( 'm' ) ), [], [ 'type' => 'date_month' ] );
			}
			if ( is_day() ) {
				$this->add_item( get_the_time( 'd' ), '', [], [ 'type' => 'date_day' ] );
			}
		}

		/**
		 * Add crumbs for taxonomies
		 */
		protected function add_crumbs_tax(): void {
			$this_term = $GLOBALS['wp_query']->get_queried_object();
			$taxonomy  = get_taxonomy( $this_term->taxonomy );

			$this->add_item(
				$taxonomy->labels->name,
				'',
				[],
				[
					'type'   => 'taxonomy',
					'object' => $taxonomy,
				]
			);

			if ( 0 !== (int) $this_term->parent ) {
				$this->term_ancestors( $this_term->term_id, $this_term->taxonomy );
			}

			$this->add_item(
				single_term_title( '', false ),
				get_term_link( $this_term->term_id, $this_term->taxonomy ),
				[],
				[
					'type'   => 'term',
					'object' => $this_term,
				]
			);
		}

		/**
		 * Add a breadcrumb for author archives.
		 */
		protected function add_crumbs_author(): void {
			global $author;

			$userdata = get_userdata( $author );

			$this->add_item(
				/* translators: %s: author name */
				sprintf( $this->args['labels']['author'], $userdata->display_name ),
				'',
				[],
				[
					'type'   => 'author',
					'object' => $author,
				]
			);
		}

		/**
		 * Add crumbs for a term.
		 *
		 * @param int    $term_id  Term ID.
		 * @param string $taxonomy Taxonomy.
		 */
		protected function term_ancestors( int $term_id, string $taxonomy ): void {
			$ancestors = get_ancestors( $term_id, $taxonomy );
			$ancestors = array_reverse( $ancestors );

			foreach ( $ancestors as $ancestor ) {
				$ancestor = get_term( $ancestor, $taxonomy );

				if ( ! is_wp_error( $ancestor ) && $ancestor ) {
					$this->add_item(
						$ancestor->name,
						get_term_link( $ancestor ),
						[],
						[
							'type'   => 'term',
							'object' => $ancestor,
						]
					);
				}
			}
		}

		/**
		 * Add a breadcrumb for search results.
		 */
		protected function search_trail(): void {
			if ( is_search() ) {
				$this->add_item( sprintf( $this->args['labels']['searchResult'], get_search_query() ), remove_query_arg( 'paged' ), [], [ 'type' => 'search' ] );
			}
		}

		/**
		 * Add a breadcrumb for pagination.
		 */
		protected function paged_trail(): void {
			if ( get_query_var( 'paged' ) ) {
				/* translators: %d: page number */
				$this->add_item( sprintf( $this->args['labels']['page'], get_query_var( 'paged' ) ), '', [], [ 'type' => 'paged' ] );
			}
		}

		/**
		 * Generates BreadcrumbList structured data.
		 *
		 * @param Breadcrumbs $breadcrumbs Breadcrumb data.
		 */
		public function generate_breadcrumb_structured_data( Breadcrumbs $breadcrumbs ): void {
			$crumbs = $breadcrumbs->get_items();

			if ( [] === $crumbs ) {
				return;
			}

			$markup                    = [];
			$markup['@context']        = 'http://schema.org';
			$markup['@type']           = 'BreadcrumbList';
			$markup['itemListElement'] = [];

			foreach ( $crumbs as $key => $crumb ) {
				// Item names carry HTML entities from the labels (esc_html-ready
				// output); the schema name must match the text the user sees, so
				// decode them (e.g. 'Search results for &ldquo;x&rdquo;').
				$item_name = html_entity_decode( (string) $crumb[0], ENT_QUOTES, 'UTF-8' );

				$markup['itemListElement'][ $key ] = [
					'@type'    => 'ListItem',
					'position' => $key + 1,
					'item'     => [
						'name' => $item_name,
					],
				];

				if ( ! empty( $crumb[1] ) ) {
					$markup['itemListElement'][ $key ]['item'] += [ '@id' => $crumb[1] ];
				} elseif ( isset( $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'] ) ) {
					$protocol    = isset( $_SERVER['HTTPS'] ) && ! empty( $_SERVER['HTTPS'] ) ? 'https' : 'http';
					$current_url = esc_url_raw( $protocol . '://' . wp_unslash( $_SERVER['HTTP_HOST'] ) . wp_unslash( $_SERVER['REQUEST_URI'] ) );

					$markup['itemListElement'][ $key ]['item'] += [ '@id' => $current_url ];
				}
			}

			$this->structured_data = apply_filters( 'breadcrumb_block_structured_data', $markup, $breadcrumbs );
		}

		/**
		 * Output the structured data for the breadcrumb
		 *
		 * @return void
		 */
		public function output_structured_data(): void {
			if ( $this->structured_data ) {
				echo '<script type="application/ld+json">' . _wp_specialchars( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					wp_json_encode( $this->structured_data ),
					ENT_NOQUOTES,  // ENT_QUOTES, Escape quotes in attribute nodes only.
					'UTF-8',       // json_encode() outputs UTF-8 (really just ASCII), not the blog's charset.
					true           // Double escape entities: `&amp;` -> `&amp;amp;`.
				) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		/**
		 * Check whether WooCommerce is activated.
		 *
		 * @return bool
		 */
		public function is_woocommerce_activated(): bool {
			return class_exists( 'woocommerce' );
		}
	}
}
