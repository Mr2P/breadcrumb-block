---
name: wp-block-audit
description: Use when the user asks to audit, review, or check this plugin for problems - security, accessibility, coding standards, block.json hygiene, or performance. Runs the project gates and produces a findings report with file/line references.
---

# wp-block-audit

Audit workflow for Breadcrumb Block. Produce findings, not fixes, unless asked.

## 1. Run the gates

`yarn verify` - lint (JS, CSS, parallel-lint, WPCS) + test + build. Record failures verbatim.

## 2. Security review (PHP)

Scope: `breadcrumb-block.php`, `includes/breadcrumbs.php`.

- Output escaping: `esc_html`, `esc_attr`, `esc_url`, `tag_escape` on every dynamic value in markup.
- Known accepted deviations (report as info, not findings): attrs printed via `htmlspecialchars`, JSON-LD printed via `_wp_specialchars` with phpcs ignores.
- Input sanitization on attribute values coming from the editor (`separator`, `labels`, `gap`).
- No raw SQL, no `$_GET`/`$_POST` handling, no regex-based HTML manipulation (use `WP_HTML_Tag_Processor` if HTML editing is ever needed).

## 3. Accessibility review (JS + markup)

Scope: `src/edit.js`, trail markup documented in `context/business-logic.md`.

- WCAG 2.1 AA: heading order, landmarks, keyboard reachability, visible focus, contrast.
- Trail already has `role="navigation"`, `aria-label`, `aria-current="page"` - flag regressions.
- Respect `prefers-reduced-motion` for any animation added to `src/style.scss`.

## 4. Block hygiene

- `src/block.json` is the source of truth; check `build/block.json` matches after a build.
- Attributes documented in `AGENTS.md` match `src/block.json`.
- `build/` assets committed and current (`git status` clean after `yarn build`).

## 5. Report

Table per finding: severity (blocker / warn / info), file:line, issue, suggested fix. End with the gate output summary. Reference hook contracts from `context/business-logic.md` when a finding affects public filters.
