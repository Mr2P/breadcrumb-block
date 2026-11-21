---
name: wp-block-tdd
description: Use when changing behavior in this plugin (PHP logic in includes/ or JS in src/) - write the failing test first, commit RED, implement GREEN, then run the verify gate. Covers the project's test commands and what to do when the PHP harness is missing.
---

# wp-block-tdd

TDD workflow for Breadcrumb Block. Rule: no behavior change without a failing test committed first.

## Test surface today

- JS: `yarn test:js` (Vitest via `@wordpress/scripts`, picks up `src/**/*.test.js`).
- PHP: `yarn test:php` (PHPUnit 9 + Brain Monkey, pure unit tests in `tests/php/`, no WordPress boot). Mock WP functions with Brain Monkey; query conditionals via `stub_query_conditionals()`, filters via `override_apply_filters()`. Keep tests WPCS-clean: `phpcs` scans `tests/` (PSR-4 file names allowed there).

## RED

1. Write the smallest test that fails for the right reason.
2. Place it next to the code: `src/<file>.test.js` for JS.
3. Run the test, capture the failure output.
4. Commit the test alone with a `test:` prefix message. The commit must fail without the implementation.

## GREEN

1. Implement the minimum change that makes the test pass.
2. Changing `includes/breadcrumbs.php`? Read `context/business-logic.md` first and keep it in sync.
3. Changing `src/`? Run `yarn build` so the committed `build/` assets stay in sync.

## Verify

1. `yarn verify` (lint + test + build). All green before declaring done.
2. Report evidence: the failing output from RED and the passing output from GREEN.
3. No em dashes in code, comments, or docs. Plain dash only.
