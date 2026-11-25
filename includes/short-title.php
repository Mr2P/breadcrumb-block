<?php
/**
 * Breadcrumb short title: meta registration and item UI.
 *
 * @package   BreadcrumbBlock
 * @author    Phi Phan <mrphipv@gmail.com>
 * @copyright Copyright (c) 2022, Phi Phan
 */

namespace BreadcrumbBlock;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( Short_Title::class ) ) {
	/**
	 * Registers the short title meta and its editor screens.
	 */
	class Short_Title {
		/**
		 * The meta key used to store the short title on posts and terms.
		 *
		 * @var string
		 */
		public const META_KEY = '_breadcrumb_short_title';

		/**
		 * Register meta and term form hooks.
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
						'type'              => 'string',
						'single'            => true,
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
						'show_in_rest'      => true,
						'auth_callback'     => static fn (): bool => (bool) ( $post_type_object && current_user_can( $post_type_object->cap->edit_posts ) ),
					]
				);
			}

			// Term meta for every public taxonomy, plus the term form fields.
			foreach ( get_taxonomies( [ 'public' => true ] ) as $taxonomy ) {
				$taxonomy_object = get_taxonomy( $taxonomy );

				register_term_meta(
					$taxonomy,
					self::META_KEY,
					[
						'type'              => 'string',
						'single'            => true,
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
						'auth_callback'     => static fn (): bool => (bool) ( $taxonomy_object && current_user_can( $taxonomy_object->cap->manage_terms ) ),
					]
				);

				add_action( "{$taxonomy}_add_form_fields", [ self::class, 'render_add_field' ] );
				add_action( "{$taxonomy}_edit_form_fields", [ self::class, 'render_edit_field' ] );
				add_action( "created_{$taxonomy}", [ self::class, 'save_term_field' ] );
				add_action( "edited_{$taxonomy}", [ self::class, 'save_term_field' ] );
			}
		}

		/**
		 * Render the short title field on the add term form.
		 *
		 * @param string $taxonomy Taxonomy slug.
		 *
		 * @return void
		 */
		public static function render_add_field( string $taxonomy ): void {
			unset( $taxonomy );

			self::render_nonce();
			?>
			<div class="form-field">
				<label for="<?php echo esc_attr( self::META_KEY ); ?>"><?php esc_html_e( 'Breadcrumb short title', 'breadcrumb-block' ); ?></label>
				<input type="text" id="<?php echo esc_attr( self::META_KEY ); ?>" name="<?php echo esc_attr( self::META_KEY ); ?>" value="" />
				<p><?php esc_html_e( 'Optional. Shown in the breadcrumb trail instead of the full title.', 'breadcrumb-block' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Render the short title field on the edit term form.
		 *
		 * @param \WP_Term $term The term being edited.
		 *
		 * @return void
		 */
		public static function render_edit_field( \WP_Term $term ): void {
			self::render_nonce();
			$value = get_term_meta( $term->term_id, self::META_KEY, true );
			?>
			<tr class="form-field">
				<th scope="row"><label for="<?php echo esc_attr( self::META_KEY ); ?>"><?php esc_html_e( 'Breadcrumb short title', 'breadcrumb-block' ); ?></label></th>
				<td>
					<input type="text" id="<?php echo esc_attr( self::META_KEY ); ?>" name="<?php echo esc_attr( self::META_KEY ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" />
					<p class="description"><?php esc_html_e( 'Optional. Shown in the breadcrumb trail instead of the full title.', 'breadcrumb-block' ); ?></p>
				</td>
			</tr>
			<?php
		}

		/**
		 * Save the short title from the term form.
		 *
		 * @param int $term_id Term ID.
		 *
		 * @return void
		 */
		public static function save_term_field( int $term_id ): void {
			$nonce = isset( $_POST['breadcrumb_short_title_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['breadcrumb_short_title_nonce'] ) ) : '';

			if ( ! wp_verify_nonce( $nonce, 'breadcrumb_short_title_save' ) ) {
				return;
			}

			$taxonomy        = isset( $_POST['taxonomy'] ) ? sanitize_key( wp_unslash( $_POST['taxonomy'] ) ) : '';
			$taxonomy_object = '' !== $taxonomy ? get_taxonomy( $taxonomy ) : false;

			if ( ! $taxonomy_object || ! current_user_can( $taxonomy_object->cap->manage_terms ) ) {
				return;
			}

			$short_title = isset( $_POST[ self::META_KEY ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::META_KEY ] ) ) : '';

			if ( '' === $short_title ) {
				delete_term_meta( $term_id, self::META_KEY );
				return;
			}

			update_term_meta( $term_id, self::META_KEY, $short_title );
		}

		/**
		 * Render the nonce field shared by the term forms.
		 *
		 * @return void
		 */
		private static function render_nonce(): void {
			wp_nonce_field( 'breadcrumb_short_title_save', 'breadcrumb_short_title_nonce' );
		}
	}
}
