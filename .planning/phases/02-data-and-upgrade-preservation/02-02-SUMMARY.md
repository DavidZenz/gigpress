---
phase: 02-data-and-upgrade-preservation
plan: "02"
subsystem: upgrade-preservation
tags: [wordpress, database, migrations, compatibility]
requires: [02-01]
provides: [complete-version-migration-matrix, current-version-steady-state]
affects: [admin/db.php, tests/compat]
tech-stack:
  added: []
  patterns: [journaled-migration-steps, fixture-manifest-matrix, final-marker-last]
key-files:
  created:
    - tests/compat/upgrade-preservation-migrations.php
    - tests/compat/fixtures/upgrade-preservation/1.0.php
    - tests/compat/fixtures/upgrade-preservation/1.1.php
    - tests/compat/fixtures/upgrade-preservation/1.2.php
    - tests/compat/fixtures/upgrade-preservation/1.3.php
    - tests/compat/fixtures/upgrade-preservation/1.5.php
    - tests/compat/fixtures/upgrade-preservation/1.6.php
  modified:
    - admin/db.php
    - tests/compat/probe.php
    - tests/compat/run.sh
decisions:
  - Journal transformed settings after each successful legacy step so retries resume with source-specific semantics.
  - Reuse exact artist and venue identity matches after interruption; reject ambiguous generated mappings.
  - Keep current 1.6 as a populated no-op fixture with every documented default present.
metrics:
  duration: 31m
  completed: 2026-10-04
  tasks: 3
  files: 11
status: complete
actuals:
  tokens: 10868
  tasks: 3
  commits: 4
commits: 4
plan_head_before: 54d16850c5520d1008bc15b997b983dd496f98ef
plan_head_after: 02b19e807862bdbb582c033d8a660863b8127812
---

# Phase 02 Plan 02: Complete Upgrade Preservation Matrix Summary

Historical 1.0 through 1.5 sources and a populated 1.6 steady state now have reproducible preservation evidence through the real WordPress plugin bootstrap.

## What Changed

- Added verified 1.0, 1.1, 1.2, 1.3, 1.5, and 1.6 synthetic reconstructed fixtures with nondefault prefixes, populated records, settings intent, linked posts, and expected identities.
- Expanded the coordinator to execute and journal only the exact legacy steps required for each recognized source version.
- Made 1.1, 1.2, 1.3, and 1.4 upgrade work retry-safe: completed settings state and generated IDs are retained in the journal until the final marker is read back.
- Added four matrix cases: early versions, later versions, current steady state, and cross-version settings/repeat loads.

## Verification

All commands ran against WordPress 7.1.2 and PHP 8.3 in the OrbStack Compose harness:

- `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case versions-1.0-1.2`
- `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case versions-1.3-1.5`
- `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case current-1.6`
- `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case settings-repeat`

Each passed. The legacy cases inject failures after each source-specific step and around generated artist, venue, and relationship work, then require a retry to converge without changing IDs or the final manifest.

## TDD Gate Compliance

- RED: `f1ba03a` added the early-version matrix; its intentional failing TAP result was validated as `RED_EVIDENCE_OK`.
- GREEN: `fae5048` expanded the verified coordinator and made that matrix pass.
- The later-path and current-state tasks extended the same already-generic coordinator with fixtures and integration assertions; no additional production behavior was needed.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Made fixture setting manifests optional for the prior 1.4 tracer**
- **Found during:** Task 02-02-03
- **Issue:** The cross-version settings matrix assumed every fixture declared a settings subset, while the existing 1.4 fixture did not.
- **Fix:** Treat an omitted settings subset as an empty assertion set while preserving all existing row and repeat checks.
- **Files modified:** tests/compat/upgrade-preservation-migrations.php
- **Commit:** 02b19e8

## Known Stubs

None.

## Self-Check: PASSED

Verified all eight preservation artifacts and all four task commits exist in this checkout.
