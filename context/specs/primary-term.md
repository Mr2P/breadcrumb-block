# Primary term selection (two-level)

## Problem

Single-item trails always show the first category (posts) or the first product category (products). Items with multiple terms get an arbitrary pick, hierarchical CPT taxonomies (documentation sites) never appear in trails, and there is no editor control - Yoast-parity is the expected standard.

## Behavior

Two levels, Yoast-style:

1. **Block setting** (attribute `taxonomy`, default `""` = Auto): which taxonomy feeds single-item trails. Auto = current behavior (`category` for posts, `product_cat` for products). An explicit choice (public hierarchical taxonomies) makes single-item trails use that taxonomy's term path - term + ancestors - for **any** post type. When the configured taxonomy has no assigned terms for the current item, the trail falls back to the Auto behavior (never empty).
2. **Per-item primary term** (post meta `breadcrumb_primary_term`, integer term id, registered on public post types): when set **and** the term is assigned to the post, the trail and the JSON-LD lead through that term (any taxonomy of the post, ancestors included) - overriding both the Auto default and the block setting. Empty/zero/not-assigned → Level 1 applies.

The JSON-LD inherits both levels automatically: the structured data is generated from the same items array during the same render pass that consumes the block setting (schema exists exactly where the visible trail exists).

UI: the sidebar panel becomes a single "Breadcrumb" panel with the Short title field and the Primary term dropdown (the post's assigned terms of hierarchical taxonomies, labeled with the taxonomy name; a "Default" option clears the override).

## Contracts

- Filter `breadcrumb_block_get_primary_term` ( ?\WP_Term $primary_term, \WP_Post $post ): ?\WP_Term - override the resolved primary term; return `null` to disable and fall back to the default path.
- `breadcrumb_block_main_term` still picks the default term on the default path; it does **not** run when a primary term is selected (the primary selection is final - filter that via `breadcrumb_block_get_primary_term`).
- Meta: `breadcrumb_primary_term` (integer) on public post types; `breadcrumb_short_title` unchanged.

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
