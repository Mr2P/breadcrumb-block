# Primary term selection (two-level)

## Problem

Single-item trails always show the first category (posts) or the first product category (products). Items with multiple terms get an arbitrary pick, hierarchical CPT taxonomies (documentation sites) never appear in trails, and there is no editor control - Yoast-parity is the expected standard.

## Behavior

Two levels, Yoast-style:

1. **Site level** (Settings > Breadcrumb Block): a per-post-type taxonomy mapping - each public post type (that has hierarchical public taxonomies) gets a dropdown to choose the taxonomy shown for its single-item trails. Default = Auto (no mapping): `category` for posts, `product_cat` for products, no term path for other types. A mapped taxonomy without assigned terms for an item falls back to the type default (the trail is never emptied). Stored in the `breadcrumb_block_taxonomies` option.
2. **Per-item primary term** (post meta `breadcrumb_primary_term`, integer term id, registered on public post types): when set **and** the term is assigned to the post **and** the term belongs to the mapped taxonomy, the trail and the JSON-LD lead through that term (ancestors included) - overriding the site-level mapping. Otherwise the site-level mapping applies.

The JSON-LD inherits both levels automatically: the structured data is generated from the same items array during the same render pass (schema exists exactly where the visible trail exists).

UI: a Settings > Breadcrumb Block screen with one dropdown per public post type (listing that type's hierarchical public taxonomies, plus "Default"), and the sidebar panel becomes a single "Breadcrumb" panel with the Short title field and the Primary term dropdown (the post's assigned terms of the mapped taxonomy; a "Default" option clears the override). The panel reads the site mapping - no per-template resolution needed.

## Contracts

- Filter `breadcrumb_block_get_primary_term` ( ?\WP_Term $primary_term, \WP_Post $post ): ?\WP_Term - override the resolved primary term; return `null` to disable and fall back to the default path.
- `breadcrumb_block_main_term` still picks the default term on the default path; it does **not** run when a primary term is selected (the primary selection is final - filter that via `breadcrumb_block_get_primary_term`).
- Meta: `breadcrumb_primary_term` (integer) on public post types; `breadcrumb_short_title` unchanged.
- Option: `breadcrumb_block_taxonomies` (object, post type => taxonomy slug), sanitized against each type's hierarchical public taxonomies.
- The block attribute `taxonomy` is removed (superseded by the site-level mapping).

## Out of scope

Multiple taxonomies in one trail. Non-hierarchical (tag) primary terms in the picker (the server accepts any assigned term via meta/filter, but the picker lists hierarchical taxonomies only). Yoast meta reading as built-in behavior. Scrollable trail (v2).

## Acceptance criteria

1. Auto + no primary → current behavior byte-identical (first category for posts, first product category for products).
2. Primary term set and assigned → trail + JSON-LD lead through that term and its ancestors, regardless of the block taxonomy setting.
3. Primary term not assigned to the post → ignored, default path.
4. Block taxonomy set + terms assigned → trail uses that taxonomy (term + ancestors), for any post type (the CPT unlock).
5. Block taxonomy set but no assigned terms → falls back to the Auto behavior.
6. The primary term filter can disable (`null`) or swap the primary term.
7. `breadcrumb_block_main_term` does not run when a primary term is selected (documented contract).
