# Breadcrumb Block - Business Logic Reference

Complete reference for `includes/breadcrumbs.php` (`BreadcrumbBlock\Breadcrumbs`). Verified against the code; update this file whenever the class changes.

## Class overview

`Breadcrumbs` is a singleton. Access it with `Breadcrumbs::get_instance()`. State:

| Property | Type | Purpose |
|----------|------|---------|
| `$items` | array | Crumb items for the current trail. Each item: `[ (string) name, (string) link, (array) attrs ]`. |
| `$args` | array | Parsed trail arguments (see `parse_args`). |
| `$structured_data` | array\|null | BreadcrumbList markup array generated during render (encoded to JSON in `wp_footer`). |

Constructor hooks:

- `breadcrumb_block_render_breadcrumb_trail` (action) -> `generate_breadcrumb_structured_data()` - builds the JSON-LD whenever a trail renders.
- `wp_footer` -> `output_structured_data()` - prints the JSON-LD once per page.

## Lifecycle

`get_breadcrumb_trail( $args = [] )` is the only entry point (called by the block render callback, or directly by themes):

1. `parse_args( $args )` - merges with defaults, fills default labels, applies `breadcrumb_block_get_args` filter.
2. `reset()` - clears old items (important: the instance is a singleton shared across renders).
3. `generate()` - builds `$items` from the current query.
4. Renders the trail HTML.
5. Applies `breadcrumb_block_strip_shortcodes` filter (default `false`; return `true` to run `strip_shortcodes()` on the markup).
6. Applies `breadcrumb_block_get_breadcrumb_trail` filter on the final HTML.
7. Fires `breadcrumb_block_render_breadcrumb_trail` action (triggers structured data generation).
8. Returns the string, or echoes it (and returns an empty string) when `$args['echo']` is `true`.

### Default args (`parse_args`)

| Key | Default | Notes |
|-----|---------|-------|
| `container` | `nav` | Wrapping element. |
| `before` / `after` | `''` | Raw markup around the list. |
| `list_tag` | `ol` | List element. |
| `item_tag` | `li` | Item element. |
| `item_before` / `item_after` | `''` | Raw markup inside each item (currently unused by the block). |
| `separator` | `''` | Block passes the `separator` attribute. |
| `aria_label` | `Breadcrumbs` (translatable) | `aria-label` on the container. |
| `labels` | `[]` | See labels below. |
| `echo` | `false` | Echo instead of return. |

### Default labels (`parse_labels`)

Empty label keys are filled with these defaults (all translatable, textdomain `breadcrumb-block`):

| Key | Default | Used on |
|-----|---------|---------|
| `home` | `Home` | Front page item. |
| `error404` | `Page not found` | 404 trail. |
| `searchResult` | `Search results for “%s”` | Search trail. |
| `postsTagged` | `Posts tagged “%s”` | Tag trail. |
| `author` | `Author: %s` | Author trail. |
| `page` | `Page %d` | Paged trail. |
| `productsTagged` | `Products tagged “%s”` | Product tag trail. |

## `add_item( string $name, string $link = '', array $attrs = [], array $context = [] ): void`

1. Runs `wp_strip_all_tags( $name )`.
2. Applies the `breadcrumb_block_get_item` filter with `[ $name, $link, $attrs ]`, the `$context`, and the instance.
3. If the filtered value is truthy, appends it to `$items`; a `false` return removes the item.

Note: internal call sites pass `[]` for attrs (an older version passed `false`; both are falsy, so markup is identical - only the value filter callbacks see changed).

Item context (3rd arg to filters, `$context`): `[ 'type' => string, 'object' => WP_Post|WP_Term|WP_Post_Type|null ]`.

Context `type` values produced by the class: `front_page`, `home`, `404`, `attachment`, `single`, `page`, `post` (non-page parent pages), `term`, `taxonomy`, `post_type_archive`, `shop`, `date_year`, `date_month`, `date_day`, `author`, `search`, `paged`.

## `generate()` - which trail runs

Conditional checks, in order (first match wins):

1. Without WooCommerce: `is_home`, `is_404`, `is_attachment`, `is_single`, `is_page`, `is_post_type_archive`, `is_category`, `is_tag`, `is_author`, `is_date`, `is_tax`.
2. With WooCommerce (`class_exists( 'woocommerce' )`), `is_product_category`, `is_product_tag`, `is_shop` are spliced in before `is_page`.
3. `add_crumbs_single()` handles attachment and single posts; `add_crumbs_attachment()` calls it for the parent first.

Guard: the whole trail is skipped on the front page, except when the query is paged. It also runs when WooCommerce is inactive or the shop is not the front page. After the matched trail, `search_trail()` and `paged_trail()` append search/pagination items.

Finally applies the `breadcrumb_block_get_items` filter to the whole array.

## Trail builders

| Method | Trail | Item links | Context types |
|--------|-------|------------|---------------|
| `add_crumbs_front_page` | Home label + URL. Filters: `breadcrumb_block_home_text`, `breadcrumb_block_home_url`. Attrs `rel="home"` (converted to the `--home` class in markup). | yes | `front_page` |
| `add_crumbs_home` | Blog posts page title (when home is a static page). `aria-current="page"`. | no | `home` |
| `add_crumbs_404` | `error404` label. `aria-current="page"`. | no | `404` |
| `add_crumbs_attachment` | Parent trail via `add_crumbs_single( parent )`, then the attachment title. | yes | `attachment` |
| `add_crumbs_single` | See breakdown below. | yes | `single` |
| `add_crumbs_page` | Parent pages via `add_parent_crumbs`, then the page title. | yes | `page` |
| `add_crumbs_post_type_archive` | Post type plural label + archive link. | yes | `post_type_archive` |
| `add_crumbs_category` | Category ancestors (`term_ancestors`) when parented, then the category name. | yes | `term` |
| `add_crumbs_tag` | `postsTagged` label. | yes | `term` |
| `add_crumbs_date` | Year, month, then day (day has no link). | year/month | `date_year`, `date_month`, `date_day` |
| `add_crumbs_tax` | Taxonomy plural label (no link), term ancestors, then the term. | term only | `taxonomy`, `term` |
| `add_crumbs_author` | `author` label with display name, no link. | no | `author` |
| `search_trail` | `searchResult` label, link is the search URL without `paged`. | yes | `search` |
| `paged_trail` | `page` label with the page number, no link. | no | `paged` |

### `add_crumbs_single( $post_id = 0, $permalink = '' )` breakdown

1. Fires `breadcrumb_block_single_prepend` action (add items before everything).
2. **Products** (`product` post type, WooCommerce only):
   - `prepend_shop_page()` - adds the shop page when the product permalink base contains the shop slug and the shop is not the front page.
   - Main `product_cat` term from `wc_get_product_terms` (args filtered by `breadcrumb_block_product_terms_args`, default `orderby=parent, order=DESC`); term picked through the `breadcrumb_block_main_term` filter; its ancestors via `term_ancestors`; then the main term itself.
3. **Other hierarchical/custom post types** (not `post`, not `product`):
   - Post type archive item when `$post_type->has_archive` (skippable via the `breadcrumb_block_add_post_type_name` filter).
   - Parent chain via `add_parent_crumbs`.
4. **Posts**: first category (primary via `breadcrumb_block_main_term`), its ancestors, then the category.
5. Fires `breadcrumb_block_single` and `breadcrumb_block_single_{post_type}` actions.
6. Adds the post title as the current item (`aria-current="page"`).

### Helpers

- `add_parent_crumbs( $parent_id )` - walks the `post_parent` chain, stops at the front page, collects then reverses so the topmost ancestor comes first. Context `page` for pages, `post` otherwise.
- `term_ancestors( $term_id, $taxonomy )` - `get_ancestors()` reversed, each ancestor added with context `term`.
- `prepend_shop_page()` - see products above. Context `shop`.
- `add_crumbs_shop` - shop page title (or product post type label) + product archive link; returns early when the shop is the front page.

## Trail markup

Built in `get_breadcrumb_trail` (only when there is at least one item); the markup of each `<li>` is produced by the `get_breadcrumb_item( $crumb, $key, $item_count, $args )` helper:

```
<nav role="navigation" aria-label="{aria_label}" class="breadcrumb">
  {before}
  <ol class="breadcrumb-items">
    <li class="breadcrumb-item [--current|--parent|--home]" {attrs}>
      <a href="{link}"><span class="breadcrumb-item-name">{name}</span></a>
      <span class="sep">{separator}</span>   <!-- non-last items only, when separator is set -->
    </li>
    ...
  </ol>
  {after}
</nav>
```

Rules:

- The last item (`--current`) is never a link; the second-to-last gets `--parent`.
- An item attr `rel="home"` becomes the `breadcrumb-item--home` class and is removed from the output attributes.
- Custom attrs are printed as `key="value"` with `htmlspecialchars` (note for maintainers: not `esc_attr`; values come from trusted code/filter authors).
- Item names are escaped with `esc_html`, links with `esc_url`.

## Structured data

`generate_breadcrumb_structured_data( $breadcrumbs )` builds:

```
{
  "@context": "http://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    { "@type": "ListItem", "position": 1, "item": { "name": "...", "@id": "https://..." } }
  ]
}
```

- `position` is the 1-based array index.
- `@id` is the crumb link; for linkless crumbs it falls back to the current request URL (from `$_SERVER['HTTP_HOST']` + `REQUEST_URI`, `https` when `$_SERVER['HTTPS']` is non-empty, passed through `esc_url_raw`).
- Filtered by `breadcrumb_block_structured_data( $markup, $breadcrumbs )`.
- `output_structured_data()` prints it in `wp_footer` as `<script type="application/ld+json">` with `_wp_specialchars( ..., ENT_NOQUOTES, 'UTF-8', true )`.

## Block render callback (`breadcrumb-block.php`)

`breadcrumb_block_render_block( $attributes, $content, $block )`:

1. Legacy merge: `attributes.homeText` becomes `labels.home` when no custom home label is set.
2. Calls `get_breadcrumb_trail( [ 'separator' => attributes.separator, 'labels' => attributes.labels ] )`.
3. Inline style `--bb--crumb-gap:{gap}` when `gap` is set; classes `hide-home-page` / `hide-current-page` from the boolean attributes (CSS hides those items on the front end).
4. Wraps everything: `sprintf( '<div %1$s>%2$s</div>', get_block_wrapper_attributes( ... ), $trail )`.

Note: hiding home/current page is CSS-based (`hide-home-page` / `hide-current-page` classes), while the JSON-LD still contains all items.
