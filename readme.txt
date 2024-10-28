=== Breadcrumb Block ===
Contributors:      Mr2P
Tags:              breadcrumb, block, Gutenberg, navigation, menu
Requires PHP:      7.0.0
Requires at least: 5.9.0
Tested up to:      6.7
Stable tag:        1.0.15
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html
Donate link:       https://boldblocks.net?utm_source=wp.org&utm_campaign=readme&utm_medium=link&utm_content=Breadcrumb+Block+Donate

A simple breadcrumb trail block that supports JSON-LD structured data and is compatible with Woocommerce

== Description ==

This is a single-block plugin for the breadcrumb trail. It's simple, lightweight, SEO-friendly, and WooCommerce compatibility. It also includes some simple separator icons. It works everywhere: Page Editor, Site Editor, and template files.

=== How to customize the breadcrumb ===

1. How to change/remove an item?

        add_filter( 'breadcrumb_block_get_item', function ( $item_args, $context, $breadcrumbs_instance ) {
          // Ignore items without context.
          if ( ! $context || ! ( $context['object'] ?? false ) ) {
            return $item_args;
          }

          // Eg: remove a term.
          if ( 'term' === ( $context['type'] ?? '' ) && 'term-slug' === $context['object']->slug ) {
            return false;
          }

          // Eg: Change the title of a page.
          if ( 'page' === ( $context['type'] ?? '' ) && page_id_to_change === $context['object']->ID ) {
            $item_args[0] = 'Make it shorter';
          }

          return $item_args;
        }, 10, 3 );

    $item_args is a 3-item array: `[$item_label, $item_link, $item_attrs]`
    $context is an associative array: `['type' => 'item type', 'object' => 'item object']`. `type` can be one of the following values: `front_page`, `home`, `shop`, `page`, `post`, `single`, `term`, `taxonomy`, `post_type_archive`, `search`, `404`, `paged`, `author`, `date_year`, `date_month`, `date_day`, `attachment`.

2. How to change the markup of the block?

        add_filter( 'breadcrumb_block_get_breadcrumb_trail', function ( $markup, $args, $breadcrumbs_instance ) {
          return $markup;
        }, 10, 3 );

3. How to add or remove the items from the breadcrumb trail?

        add_filter( 'breadcrumb_block_get_items', function ( $items, $breadcrumbs_instance ) {
          return $items;
        }, 10, 2 );

4. How to use a custom separator for the breadcrumb trail?

        add_filter( 'breadcrumb_block_get_args', function ( $args ) {
          // For example, change separator.
          $args['separator'] = '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor"	width="1em"	height="1em" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M3.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L9.293 8 3.646 2.354a.5.5 0 0 1 0-.708z"/><path fill-rule="evenodd" d="M7.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L13.293 8 7.646 2.354a.5.5 0 0 1 0-.708z"/></svg>';
          return $args;
        } );

    The custom separator should be an inline SVG. To make sure it displays properly, it should have three attributes: width, height, and fill. The values of these attributes should be as follows: `fill="currentColor" width="1em" height="1em"`.
    Using this hook, you can customize other attributes such as container, before, after, list_tag, item_tag, item_before, item_after, separator.

5. How to prepend the blog page to all single posts?

        add_action( 'breadcrumb_block_single_prepend', function ( $post, $breadcrumbs_instance ) {
          if ( 'post' === $post->post_type ) {
            $blog_id = get_option( 'page_for_posts');
            if ( $blog_id ) {
              $breadcrumbs_instance->add_item( get_the_title( $blog_id ), get_permalink( $blog_id ) );
            }
          }
        }, 10, 2 );

If this plugin is useful for you, please do a quick review and [rate it](https://wordpress.org/support/plugin/breadcrumb-block/reviews/#new-post) on WordPress.org to help us spread the word. I would very much appreciate it.

Please check out my other plugins if you're interested:

- **[Content Blocks Builder](https://wordpress.org/plugins/content-blocks-builder)** - This plugin turns the Block Editor into a powerful page builder by allowing you to create blocks, variations, and patterns directly in the Block Editor without needing a code editor.
- **[Meta Field Block](https://wordpress.org/plugins/display-a-meta-field-as-block)** - A block to display custom fields as blocks on the front end. It supports custom fields for posts, terms, users, and setting fields. It can also be used in the Query Loop block.
- **[SVG Block](https://wordpress.org/plugins/svg-block)** - A block to display SVG images as blocks. Useful for images, icons, dividers, and buttons. It allows you to upload SVG images and load them into the icon library.
- **[Icon separator](https://wordpress.org/plugins/icon-separator)** - A tiny block just like the core/separator block but with the ability to add an icon.
- **[Block Enhancements](https://wordpress.org/plugins/block-enhancements)** - Adds practical features to blocks like icons, box shadows, transforms, etc.
- **[Counting Number Block](https://wordpress.org/plugins/counting-number-block)** - A block to display numbers with a counting effect
- **[Better YouTube Embed Block](https://wordpress.org/plugins/better-youtube-embed-block)** - A block to solve the performance issue with embedded YouTube videos. It can also embed multiple videos and playlists.

The plugin is developed using @wordpress/create-block.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/breadcrumb-block` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress


== Frequently Asked Questions ==

= What problem does this plugin solve? =

It provides a simple way to add a breadcrumb trail to your site.

= Who needs this plugin? =

Anyone can use this plugin.

== Screenshots ==

1. Block settings

== Changelog ==

= 1.0.15 =
*Release Date - 28 October 2024*

* Added   - Support clientNavigation interactivity
* Updated - Replace classnames with clsx
* Updated - Tested up to WP 6.7

= 1.0.14 =
*Release Date - 07 May 2024*

* Added    - Add the breadcrumbs instance to the `breadcrumb_block_get_item` hook
* Added    - Add the new hook `breadcrumb_block_single_prepend`
* Improved - Add the commented text to remove the output not escaped warning

= 1.0.13 =
*Release Date - 30 April 2024*

* Added - A new hook `breadcrumb_block_get_item` for customizing/removing each item of the breadcrumbs

= 1.0.12 =
*Release Date - 22 September 2023*

* Added - A new hook `breadcrumb_block_get_args` for customizing the output of the breadcrumb.

= 1.0.11 =
*Release Date - 12 August 2023*

* DEV - Refactor block metadata and upgrade to apiVerion 3
* DEV - Add a hook `apply_filters( 'breadcrumb_block_strip_shortcodes', false, $breadcrumb_instance )` to allow opt-in/opt-out shortcodes in the post title. Thanks to Steven A. Zahm (https://github.com/shazahm1)
* DEV - Add parent crumbs to other hierarchical post types. Thanks to Steven A. Zahm (https://github.com/shazahm1)
* DEV - Add a hook `apply_filters( 'breadcrumb_block_add_post_type_name', true, $post, $breadcrumb_instance )` to allow opt-in/opt-out post type name for custom post types
* Fix - Conflict style with Bootstrap's breadcrumb

= 1.0.10 =
*Release Date - 13 Apr 2023*

* DEV - Add a setting to input a custom home text

= 1.0.9 =
*Release Date - 24 Mar 2023*

* FIX - Error converting WP_Post_Type to string. Thanks to @tnchuntic for reporting it.

= 1.0.8 =
*Release Date - 20 Mar 2023*

* DEV - Make the block compatible with woocommerce

= 1.0.7 =
*Release Date - 11 Mar 2023*

* FIX - Add namespace to class_exists check

= 1.0.6 =
*Release Date - 12 Feb 2023*

* Add new hooks `breadcrumb_block_home_text`, `breadcrumb_block_home_url`

= 1.0.5 =
*Release Date - 05 Feb 2023*

* DEV - Add a setting to hide the home page link

= 1.0.4 =
*Release Date - 19 Jan 2023*

* FIX - Gap issue: adding the semicolon to the CSS variable
* FIX - Could not modify breadcrumb data via the `breadcrumb_block_get_items` filter
* DEV - Add a new arrow icon as a separator

= 1.0.3 =
*Release Date - 13 Dec 2022*

* DEV - Use post_type->labels->name instead of post_type->labels->singular_name for custom post type archive name
* DEV - Add http://schema.org as @context for structured data
* Note: Big thanks to [Yannick](https://wordpress.org/support/users/ja4st3r) for this release

= 1.0.2 =
*Release Date - 08 Dec 2022*

* DEV - Add a setting to hide the current page title

= 1.0.1 =
*Release Date - 01 Dec 2022*

* FIX - Syntax error on PHP version 7.0.0

= 1.0.0 =
*Release Date - 22 Oct 2022*


