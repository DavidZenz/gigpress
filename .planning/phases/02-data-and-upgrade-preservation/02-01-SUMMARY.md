---
phase: 02-data-and-upgrade-preservation
plan: "01"
subsystem: database
tags: [wordpress, wpdb, migrations, compose, data-preservation]
requires:
  - phase: 01-compatibility-baseline-and-menu-diagnosis
    provides: PHP 8.3 runtime guard and disposable WordPress compatibility harness
provides:
  - Verified 1.4-to-1.6 coordinator with a durable retry journal
  - Blocked-upgrade bootstrap gate and capability-scoped notice
  - Reconstructed prefix-aware preservation, interruption, and metadata test cells
affects: [02-02, 02-03, 02-04, database-upgrades]
tech-stack:
  added: []
  patterns: [final-marker-last, durable-upgrade-journal, prefix-aware-compose-cell]
key-files:
  created:
    - tests/compat/fixtures/upgrade-preservation/1.4.php
  modified:
    - admin/db.php
    - gigpress.php
    - tests/compat/compose.yaml
    - tests/compat/probe.php
    - tests/compat/run.sh
key-decisions:
  - "Treat a database version as completion evidence only after each required postcondition and marker read-back succeeds."
  - "Block table-bearing databases with missing, malformed, unknown, or newer metadata instead of guessing or reinstalling them."
  - "Use an isolated nondefault-prefix Compose cell and label its source evidence reconstructed because no live database was supplied."
requirements-completed: [DATA-01]
actuals:
  tokens: 8280
  tasks: 2
  commits: 3
commits: 3
plan_head_before: 11a0859b9fc339c6e56609621d5dd8da3e173795
plan_head_after: c3de1eea6547c38f24ca5f660cf3e3826e443e0f
duration: 12min
completed: 2026-10-04
status: complete
coverage:
  - id: D1
    description: Reconstructed populated 1.4 databases reach 1.6 with IDs, relationships, settings, trash state, linked post, and prefix preserved across repeat loads.
    requirement: DATA-01
    verification:
      - kind: integration
        ref: rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case tracer-1.4
        status: pass
    human_judgment: false
  - id: D2
    description: Interrupted and unsafe upgrades retain their durable evidence, expose only an authorized notice, and recover without duplicate records.
    requirement: DATA-01
    verification:
      - kind: integration
        ref: rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case safety-1.4
        status: pass
      - kind: integration
        ref: rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case metadata-classification
        status: pass
    human_judgment: false
---

# Phase 02 Plan 01: Safe representative upgrade summary

**A prefix-aware, reconstructed 1.4-to-1.6 WordPress upgrade path with verified completion, retry evidence, and a blocked normal surface for unsafe database state.**

## Performance

- **Duration:** 12 min
- **Started:** 2026-10-04T12:55:15Z
- **Completed:** 2026-10-04T13:07:10Z
- **Tasks:** 2/2
- **Files modified:** 7

## Accomplishments

- Replaced the unchecked load-time upgrade switch with `gigpress_db_bootstrap()`, which classifies fresh, current, representative legacy, and unsafe state before mutation.
- Added a separate `gigpress_upgrade_state` journal, postcondition checks, and final-version read-back before journal removal.
- Gated normal GigPress modules on the coordinator result and limited the actionable blocked-state notice to users with `activate_plugins`.
- Added a Compose-only reconstructed fixture with nontrivial IDs, active and trashed shows, relationships, settings, a linked post, and a nondefault prefix.
- Added three focused runner cases for successful preservation, every representative interruption/retry boundary, and unsafe metadata classification.

## Task Commits

1. **Task 1 RED: upgrade-preservation contract** — `07d7d29` (`test`)
2. **Task 1 GREEN: verified 1.4 coordinator** — `6093db4` (`feat`)
3. **Task 2: blocked recovery coverage** — `c3de1ee` (`test`)

## Validation

- `tracer-1.4` passed on WordPress 7.1.2 / PHP 8.3.
- `safety-1.4` passed, including schema, data, and final-marker interruption points with retry.
- `metadata-classification` passed for fresh, absent, malformed, unrecognized, and newer marker states plus notice authorization and bootstrap gating.
- PHP syntax checks passed for `admin/db.php`, `gigpress.php`, and `tests/compat/probe.php`; `bash -n tests/compat/run.sh` passed.

## Decisions Made

- Settings defaults are merged only when `array_key_exists()` confirms a key is absent, preserving empty, falsey, and unknown settings.
- A failed or unprovable operation leaves the final `db_version` untouched and preserves the durable journal for a safe retry.
- The test cell configures WordPress's table prefix through Compose only for this scenario; all fixture writes remain inside its disposable database.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Escaped the WordPress table-prefix variable in Compose config.**
- **Found during:** Task 1 verification
- **Issue:** Compose interpolated the PHP `$table_prefix` assignment before WordPress could evaluate it.
- **Fix:** Escaped the variable as `$$table_prefix`.
- **Files modified:** `tests/compat/compose.yaml`
- **Verification:** The nondefault-prefix tracer cell passed.

**2. [Rule 2 - Missing critical functionality] Made the existing Compose harness configurable for the required nondefault prefix.**
- **Found during:** Task 1 fixture design
- **Issue:** The test could not prove prefix-aware persistence with the fixed default WordPress prefix.
- **Fix:** Added the narrow `COMPAT_TABLE_PREFIX` configuration path used only by the upgrade-preservation cell.
- **Files modified:** `tests/compat/compose.yaml`, `tests/compat/run.sh`
- **Verification:** All three preservation cells passed with `compat_legacy_`.

## Known Stubs

None.

## Next Phase Readiness

Plans 02-02 and 02-04 can extend the coordinator and fixture contract to the remaining historical versions. The 1.0–1.3 branches are deliberately blocked as `legacy_path_unproven` until their source fixtures and verified transforms are added.

## Self-Check: PASSED

All seven declared files exist and commits `07d7d29`, `6093db4`, and `c3de1ee` are reachable in Git history.

---
*Phase: 02-data-and-upgrade-preservation*
*Completed: 2026-10-04*
