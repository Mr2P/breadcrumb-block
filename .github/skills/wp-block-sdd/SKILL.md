---
name: wp-block-sdd
description: Use when the user wants to design or specify a change before coding - write or update a spec in context/, get explicit approval, then hand off to wp-block-tdd. Covers where specs live and what they must contain for this plugin.
---

# wp-block-sdd

Spec-driven development for Breadcrumb Block. Spec first, code second, approval in between.

## Spec location

- Features and behavior changes: `context/specs/<slug>.md` (create the folder if needed).
- Changes to existing behavior: update `context/business-logic.md` in the same PR as the spec diff.

## Spec template

```md
# <Feature name>

## Problem
What is wrong or missing, from the user's perspective.

## Behavior
Exact expected behavior: which trail items, labels, links, classes, and context types.
Name the affected query types (single, page, category, shop, ...).

## Hooks contract
New/changed filters or actions, with params and expected return values.
Follow the existing naming: breadcrumb_block_*.

## Out of scope
Explicit non-goals.

## Acceptance criteria
Checkable statements. Each becomes at least one test (see wp-block-tdd).
```

## Rules

1. Read `context/business-logic.md` and `AGENTS.md` before writing - reuse existing item shapes, context types, and hook naming.
2. Keep the block server-rendered: prefer PHP behavior + filters over editor-side logic.
3. Present the spec, then wait for explicit approval. No implementation before approval.
4. After approval, hand off to the wp-block-tdd skill: failing test from an acceptance criterion, then implement, then `yarn verify`.
5. Update `context/business-logic.md` and `AGENTS.md` (hooks table, attributes) as part of the change, not as an afterthought.
