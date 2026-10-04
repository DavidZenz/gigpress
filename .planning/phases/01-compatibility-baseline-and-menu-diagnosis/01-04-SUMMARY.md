---
phase: 01-compatibility-baseline-and-menu-diagnosis
plan: "04"
subsystem: compatibility
tags: [wordpress, php, admin-menu, docker-compose, tdd]
requires:
  - phase: 01-02
    provides: controlled menu warning attribution and conflict fixture
  - phase: 01-03
    provides: PHP 8.3 runtime floor and supported compatibility harness
provides:
  - early owned separator registration and pure menu-order transform
  - supported preferred-placement and conflict-fallback matrix evidence
affects: [01-05, admin-menu]
actuals:
  tokens: 4300
  tasks: 2
  commits: 3
commits: 3
plan_head_before: 5efb83650a0b54624be8ab98fd25eb747dc9a960
plan_head_after: deddd8713a4cca94fb4048e5ad6de4e99bfcce9b
tech-stack:
  added: []
  patterns: [pure filter transform, strict unique-slug validation, disposable conflict matrix]
key-files:
  created: [.planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-MENU-VERIFICATION.md]
  modified: [gigpress.php, tests/compat/run.sh, tests/compat/probe.php, tests/compat/fixtures/menu-conflict-plugin.php]
key-decisions:
  - "Register separator-gp during admin_menu, then return a stable input-only transform when its prerequisites are unique."
  - "Treat a conflict marker as unsafe and preserve WordPress standard order for both before and after callback positions."
requirements-completed: [COMP-03]
coverage:
  - id: D1
    description: Pure GigPress menu-order transform with strict standard-order fallback.
    requirement: COMP-03
    verification:
      - kind: unit
        ref: rtk bash tests/compat/run.sh menu-contract --wp 7.1.2 --php 8.3 --cases preferred,index-zero,missing,duplicate,empty,single,order-conflict,no-global-mutation
        status: pass
    human_judgment: false
  - id: D2
    description: Supported WordPress/PHP admin-menu placement and conflict fallback.
    requirement: COMP-03
    verification:
      - kind: integration
        ref: rtk bash tests/compat/run.sh matrix --scenario admin-menu --wp-lines 7.0,7.1 --php-supported upstream
        status: pass
      - kind: integration
        ref: rtk bash tests/compat/run.sh matrix --scenario admin-menu --wp-lines 7.0,7.1 --php-supported upstream --conflict-fixture tests/compat/fixtures/menu-conflict-plugin.php --conflict-mode order-only
        status: pass
    human_judgment: true
    rationale: Disposable browserless cells cannot assess rendered admin-menu usability.
duration: 48min
completed: 2026-10-04
status: complete
---

# Phase 01 Plan 04: Menu Ordering Correction Summary

**GigPress now registers its separator before WordPress snapshots menu slugs and safely falls back to core ordering whenever another menu-order callback conflicts.**

## Accomplishments

- Replaced late global `$menu` mutation with a pure stable transform that preserves unrelated slug order and handles index-zero, null, missing, duplicate, and conflict cases.
- Registered `separator-gp` exactly once during `admin_menu` after GigPress page registration.
- Proved preferred placement across WordPress 7.0/7.1 and PHP 8.3–8.5, and standard-order fallback for before and after order-only conflict callbacks.
- Recorded diagnostic-only fixture attribution and the boundary around the unavailable live callback in [01-MENU-VERIFICATION.md](./01-MENU-VERIFICATION.md).

## Task Commits

1. **Task 01-04-01 RED: menu ordering contract** — `15fb040` (test)
2. **Task 01-04-01 GREEN: pure fallback-safe transform** — `c1a1890` (feat)
3. **Task 01-04-02: placement and fallback evidence** — `deddd87` (feat)

## Deviations from Plan

### Auto-fixed Issues

1. **[Rule 1 - Harness] Corrected menu-contract JSON parsing and full matrix iteration.**
   - The harness parsed TAP lines as JSON and nested matrix cells consumed their parent input stream.
   - It now selects the JSON record and runs each child with standard input closed.

2. **[Rule 3 - Blocking] Added the planned `admin-menu` scenario and readiness check.**
   - The documented matrix scenario was not accepted and WordPress could race its application database.
   - The runner accepts `admin-menu` and waits for the database account before probing.

3. **[Rule 1 - Diagnostic] Isolated PHP 8.2 exact-key attribution from the PHP 8.3 GigPress floor.**
   - The supported plugin header correctly prevents PHP 8.2 activation.
   - The fixture enables its own order filter for the narrowly scoped diagnostic request, leaving PHP 8.2 outside supported results.

4. **[Rule 2 - Verification] Added explicit warning, duplicate, preferred-placement, and before/after conflict assertions.**
   - The original result shape hid duplicate slugs and did not prove the conflict fallback.
   - The probe now reports raw slugs, captured menu warnings, and conflict state; the fixture and matrix cover both callback positions.

## TDD Gate Compliance

- RED evidence record: `01-04-RED-EVIDENCE.json`, validated as `RED_EVIDENCE_OK` before the GREEN implementation.
- RED commit `15fb040` precedes GREEN commit `c1a1890`.
- The final menu-contract command passes.

## Known Stubs

None.

## Self-Check: PASSED

- Confirmed the verification record and all modified compatibility files exist.
- Confirmed commits `15fb040`, `c1a1890`, and `deddd87` exist in Git history.
