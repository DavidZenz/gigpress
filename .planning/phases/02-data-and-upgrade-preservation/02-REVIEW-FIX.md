---
phase: 02-data-and-upgrade-preservation
fixed_at: 2026-10-04T15:40:40Z
review_path: .planning/phases/02-data-and-upgrade-preservation/02-REVIEW.md
iteration: 2
findings_in_scope: 2
fixed: 2
skipped: 0
status: all_fixed
---

# Phase 02: Code Review Fix Report

**Fixed at:** 2026-10-04T15:40:40Z
**Source review:** `.planning/phases/02-data-and-upgrade-preservation/02-REVIEW.md`
**Iteration:** 2

**Summary:**

- Findings in scope: 2
- Fixed: 2
- Skipped: 0

## Fixed Issues

### WR-01: Schema migration coverage accepts incorrect column definitions

**Files modified:** `tests/compat/probe.php`, `tests/compat/upgrade-preservation-migrations.php`
**Commit:** 43f5df2
**Applied fix:** Reconstructed schemas now use the production types, nullability, defaults, and auto-increment attributes for columns present at each version. Final assertions compare every `Field`, `Type`, `Null`, `Key`, `Default`, and `Extra` value with the canonical `admin/db.php` definitions. A same-name `show_tour_id` column with an incorrect type and default is required to fail that comparison.

### WR-02: PHP 8.3 null normalization is bypassed by missing optional request fields

**Files modified:** `admin/handlers.php`, `tests/compat/probe.php`, `tests/compat/run.sh`, `tests/compat/upgrade-preservation-crud.php`
**Commit:** 592d868
**Applied fix:** Show, new-artist, and optional venue fields use null-safe POST defaults before normalizing. The `optional-request-fields` compatibility case omits every optional show and venue input, creates a show and venue through `gigpress_add_show`, verifies empty stored values, and asserts that the request emitted no PHP warnings.

## Verification

All checks ran in the main checkout through the repository's OrbStack Compose compatibility runner.

- `matrix --scenario upgrade-preservation --case optional-request-fields --wp-lines 7.1 --php-branches 8.3` — PASS (WordPress 7.1.2, PHP 8.3.35, zero plugin warnings)
- `matrix --scenario upgrade-preservation --case all --wp-lines 7.1 --php-branches 8.3` — PASS (all 11 required preservation cases)

---

_Fixed: 2026-10-04T15:40:40Z_
_Fixer: the agent (gsd-code-fixer)_
_Iteration: 2_
