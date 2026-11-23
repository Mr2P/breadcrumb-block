# Breadcrumb short title

## Problem

Long post and term titles make the breadcrumb trail unwieldy. Editors need a way to show a shorter label for an item in the trail while the item keeps its real title everywhere else (page title, H1, archives, menus, SEO tags).

## Behavior

- New meta key `breadcrumb_short_title` (string, single, sanitized) for **all public post types** and **all public taxonomies**.
- When set on an item: the visible trail **and** the JSON-LD `BreadcrumbList` show the short title (Google defines `name` as "the title of the breadcrumb displayed for the user"; schema must match the visible trail).
- When empty or missing: current behavior, byte-identical.
- Applied centrally in `add_item()`, after `wp_strip_all_tags` and before the `breadcrumb_block_get_item` filter - so singles, parent chains, ancestor terms, and category items all pick it up, and third-party filters see the effective name.
- Editor UI: a "Breadcrumb short title" panel (`PluginDocumentSettingPanel`) on public post types using the block editor; renders only where the meta is registered.
- Term UI: fields on the add-term and edit-term forms for public taxonomies (classic admin forms), with nonce + `manage_terms` capability checks on save.
- Filter: `breadcrumb_block_get_short_title` ( `$short_title, $object` ) - override or disable per item; return `''` to keep the real title. This filter is also the documented migration path from Yoast (`_yoast_wpseo_bctitle`).

## Contracts

- Filter `breadcrumb_block_get_short_title` ( string $short_title, \WP_Post|\WP_Term|null $object ): string.
- Meta key `breadcrumb_short_title` on posts and terms (registered with `show_in_rest` for posts so the sidebar panel can persist).

## Out of scope

Auto-truncation. Yoast fallback as built-in behavior (the filter snippet is the migration path). Classic non-block product metabox UI. Short titles for home, search, date, and paged items (no object; home has its label setting). Scrollable trail (v2).

## Acceptance criteria

1. Short title meta on a post → trail shows the short title, JSON-LD `name` = short title, real title absent from trail markup.
2. Same for a term.
3. Empty or missing meta → trail identical to current behavior.
4. The filter overrides the meta value per item; returning `''` disables the short title.
5. Term form saves and clears the field with nonce + capability checks; saving an empty value deletes the meta.
6. The sidebar panel renders only where the meta is registered (registered post types).
