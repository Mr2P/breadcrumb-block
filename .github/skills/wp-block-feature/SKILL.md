---
name: wp-block-feature
description: Use when the user asks to add or change a feature in this plugin end to end - spec, tests, implementation, docs, and release prep. Orchestrates wp-block-sdd and wp-block-tdd plus the project's docs and release steps.
---

# wp-block-feature

End-to-end feature workflow for Breadcrumb Block.

## 0. Orient

- Read `AGENTS.md` and `context/business-logic.md`.
- Confirm the target: block behavior (PHP), editor UI (`src/edit.js`), styles, or a combination.

## 1. Spec (wp-block-sdd)

Write `context/specs/<slug>.md`, present it, wait for approval.

## 2. Tests (wp-block-tdd)

One failing test per acceptance criterion. Commit RED first. PHP harness note in wp-block-tdd applies.

## 3. Implement

- PHP in `includes/breadcrumbs.php` / `breadcrumb-block.php`; follow WPCS, tabs, max complexity 10, max nesting 3, PHP 7.0-compatible syntax.
- JS in `src/`; 2-space indent, `@wordpress/components` and data API conventions, no inline styles in React (use `style.scss` + CSS custom properties).
- Keep `yarn build` output in sync (build is committed).

## 4. Gates

`yarn verify` - all green, paste the evidence.

## 5. Docs (part of done)

- `context/business-logic.md`: methods, hooks, context types touched.
- `AGENTS.md`: hooks table / attributes / commands if changed.
- `readme.txt`: changelog entry under Unreleased or the next version.

## 6. Release prep (only if asked to release)

1. `yarn bump:patch|minor|major`.
2. Move the changelog to the new version, update `Stable tag` in `readme.txt`.
3. `yarn build`, then `yarn git-tag` (commits all, tags `v{version}`, pushes the tag).
