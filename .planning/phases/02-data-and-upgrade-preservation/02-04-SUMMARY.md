---
phase: 02-data-and-upgrade-preservation
plan: "04"
subsystem: compatibility-testing
tags: [wordpress, php, compose, data-preservation, upgrade-testing]
requires:
  - phase: 02-01
    provides: reconstructed upgrade fixtures and safety cases
  - phase: 02-02
    provides: version and settings preservation cases
  - phase: 02-03
    provides: post-upgrade lifecycle and relationship cases
provides:
  - fail-closed aggregate upgrade-preservation cell
  - supported WordPress/PHP preservation and workflow evidence
  - machine-readable preservation evidence validator
affects: [phase-03, phase-04, phase-05, release-compatibility]
tech-stack:
  added: []
  patterns: [explicit-case-registry, isolated-child-probes, machine-checked-markdown-evidence, compose-runtime-matrix]
key-files:
  created:
    - .planning/phases/02-data-and-upgrade-preservation/02-PRESERVATION-MATRIX.md
    - .planning/phases/02-data-and-upgrade-preservation/02-04-TDD-RED-01.json
  modified:
    - tests/compat/probe.php
    - tests/compat/run.sh
key-decisions:
  - "Run the aggregate registry in one disposable container cell while each required case receives a reset request state and fresh plugin activation."
  - "Treat WordPress 7.0.6 and 7.1.2 with PHP 8.3, 8.4, and 8.5 as the supported evidence set; PHP 8.2 remains diagnostic-only."
  - "Store a compact machine-readable evidence record inside the human-readable matrix report and validate it without rerunning containers."
patterns-established:
  - "Aggregate compatibility scenarios list every required case explicitly and fail when a support module, case result, warning, fatal, or plugin error is missing."
  - "Evidence reports pair exact runtime cells and command lines with a fast parser-level validator."
requirements-completed: [DATA-01, DATA-02]
actuals:
  tokens: 5896
  tasks: 2
  commits: 3
commits: 3
plan_head_before: 30f4c0dabfa3baf01e5c89e6dc89fed7538b2b85
plan_head_after: 3ee96f8311ad5f758c450b77047ad0edc1f1680e
coverage:
  - id: D1
    description: "Aggregate preservation runs every migration, safety, settings, lifecycle, dependency, and tour undo case once with fail-closed result evidence."
    requirement: DATA-01
    verification:
      - kind: integration
        ref: "rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case all"
        status: pass
    human_judgment: false
  - id: D2
    description: "WordPress 7.0/7.1 and PHP 8.3/8.4/8.5 evidence proves data preservation and existing full workflows without warnings or fatals."
    requirement: DATA-02
    verification:
      - kind: integration
        ref: "rtk bash tests/compat/run.sh preservation-evidence --report .planning/phases/02-data-and-upgrade-preservation/02-PRESERVATION-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3"
        status: pass
    human_judgment: false
metrics:
  duration: 25m
  completed: 2026-10-04
  tasks: 2
  files: 4
status: complete
---

# Phase 02 Plan 04: Aggregate Preservation Matrix Summary

**A fail-closed ten-case upgrade-preservation registry and reproducible WordPress 7.0/7.1 × PHP 8.3–8.5 evidence matrix.**

## Performance

- **Duration:** 25m
- **Started:** 2026-10-04T14:10:54Z
- **Completed:** 2026-10-04T14:36:02Z
- **Tasks:** 2/2
- **Files modified:** 4

## Accomplishments

- Added an explicit aggregate registry that runs all ten migration and post-upgrade behavior cases once, rejects unavailable support modules and duplicate IDs, and records per-case readiness, warning, fatal, and plugin-error status.
- Preserved fast targeted cells while making `matrix --scenario upgrade-preservation` default to the aggregate `all` case.
- Added a parser-only `preservation-evidence` command and published exact passing evidence for WordPress 7.0.6/7.1.2 and PHP 8.3.35/8.4.26/8.5.11.
- Confirmed existing administration, shortcode, RSS, iCalendar, CSV import/export, and duplicate handling still pass across the same six runtime cells.

## Task Commits

1. **Task 1 RED: aggregate preservation contract** — `461f76b` (`test`)
2. **Task 1 GREEN: fail-closed aggregate registry** — `7869283` (`feat`)
3. **Task 2: matrix evidence and validator** — `3ee96f8` (`docs`)

## Files Created/Modified

- `tests/compat/probe.php` — Explicit required-case registry and isolated aggregate execution evidence.
- `tests/compat/run.sh` — Aggregate matrix selection and machine-readable report validation.
- `.planning/phases/02-data-and-upgrade-preservation/02-PRESERVATION-MATRIX.md` — Supported runtime results, commands, fixture scope, and requirement evidence.
- `.planning/phases/02-data-and-upgrade-preservation/02-04-TDD-RED-01.json` — Verified RED evidence for the aggregate contract.

## Validation

Passed in the OrbStack Compose harness:

- `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case all`
- `rtk bash tests/compat/run.sh lint --php-branches 8.3 --files admin/db.php,admin/handlers.php,gigpress.php,tests/compat/probe.php,tests/compat/upgrade-preservation-migrations.php,tests/compat/upgrade-preservation-crud.php`
- `rtk bash tests/compat/run.sh matrix --scenario upgrade-preservation --case all --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL`
- `rtk bash tests/compat/run.sh matrix --scenario full-workflows --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL`
- `rtk bash tests/compat/run.sh preservation-evidence --report .planning/phases/02-data-and-upgrade-preservation/02-PRESERVATION-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3`

## Decisions Made

- Keep aggregation container-only: the host has no PHP runtime and PHP 8.2 remains outside supported evidence.
- Spawn each named aggregate case with a reset request state and a deactivated/reactivated plugin so database active-state records cannot hide missing module functions.
- Make the report validator read only the report; it does not rerun containers or require host PHP.

## TDD Gate Compliance

- RED evidence for `upgrade-preservation.all` was validated as `RED_EVIDENCE_OK` before the aggregate implementation.
- The GREEN aggregate cell passes the same focused PHP 8.3 command with all ten named cases present exactly once.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Re-activate GigPress for each aggregate child case**
- **Found during:** Task 1
- **Issue:** A later child saw the active-plugin database record but did not load the plugin functions, causing aggregate cases to fatal.
- **Fix:** Deactivate and activate GigPress for each upgrade-preservation probe invocation.
- **Files modified:** `tests/compat/probe.php`
- **Verification:** The focused aggregate cell and all six preservation matrix cells passed.
- **Committed in:** `7869283`

**2. [Rule 1 - Bug] Quote hyphenated requirement IDs in the report validator**
- **Found during:** Task 2
- **Issue:** jq parsed `DATA-01` and `DATA-02` as expressions instead of object keys.
- **Fix:** Use bracket-key access in the report validator.
- **Files modified:** `tests/compat/run.sh`
- **Verification:** `preservation-evidence` passed against the final report.
- **Committed in:** `3ee96f8`

**Total deviations:** 2 auto-fixed bugs.

## Issues Encountered

The sandbox could not access the local OrbStack Docker socket. Running the explicitly authorized Compose commands outside the sandbox resolved that environment restriction.

## Known Stubs

None.

## Next Phase Readiness

Phase 02 has reproducible DATA-01 and DATA-02 evidence across the declared supported matrix. Later phases can use the aggregate preservation cell and parser-only report validator as regression gates.

## Self-Check: PASSED

Verified the aggregate runner, probe, matrix report, summary, and task commits
`461f76b`, `7869283`, and `3ee96f8` in Git history.

---

*Phase: 02-data-and-upgrade-preservation*
*Completed: 2026-10-04*
