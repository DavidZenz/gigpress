---
phase: 01-compatibility-baseline-and-menu-diagnosis
plan: "02"
subsystem: compatibility-diagnostics
tags: [wordpress, php, docker-compose, admin-menu, evidence]
requires: [01-01]
provides:
  - Request-local menu-order trace with callback identity and priority evidence
  - Controlled exact-key separator-gigpress fixture for diagnostic-only reproduction
  - Repository-boundary record separating fixture, checkout, historical, and live-site evidence
affects: [01-04, 01-05]
tech-stack:
  added: []
  patterns: [request-local diagnostic trace, disposable Compose evidence cell, fixture-scoped attribution]
key-files:
  created: [tests/compat/diagnostics/menu-trace.php, tests/compat/fixtures/menu-conflict-plugin.php, .planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-DIAGNOSIS.md]
  modified: [tests/compat/run.sh, tests/compat/probe.php]
key-decisions:
  - "The exact separator-gigpress reproduction is attributed only to the controlled fixture callback at priority 20."
  - "GigPress's repository-visible separator-gp mutation is independently actionable, while the unavailable live callback identity remains unproven."
actuals:
  tokens: 4154
  tasks: 2
  commits: 2
duration: 8min
completed: 2026-10-03
commits: 2
plan_head_before: 8cde7d92937c427c82d7067ef111ae71a58863a9
plan_head_after: e30375c9273763c673e744224725375393cdafc0
status: complete
---

# Phase 01 Plan 02: Compatibility Baseline and Menu Diagnosis Summary

**A disposable, request-local WordPress menu trace that attributes the controlled `separator-gigpress` fixture while preserving the boundary around the unavailable live installation.**

## Performance

- **Duration:** 8 min
- **Started:** 2026-10-03T20:52:42Z
- **Completed:** 2026-10-03T21:00:42Z
- **Tasks:** 2
- **Files modified:** 5

## Accomplishments

- Added `diagnose-menu`, which runs a disposable WordPress cell, returns only request-local JSON evidence, and rejects unsupported diagnostic inputs.
- Traced each `menu_order` callback's identity, priority, input and returned ordering; captured controlled row creation and redacted final menu evidence with the deployed GigPress version and hash.
- Reproduced the synthetic `separator-gigpress` order-map mismatch through `gigpress_menu_conflict_late_add` at priority 20 on PHP 8.2 and PHP 8.3.
- Recorded that the checkout's `separator-gp` mutation, the historical report, the synthetic fixture, and the unavailable production callback are separate evidence classes.

## Verification

- `diagnose-menu` with the exact-key fixture passed on WordPress 7.1.2 with diagnostic-only PHP 8.2 and supported PHP 8.3.
- Checkout-only `diagnose-menu` paths passed on the same PHP 8.2 and PHP 8.3 cells and retained the checkout's `separator-gp` evidence.
- The repository-boundary assertion passed against `01-DIAGNOSIS.md` and confirmed no historical `separator-gigpress` occurrence in `gigpress.php`.

## Task Commits

1. **Task 01-02-01: Attribute the local warning class and validate the trace** - `cd187d6` (feat)
2. **Task 01-02-02: Establish the repository attribution boundary** - `e30375c` (docs)

## Decisions Made

- Attribute the exact reported key only to a controlled fixture, never to the unavailable live site.
- Preserve the D-04 correction path: retain the after-Comments placement only when safe and otherwise return standard WordPress order.

## Deviations from Plan

### Auto-fixed Issues

1. **[Rule 3 - Blocking] Used the existing plugin-mounted repository path for diagnostic deployment**
   - **Found during:** Task 01-02-01
   - **Issue:** The existing Compose file mounts fixtures at `/compat` but does not mount the new diagnostics directory there.
   - **Fix:** The runner copies diagnostic and fixture source from the read-only GigPress plugin mount into the disposable WordPress paths.
   - **Files modified:** `tests/compat/run.sh`

2. **[Rule 1 - Bug] Added the required WordPress plugin header to the fixture**
   - **Found during:** Task 01-02-01
   - **Issue:** WordPress rejected the first fixture draft because it had no plugin header.
   - **Fix:** Added a minimal header so the disposable cell can activate the controlled fixture.
   - **Files modified:** `tests/compat/fixtures/menu-conflict-plugin.php`

## Known Stubs

None.

## Self-Check: PASSED

- Confirmed every task artifact and this summary exist.
- Confirmed task commits `cd187d6` and `e30375c` are present in Git history.
