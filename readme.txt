=== Breadcrumb Block ===
Contributors:      Mr2P
Tags:              breadcrumb, block, Gutenberg, navigation, menu
Requires PHP:      7.0.0
Requires at least: 5.9.0
Tested up to:      6.2
Stable tag:        1.0.9
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

A simple breadcrumb trail block that supports JSON-LD structured data and is compatible with Woocommerce

== Description ==

This is a single-block plugin for the breadcrumb trail. It's simple, lightweight, SEO-friendly, and WooCommerce compatibility. It also includes some simple separator icons.

= How to customize the breadcrumb =

1. [How to change the markup of the block?](https://wordpress.org/support/topic/how-to-change-the-markup-of-the-block/)
2. [How to add/remove the items of the breadcrumb trail?](https://wordpress.org/support/topic/how-to-add-remove-the-items-of-the-breadcrumb-trail/)
3. [How to change the home item of the breadcrumb trail?](https://wordpress.org/support/topic/how-to-change-the-home-item-of-the-breadcrumb-trail/)

If this plugin is useful for you, please do a quick review and [rate it](https://wordpress.org/support/plugin/breadcrumb-block/reviews/#new-post) on WordPress.org to help us spread the word. I would very much appreciate it.

Please check out my other plugins if you're interested:

* [Content Blocks Builder](https://wordpress.org/plugins/content-blocks-builder) - A tool to create blocks, patterns or variations easily for your site directly on the Block Editor.
* [Meta Field Block](https://wordpress.org/plugins/display-a-meta-field-as-block) - A block to display a meta field or an ACF field as a block. It can also be used in the Query Loop block.
* [Block Enhancements](https://wordpress.org/plugins/block-enhancements) - A plugin to add more useful features to blocks like icons, box-shadow, transform, hover style...
* [Icon Separator](https://wordpress.org/plugins/icon-separator) - A tiny block just like the core/separator block but with the ability to add an icon to it.
* [SVG Block](https://wordpress.org/plugins/svg-block) - A block to insert inline SVG images easily and safely. It also bundles with more than 3000 icons and some common non-rectangular dividers.
* [Counting Number Block](https://wordpress.org/plugins/counting-number-block) - A block to display a number that has the number-counting effect.
* [Better Youtube Embed Block](https://wordpress.org/plugins/better-youtube-embed-block) - Embed Youtube videos without slowing down your site.

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

== Changelog ==

= 1.0.9 =
*Release Date 24 Mar 2023*

* FIX - Error converting WP_Post_Type to string. Thanks to @tnchuntic for reporting it.

= 1.0.8 =
*Release Date 20 Mar 2023*

* DEV - Make the block compatible with woocommerce

= 1.0.7 =
*Release Date 11 Mar 2023*

* FIX - Add namespace to class_exists check

= 1.0.6 =
*Release Date 12 Feb 2023*

* Add new hooks `breadcrumb_block_home_text`, `breadcrumb_block_home_url`

= 1.0.5 =
*Release Date 05 Feb 2023*

* DEV - Add a setting to hide the home page link

= 1.0.4 =
*Release Date 19 Jan 2023*

* FIX - Gap issue: adding the semicolon to the CSS variable
* FIX - Could not modify breadcrumb data via the `breadcrumb_block_get_items` filter
* DEV - Add a new arrow icon as separator

= 1.0.3 =
*Release Date 13 Dec 2022*

* DEV - Use post_type->labels->name instead of post_type->labels->singular_name for custom post type archive name
* DEV - Add http://schema.org as @context for structured data
* Note: Big thanks to [Yannick](https://wordpress.org/support/users/ja4st3r) for this release

= 1.0.2 =
*Release Date 08 Dec 2022*

* DEV - Add a setting to hide the current page title

= 1.0.1 =
*Release Date 01 Dec 2022*

* FIX - Syntax error on PHP version 7.0.0

= 1.0.0 =
*Release Date 22 Oct 2022*


