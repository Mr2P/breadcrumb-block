<?php
/**
 * Breadcrumb Block settings: per-post-type taxonomy mapping.
 *
 * @package   BreadcrumbBlock
 * @author    Phi Phan <mrphipv@gmail.com>
 * @copyright Copyright (c) 2022, Phi Phan
 */

namespace BreadcrumbBlock;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( Settings::class ) ) {
	/**
	 * Registers the settings screen and sanitizes the taxonomy mapping.
	 */
	class Settings {
		/**
		 * The option key storing the per-post-type taxonomy mapping.
		 *
		 * @var string
		 */
		public const OPTION_KEY = 'breadcrumb_block_taxonomies';

		/**
		 * Register the setting and the settings page.
		 *
		 * @return void
		 */
		public static function init(): void {
			register_setting(
				'breadcrumb_block',
				self::OPTION_KEY,
				[
					'type'              => 'object',
					'default'           => [],
					'sanitize_callback' => [ self::class, 'sanitize_taxonomies' ],
					'show_in_rest'      => [
						'schema' => [
							'type'                 => 'object',
							'additionalProperties' => [ 'type' => 'string' ],
						],
					],
				]
			);

			if ( ! is_admin() ) {
				return;
			}

			add_action( 'admin_menu', [ self::class, 'register_page' ] );
		}

		/**
		 * Register the settings page.
		 *
		 * @return void
		 */
		public static function register_page(): void {
			add_options_page(
				__( 'Breadcrumb Block', 'breadcrumb-block' ),
				__( 'Breadcrumb Block', 'breadcrumb-block' ),
				'manage_options',
				'breadcrumb-block',
				[ self::class, 'render_page' ]
			);

			add_settings_section(
				'breadcrumb_block_taxonomies',
				__( 'Taxonomy for single items', 'breadcrumb-block' ),
				[ self::class, 'render_taxonomies_section' ],
				'breadcrumb-block'
			);

			foreach ( self::post_types_with_taxonomies() as $post_type_name => $data ) {
				add_settings_field(
					"taxonomy_{$post_type_name}",
					$data['object']->labels->singular_name,
					[ self::class, 'render_taxonomy_field' ],
					'breadcrumb-block',
					'breadcrumb_block_taxonomies',
					[
						'post_type'  => $data['object'],
						'taxonomies' => $data['taxonomies'],
					]
				);
			}
		}

		/**
		 * Render the settings page.
		 *
		 * @return void
		 */
		public static function render_page(): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Breadcrumb Block', 'breadcrumb-block' ); ?></h1>
				<form action="options.php" method="post">
					<?php settings_fields( 'breadcrumb_block' ); ?>
					<?php do_settings_sections( 'breadcrumb-block' ); ?>
					<?php submit_button(); ?>
				</form>
			</div>
			<?php
		}

		/**
		 * Render the taxonomies section description.
		 *
		 * @return void
		 */
		public static function render_taxonomies_section(): void {
			?>
			<p><?php esc_html_e( 'Choose the taxonomy whose terms are shown in single-item breadcrumb trails for each post type. Default uses the type default (category for posts, product category for products).', 'breadcrumb-block' ); ?></p>
			<?php
		}

		/**
		 * Render the taxonomy dropdown for one post type.
		 *
		 * @param array $args The post type object and its public taxonomies.
		 *
		 * @return void
		 */
		public static function render_taxonomy_field( array $args ): void {
			$post_type  = $args['post_type'];
			$taxonomies = $args['taxonomies'];
			$mapping    = get_option( self::OPTION_KEY, [] );
			$current    = is_array( $mapping ) ? ( $mapping[ $post_type->name ] ?? '' ) : '';
			?>
			<select class="regular-text" id="<?php echo esc_attr( self::OPTION_KEY . '-' . $post_type->name ); ?>" name="<?php echo esc_attr( self::OPTION_KEY . '[' . $post_type->name . ']' ); ?>">
				<option value=""><?php esc_html_e( 'Default', 'breadcrumb-block' ); ?></option>
				<?php foreach ( $taxonomies as $taxonomy ) : ?>
					<option value="<?php echo esc_attr( $taxonomy->name ); ?>" <?php selected( $current, $taxonomy->name ); ?>>
						<?php echo esc_html( $taxonomy->labels->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php
		}

		/**
		 * Public post types whose single-item trails can include a term, with
		 * their public taxonomies.
		 *
		 * Pages and attachments never show a term in their trails: no taxonomy
		 * to map.
		 *
		 * @return array Post type data keyed by post type name.
		 */
		public static function post_types_with_taxonomies(): array {
			$types = [];

			foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $post_type ) {
				if ( in_array( $post_type->name, [ 'attachment', 'page' ], true ) ) {
					continue;
				}

				$taxonomies = array_filter(
					get_object_taxonomies( $post_type->name, 'objects' ),
					static fn ( $taxonomy ) => $taxonomy->public
				);

				if ( $taxonomies ) {
					$types[ $post_type->name ] = [
						'object'     => $post_type,
						'taxonomies' => $taxonomies,
					];
				}
			}

			return $types;
		}

		/**
		 * Sanitize the taxonomy mapping against each type's real taxonomies.
		 *
		 * @param array|mixed $input Raw mapping: post type => taxonomy slug.
		 * @return array
		 */
		public static function sanitize_taxonomies( $input ): array {
			$input = is_array( $input ) ? array_map( 'wp_unslash', $input ) : [];
			$clean = [];

			foreach ( $input as $post_type_name => $taxonomy ) {
				$post_type_name = sanitize_key( is_string( $post_type_name ) ? $post_type_name : '' );
				$taxonomy       = sanitize_key( is_string( $taxonomy ) ? $taxonomy : '' );

				if ( '' === $post_type_name || '' === $taxonomy ) {
					continue;
				}

				$taxonomy_object = get_taxonomy( $taxonomy );

				if ( $taxonomy_object && $taxonomy_object->public && in_array( $post_type_name, $taxonomy_object->object_type, true ) ) {
					$clean[ $post_type_name ] = sanitize_key( $taxonomy );
				}
			}

			return $clean;
		}
	}
}
