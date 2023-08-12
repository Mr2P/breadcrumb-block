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

if ( ! class_exists( Breadcrumbs::class ) ) :
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
		protected $items = [];

		/**
		 * Arguments to build the breadcrumb trail.
		 *
		 * @var array
		 */
		protected $args = [];

		/**
		 * Structured data
		 *
		 * @var string
		 */
		protected $structured_data;

		/**
		 * Plugin instance
		 *
		 * @var Breadcrumbs
		 */
		private static $instance;

		/**
		 * A dummy constructor
		 */
		private function __construct() {
			// Generate structured data.
			add_action( 'breadcrumb_block_render_breadcrumb_trail', [ $this, 'generate_breadcrumb_structured_data' ], 10 );

			// Out the structured data to the front end.
			add_action( 'wp_footer', [ $this, 'output_structured_data' ] );
		}

		/**
		 * Initialize the instance.
		 *
		 * @return Breadcrumbs
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Breadcrumbs ) ) {
				self::$instance = new Breadcrumbs();
			}

			return self::$instance;
		}

		/**
		 * Add a item.
		 *
		 * @param string $name Name.
		 * @param string $link Link.
		 * @param array  $attrs
		 */
		public function add_item( $name, $link = '', $attrs = [] ) {
			$this->items[] = [ wp_strip_all_tags( $name ), $link, $attrs ];
		}


		/**
		 * Build the breadcrumb items
		 *
		 * @return array
		 */
		public function generate() {
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

			if ( ( ! is_front_page() && ( ! $is_woocommerce_activated || ! ( is_post_type_archive() && intval( get_option( 'page_on_front' ) ) === wc_get_page_id( 'shop' ) ) ) ) || is_paged() ) {
				$this->add_crumbs_front_page();

				foreach ( $conditionals as $conditional ) {
					if ( call_user_func( $conditional ) ) {
						call_user_func( array( $this, 'add_crumbs_' . substr( $conditional, 3 ) ) );
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
		public function get_items() {
			return $this->items;
		}

		/**
		 * Reset the breadcrumb items.
		 *
		 * @return array
		 */
		public function reset() {
			$this->items = [];
		}

		/**
		 * Parse the arguments with the deaults.
		 *
		 * @return array
		 */
		public function parse_args( $args ) {
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

			return $this->args;
		}

		/**
		 * Get the breadcrumb trail.
		 *
		 * @param @args
		 * @return array
		 */
		public function get_breadcrumb_trail( $args = [] ) {
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
							function ( $carry, $key ) use ( $attrs ) {
								return $carry . ' ' . $key . '="' . htmlspecialchars( $attrs[ $key ] ) . '"';
							},
							''
						);
					}

					$attributes = sprintf( 'class="%1$s"%2$s', \implode( ' ', $item_classes ), $attributes );

					// Build the list item.
					$breadcrumb .= sprintf( '<%1$s %2$s>%3$s</%1$s>', tag_escape( $args['item_tag'] ), $attributes, $item );
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

			echo $breadcrumb; // WPCS: XSS OK.
		}

		/**
		 * Front page trail.
		 */
		protected function add_crumbs_front_page() {
			$home_label = $this->args['labels']['home'] ?? '';
			$home_text  = apply_filters( 'breadcrumb_block_home_text', empty( $home_label ) ? __( 'Home', 'breadcrumb-block' ) : $home_label );
			$this->add_item( $home_text, esc_url( user_trailingslashit( apply_filters( 'breadcrumb_block_home_url', home_url() ) ) ), [ 'rel' => 'home' ] );
		}

		/**
		 * Is home trail.
		 */
		protected function add_crumbs_home() {
			$this->add_item( single_post_title( '', false ), false, [ 'aria-current' => 'page' ] );
		}

		/**
		 * 404 trail.
		 */
		protected function add_crumbs_404() {
			$this->add_item( __( 'Error 404', 'breadcrumb-block' ), false, [ 'aria-current' => 'page' ] );
		}

		/**
		 * Attachment trail.
		 */
		protected function add_crumbs_attachment() {
			global $post;

			$this->add_crumbs_single( $post->post_parent, get_permalink( $post->post_parent ) );
			$this->add_item( get_the_title(), get_permalink(), [ 'aria-current' => 'page' ] );
		}

		/**
		 * Single post trail.
		 *
		 * @param int    $post_id   Post ID.
		 * @param string $permalink Post permalink.
		 */
		protected function add_crumbs_single( $post_id = 0, $permalink = '' ) {
			if ( ! $post_id ) {
				global $post;
			} else {
				$post = get_post( $post_id ); // WPCS: override ok.
			}

			if ( ! $permalink ) {
				$permalink = get_permalink( $post );
			}

			if ( 'product' === get_post_type( $post ) ) {
				$this->prepend_shop_page();

				$terms = wc_get_product_terms(
					$post->ID,
					'product_cat',
					apply_filters(
						'breadcrumb_block_product_terms_args',
						array(
							'orderby' => 'parent',
							'order'   => 'DESC',
						)
					)
				);

				if ( $terms ) {
					$main_term = apply_filters( 'breadcrumb_block_main_term', $terms[0], $terms, 'product_cat' );
					$this->term_ancestors( $main_term->term_id, 'product_cat' );
					$this->add_item( $main_term->name, get_term_link( $main_term ) );
				}
			} elseif ( 'post' !== get_post_type( $post ) ) {
				$post_type = get_post_type_object( get_post_type( $post ) );

				// Allow the ability to remove the post type name from the breadcrumb.
				if ( apply_filters( 'breadcrumb_block_add_post_type_name', true, $post, $this ) ) {
					// If the post doesn't have a parent, get its hierarchy based off the post type.
					if ( ! empty( $post_type->has_archive ) ) {
						$this->add_item( $post_type->labels->name, get_post_type_archive_link( get_post_type( $post ) ) );
					}
				}

				// If the post has a parent, follow the parent trail.
				$this->add_parent_crumbs( $post->post_parent );

				do_action( 'breadcrumb_block_single_' . $post_type->name, $post, $this );
			} else {
				$cats = get_the_category( $post );
				if ( $cats ) {
					$cat = apply_filters( 'breadcrumb_block_main_term', $cats[0], $cats, 'category' );
					$this->term_ancestors( $cat->term_id, 'category' );
					$this->add_item( $cat->name, get_term_link( $cat ) );
				}
			}

			$this->add_item( get_the_title( $post ), $permalink, [ 'aria-current' => 'page' ] );
		}

		/**
		 * Product category trail.
		 */
		protected function add_crumbs_product_category() {
			$current_term = $GLOBALS['wp_query']->get_queried_object();

			$this->prepend_shop_page();
			$this->term_ancestors( $current_term->term_id, 'product_cat' );
			$this->add_item( $current_term->name, get_term_link( $current_term, 'product_cat' ) );
		}

		/**
		 * Product tag trail.
		 */
		protected function add_crumbs_product_tag() {
			$current_term = $GLOBALS['wp_query']->get_queried_object();

			$this->prepend_shop_page();

			/* translators: %s: product tag */
			$this->add_item( sprintf( __( 'Products tagged &ldquo;%s&rdquo;', 'breadcrumb-block' ), $current_term->name ), get_term_link( $current_term, 'product_tag' ) );
		}

		/**
		 * Shop breadcrumb.
		 */
		protected function add_crumbs_shop() {
			if ( intval( get_option( 'page_on_front' ) ) === wc_get_page_id( 'shop' ) ) {
				return;
			}

			$_name = wc_get_page_id( 'shop' ) ? get_the_title( wc_get_page_id( 'shop' ) ) : '';

			if ( ! $_name ) {
				$product_post_type = get_post_type_object( 'product' );
				$_name             = $product_post_type->labels->name;
			}

			$this->add_item( $_name, get_post_type_archive_link( 'product' ) );
		}

		/**
		 * Prepend the shop page to shop breadcrumbs.
		 */
		protected function prepend_shop_page() {
			$permalinks   = wc_get_permalink_structure();
			$shop_page_id = wc_get_page_id( 'shop' );
			$shop_page    = get_post( $shop_page_id );

			// If permalinks contain the shop page in the URI prepend the breadcrumb with shop.
			if ( $shop_page_id && $shop_page && isset( $permalinks['product_base'] ) && strstr( $permalinks['product_base'], '/' . $shop_page->post_name ) && intval( get_option( 'page_on_front' ) ) !== $shop_page_id ) {
				$this->add_item( get_the_title( $shop_page ), get_permalink( $shop_page ) );
			}
		}

		/**
		 * Page trail.
		 */
		protected function add_crumbs_page() {
			global $post;

			// Add parent pages if any.
			$this->add_parent_crumbs( $post->post_parent );

			$this->add_item( get_the_title(), get_permalink(), [ 'aria-current' => 'page' ] );
		}

		/**
		 * Add parent crumbs for page and other hierarchical post types.
		 *
		 * @return void
		 */
		protected function add_parent_crumbs( $parent_id ) {
			if ( $parent_id ) {
				$parent_crumbs = array();

				while ( $parent_id ) {
					$post = get_post( $parent_id );

					// Ignore home page.
					if ( 'page' === $post->post_type && $parent_id === intval( get_option( 'page_on_front' ) ) ) {
						break;
					}

					$parent_crumbs[] = array( get_the_title( $post->ID ), get_permalink( $post->ID ) );

					$parent_id = $post->post_parent;
				}

				$parent_crumbs = array_reverse( $parent_crumbs );

				foreach ( $parent_crumbs as $crumb ) {
					$this->add_item( $crumb[0], $crumb[1] );
				}
			}
		}

		/**
		 * Post type archive trail.
		 */
		protected function add_crumbs_post_type_archive() {
			$post_type = get_post_type_object( get_post_type() );

			if ( $post_type ) {
				$this->add_item( $post_type->labels->name, get_post_type_archive_link( get_post_type() ) );
			}
		}

		/**
		 * Category trail.
		 */
		protected function add_crumbs_category() {
			$this_category = get_category( $GLOBALS['wp_query']->get_queried_object() );

			if ( 0 !== intval( $this_category->parent ) ) {
				$this->term_ancestors( $this_category->term_id, 'category' );
			}

			$this->add_item( single_cat_title( '', false ), get_category_link( $this_category->term_id ) );
		}

		/**
		 * Tag trail.
		 */
		protected function add_crumbs_tag() {
			$queried_object = $GLOBALS['wp_query']->get_queried_object();

			/* translators: %s: tag name */
			$this->add_item( sprintf( __( 'Posts tagged &ldquo;%s&rdquo;', 'breadcrumb-block' ), single_tag_title( '', false ) ), get_tag_link( $queried_object->term_id ) );
		}

		/**
		 * Add crumbs for date based archives.
		 */
		protected function add_crumbs_date() {
			if ( is_year() || is_month() || is_day() ) {
				$this->add_item( get_the_time( 'Y' ), get_year_link( get_the_time( 'Y' ) ) );
			}
			if ( is_month() || is_day() ) {
				$this->add_item( get_the_time( 'F' ), get_month_link( get_the_time( 'Y' ), get_the_time( 'm' ) ) );
			}
			if ( is_day() ) {
				$this->add_item( get_the_time( 'd' ) );
			}
		}

		/**
		 * Add crumbs for taxonomies
		 */
		protected function add_crumbs_tax() {
			$this_term = $GLOBALS['wp_query']->get_queried_object();
			$taxonomy  = get_taxonomy( $this_term->taxonomy );

			$this->add_item( $taxonomy->labels->name );

			if ( 0 !== intval( $this_term->parent ) ) {
				$this->term_ancestors( $this_term->term_id, $this_term->taxonomy );
			}

			$this->add_item( single_term_title( '', false ), get_term_link( $this_term->term_id, $this_term->taxonomy ) );
		}

		/**
		 * Add a breadcrumb for author archives.
		 */
		protected function add_crumbs_author() {
			global $author;

			$userdata = get_userdata( $author );

			/* translators: %s: author name */
			$this->add_item( sprintf( __( 'Author: %s', 'breadcrumb-block' ), $userdata->display_name ) );
		}

		/**
		 * Add crumbs for a term.
		 *
		 * @param int    $term_id  Term ID.
		 * @param string $taxonomy Taxonomy.
		 */
		protected function term_ancestors( $term_id, $taxonomy ) {
			$ancestors = get_ancestors( $term_id, $taxonomy );
			$ancestors = array_reverse( $ancestors );

			foreach ( $ancestors as $ancestor ) {
				$ancestor = get_term( $ancestor, $taxonomy );

				if ( ! is_wp_error( $ancestor ) && $ancestor ) {
					$this->add_item( $ancestor->name, get_term_link( $ancestor ) );
				}
			}
		}

		/**
		 * Add a breadcrumb for search results.
		 */
		protected function search_trail() {
			if ( is_search() ) {
				/* translators: %s: search term */
				$this->add_item( sprintf( __( 'Search results for &ldquo;%s&rdquo;', 'breadcrumb-block' ), get_search_query() ), remove_query_arg( 'paged' ) );
			}
		}

		/**
		 * Add a breadcrumb for pagination.
		 */
		protected function paged_trail() {
			if ( get_query_var( 'paged' ) ) {
				/* translators: %d: page number */
				$this->add_item( sprintf( __( 'Page %d', 'breadcrumb-block' ), get_query_var( 'paged' ) ) );
			}
		}

		/**
		 * Generates BreadcrumbList structured data.
		 *
		 * @param Breadcrumbs $breadcrumbs Breadcrumb data.
		 */
		public function generate_breadcrumb_structured_data( $breadcrumbs ) {
			$crumbs = $breadcrumbs->get_items();

			if ( empty( $crumbs ) || ! is_array( $crumbs ) ) {
				return;
			}

			$markup                    = array();
			$markup['@context']        = 'http://schema.org';
			$markup['@type']           = 'BreadcrumbList';
			$markup['itemListElement'] = array();

			foreach ( $crumbs as $key => $crumb ) {
				$markup['itemListElement'][ $key ] = array(
					'@type'    => 'ListItem',
					'position' => $key + 1,
					'item'     => array(
						'name' => $crumb[0],
					),
				);

				if ( ! empty( $crumb[1] ) ) {
					$markup['itemListElement'][ $key ]['item'] += array( '@id' => $crumb[1] );
				} elseif ( isset( $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'] ) ) {
					$protocol    = isset( $_SERVER['HTTPS'] ) && ! empty( $_SERVER['HTTPS'] ) ? 'https' : 'http';
					$current_url = esc_url_raw( $protocol . '://' . wp_unslash( $_SERVER['HTTP_HOST'] ) . wp_unslash( $_SERVER['REQUEST_URI'] ) );

					$markup['itemListElement'][ $key ]['item'] += array( '@id' => $current_url );
				}
			}

			$this->structured_data = apply_filters( 'breadcrumb_block_structured_data', $markup, $breadcrumbs );
		}

		/**
		 * Output the structured data for the breadcrumb
		 *
		 * @return void
		 */
		public function output_structured_data() {
			if ( $this->structured_data ) {
				echo '<script type="application/ld+json">' . _wp_specialchars(
					wp_json_encode( $this->structured_data ),
					ENT_NOQUOTES,  // ENT_QUOTES, Escape quotes in attribute nodes only.
					'UTF-8',       // json_encode() outputs UTF-8 (really just ASCII), not the blog's charset.
					true           // Double escape entities: `&amp;` -> `&amp;amp;`.
				) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		/**
		 * Check is woocommere activated
		 *
		 * @return boolean
		 */
		public function is_woocommerce_activated() {
			return class_exists( 'woocommerce' );
		}
	}
endif;

