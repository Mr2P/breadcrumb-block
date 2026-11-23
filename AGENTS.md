# Breadcrumb Block - AGENTS.MD

Single-block WordPress breadcrumb plugin. Renders a breadcrumb trail (server-side) with optional JSON-LD structured data and WooCommerce compatibility. Block name: `boldblocks/breadcrumb-block`.

## Project location

`D:/Working/Projects/src/breadcrumb-block`

## What this plugin does

Adds a Gutenberg block that outputs a breadcrumb navigation trail on the front end. The block is configurable from the editor (separator, gap, hide home/current page, custom labels, short title, primary term, taxonomy for single items). The breadcrumb logic runs in PHP and adapts to the current query: home, single post, page, archive, category, tag, date, author, search, 404, attachment, and WooCommerce shop/product/tag/category pages.

## Key files

| File | Role |
|------|------|
| `breadcrumb-block.php` | Plugin entry. Namespace `BreadcrumbBlock`. Registers block on `init` with server render callback. |
| `includes/breadcrumbs.php` | Core business logic: `BreadcrumbBlock\Breadcrumbs` singleton class. |
| `includes/short-title.php` | Short title meta registration, term form UI. |
| `includes/primary-term.php` | Primary term meta registration. |
| `src/block.json` | Block metadata, attributes, supports (source of truth; copied to `build/` by `yarn build`). |
| `src/index.js` | Block registration (`registerBlockType`, no `save` - server-rendered only). |
| `src/edit.js` | Editor UI (InspectorControls: block settings + labels). |
| `src/style.scss` / `src/editor.scss` | Front-end / editor styles. |
| `build/` | Compiled assets, committed to git. Regenerate with `yarn build`; never edit by hand. |
| `context/business-logic.md` | Full business-logic reference (class methods, item shape, hooks, render flow). Read before changing `includes/breadcrumbs.php`. |
| `readme.txt` | WordPress.org readme + changelog + customization examples. |
| `composer.json` / `package.json` | PHP / JS dependency manifests and scripts. |
| `phpcs.xml` | PHPCS ruleset (WordPress + PHPCompatibilityWP). |
| `phpunit.xml.dist` + `tests/php/` | PHPUnit 9 unit tests (Brain Monkey mocks, no WP boot). |

## Commands

Install JS deps: `yarn install`. Install PHP deps: `composer install`.

| Command | Purpose |
|---------|---------|
| `yarn lint` | All lint: JS, CSS, PHP syntax (parallel-lint), WPCS. |
| `yarn test` | All tests: JS unit tests (Jest) + PHP unit tests (PHPUnit + Brain Monkey). |
| `yarn test:js` | JS unit tests only (Vitest via wp-scripts, picks up `src/**/*.test.js`). |
| `yarn test:php` | PHP unit tests only (`tests/php/`, pure unit - no WordPress boot). |
| `yarn verify` | Full gate: `lint` + `test` + `build`. Run before declaring done. |
| `yarn build` | Build block assets (`wp-scripts build`). Required after changing `src/`. |
| `yarn start` | Dev mode with watch (`wp-scripts start`). |
| `yarn format` | Format code (`wp-scripts format`). |
| `yarn lint:js` / `yarn lint:css` | Lint JS / CSS only. |
| `composer run lint:wpcs` | PHPCS on PHP files (WordPress + PHPCompatibilityWP). |
| `composer run lint:php` | PHP parallel-lint. |
| `yarn bump:patch\|minor\|major` | Bump version in `package.json` + all `*.php` files. Supports `--dry`, `--verbose`. |
| `yarn git-tag` | `git add . && git commit` + annotated tag `v{version}` + push tag to origin. |
| `yarn make-pot` | Generate POT translation file. |

PHP unit tests run with PHPUnit 9 + Brain Monkey (pure unit, no WordPress boot, no Docker). The bootstrap is `tests/php/bootstrap.php`; the base test case stubs query conditionals (`stub_query_conditionals`) and filters (`override_apply_filters`). Not scaffolded yet: wp-env / full WordPress test harness for integration tests. Verify PHP changes with `yarn test:php` plus `composer run lint:wpcs`.

## Block render flow

1. `init` -> `breadcrumb_block_block_init()` -> `register_block_type( __DIR__ . '/build', [ 'render_callback' => 'BreadcrumbBlock\breadcrumb_block_render_block' ] )`.
2. `breadcrumb_block_render_block( $attributes, $content, $block )`:
   - Merges `homeText` into `labels.home`.
   - Calls `Breadcrumbs::get_instance()->get_breadcrumb_trail( [ 'separator', 'labels' ] )`.
   - Sets CSS var `--bb--crumb-gap` from `gap` attribute.
   - Adds classes `hide-home-page` / `hide-current-page` when enabled.
   - Returns `<div {wrapper}>{trail}</div>`.
3. Trail markup: `<nav class="breadcrumb" role="navigation">` > `<ol class="breadcrumb-items">` > `<li class="breadcrumb-item breadcrumb-item--current|--parent|--home">` with `<a><span class="breadcrumb-item-name">` and `<span class="sep">` between items.
4. JSON-LD `BreadcrumbList` is generated during rendering and printed once in `wp_footer` as `application/ld+json`.

## Block attributes

| Attribute | Type | Default |
|-----------|------|---------|
| `gap` | string | `.4em` |
| `separator` | string | `/` |
| `hideHomePage` | boolean | `false` |
| `hideCurrentPage` | boolean | `false` |
| `homeText` | string | `""` (legacy, migrated to `labels.home` in editor) |
| `labels` | object | `{}` - keys: `home`, `error404`, `searchResult`, `postsTagged`, `author`, `page`, `productsTagged` |

## Filters & actions (quick reference)

| Hook | Type | Params | Purpose |
|------|------|--------|---------|
| `breadcrumb_block_get_item` | filter | `$item_args, $context, $breadcrumbs` | Modify/remove one item (`return false` to remove). |
| `breadcrumb_block_get_items` | filter | `$items, $breadcrumbs` | Modify full items array. |
| `breadcrumb_block_get_args` | filter | `$args` | Modify trail arguments. |
| `breadcrumb_block_get_breadcrumb_trail` | filter | `$markup, $args, $breadcrumbs` | Modify final HTML. |
| `breadcrumb_block_strip_shortcodes` | filter | `false, $breadcrumbs` | Return `true` to strip shortcodes. |
| `breadcrumb_block_structured_data` | filter | `$markup, $breadcrumbs` | Modify JSON-LD. |
| `breadcrumb_block_product_terms_args` | filter | `$args` | Args for `wc_get_product_terms` on product singles. |
| `breadcrumb_block_main_term` | filter | `$term, $terms, $taxonomy` | Pick the primary category / product category. |
| `breadcrumb_block_get_short_title` | filter | `$short_title, $object` | Replace the item name with the short title from item meta. |
| `breadcrumb_block_get_primary_term` | filter | `$primary_term, $post` | Replace or disable the per-item primary term. |
| `breadcrumb_block_render_breadcrumb_trail` | action | `$breadcrumbs` | Fired during generation (triggers structured data). |
| `breadcrumb_block_home_text` / `breadcrumb_block_home_url` | filter | value | Filter home label/URL. |
| `breadcrumb_block_add_post_type_name` | filter | `true, $post, $breadcrumbs` | Return `false` to hide CPT archive name. |
| `breadcrumb_block_single_prepend` | action | `$post, $breadcrumbs` | Prepend items before single-post trail. |
| `breadcrumb_block_single` / `breadcrumb_block_single_{post_type}` | action | `$post, $breadcrumbs` | Single-post hooks. |

Item shape: `[ (string) name, (string) link, (array) attrs ]`; context is `[ 'type' => ..., 'object' => ... ]`. See `context/business-logic.md` for details.

## WooCommerce behavior

Active when `class_exists( 'woocommerce' )`. Adds shop/product category/product tag trails; product singles prepend the shop page and list the main product category with ancestors. Shop trail is skipped if the front page is the shop.

## Coding standards

- PHP: WordPress Coding Standards + PHPCompatibilityWP. Tabs for PHP (`.editorconfig`), spaces 2 for JS and CSS/SCSS (`.prettierrc.json` + `.stylelintrc.json`). PHPCS `testVersion` is `8.0-`; plugin header says `Requires PHP: 8.0`. PHP 8.x syntax is available (typed properties, constructor promotion, match, null-safe operator).
- Max cyclomatic complexity 10, max nesting level 3 (enforced by `phpcs.xml`).
- Textdomain: `breadcrumb-block`.
- JS: `@wordpress/scripts` ESLint/Prettier config.
- `.editorconfig`: charset utf-8, LF, final newline, trim trailing whitespace (except `.md`).

## Environment facts

- `build/` is committed to git (compiled assets are shipped with the plugin). Regenerate with `yarn build`; do not edit by hand.
- `vendor/` and `node_modules/` are ignored by git. Coding standards come from composer (`wp-coding-standards/wpcs` 3.4.1+; a legacy local `wpcs/` clone was removed because it shadowed the composer standard and broke `phpcs`).
- PHP target: min 8.0, PHPCS compatibility checked for 8.0-. WordPress: requires 7.1, tested up to 7.1.
- Author: Phi Phan (mrphipv@gmail.com). Site: https://boldblocks.net. Repo: https://github.com/mr2p/breadcrumb-block.

## How to release

1. `yarn bump:patch` (or minor/major) - updates version in `package.json` and all `*.php` files.
2. Update `readme.txt` "Stable tag" and add changelog entry.
3. `yarn build` to rebuild assets.
4. `yarn git-tag` - commits everything, tags `vX.Y.Z`, pushes the tag to origin.

## Skills

WordPress project: load the global `wp-dev` skill before coding (global skills live in `~/.agents/skills/phi/`). Also global: `wp-block-tdd` (test surface and helpers), `spec-driven` (specs in `context/specs/`), `code-review` (audit workflow).

## Notes for agents

- Changing `includes/breadcrumbs.php`? Read `context/business-logic.md` first - it documents every method, item context type, and hook.
- Changing `src/`? Run `yarn build` so `build/` stays in sync.
- Security baseline: escape everything on output (`esc_html`, `esc_attr`, `esc_url`), sanitize on input, use `WP_HTML_Tag_Processor` for HTML manipulation (never regex on HTML).
- Accessibility: WCAG 2.1 AA for editor UI; the front-end trail already uses `role="navigation"`, `aria-label`, and `aria-current="page"`.
