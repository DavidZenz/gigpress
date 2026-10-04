---
phase: 02-data-and-upgrade-preservation
plan: "03"
subsystem: wordpress-admin-data-integrity
tags: [wordpress, php, upgrades, admin-handlers, data-preservation]
requires:
  - phase: 02-01
    provides: verified database bootstrap coordinator and blocked-state contract
provides:
  - Readiness-guarded post-upgrade show mutations
  - All-status artist and venue dependency deletion protection
  - Per-tour pending undo ownership for detached shows
affects: [02-04, admin-show-management, database-upgrades]
tech-stack:
  added: []
  patterns: [handler-readiness-guard, all-status-dependency-check, per-tour-undo-map, containerized-mutation-probe]
key-files:
  created:
    - tests/compat/upgrade-preservation-crud.php
  modified:
    - admin/handlers.php
    - admin/artists.php
    - admin/venues.php
    - tests/compat/probe.php
    - tests/compat/run.sh
key-decisions:
  - "Run coordinator readiness checks after the existing nonce verification and before every covered mutation."
  - "Count all show statuses for deletion eligibility while retaining active-only count links in the list views."
  - "Use gigpress_tour_restore_map as exact pending ownership and retain show_tour_restore only as a compatibility hint."
patterns-established:
  - "Mutation probes seed an upgraded fixture and invoke real admin handlers with valid WordPress nonces."
  - "Undo operations restore only rows that still match their persisted ownership and expected detached state."
requirements-completed: [DATA-02]
actuals:
  tokens: 7634
  tasks: 3
  commits: 6
commits: 6
plan_head_before: 6e1848705a9b41d970abc54b1b7e3d49c6dc6900
plan_head_after: 7ba699c08e482e28e11beab1ebc374a14d06f824
metrics:
  duration: 8m
  completed: 2026-10-04
  tasks: 3
  files: 6
status: complete
coverage:
  - id: D1
    description: "Real post-upgrade show handlers preserve create, edit, copy, selected trash, restore, and blocked-state immutability."
    requirement: DATA-02
    verification:
      - kind: integration
        ref: "rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case show-lifecycle"
        status: pass
    human_judgment: false
  - id: D2
    description: "Artist and venue deletion controls and handlers reject both active and trashed show dependencies."
    requirement: DATA-02
    verification:
      - kind: integration
        ref: "rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case entity-guards"
        status: pass
    human_judgment: false
  - id: D3
    description: "Tour delete and undo retain per-operation ownership through overlapping, repeated, missing-row, reassignment, and legacy-marker cases."
    requirement: DATA-02
    verification:
      - kind: integration
        ref: "rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case tour-undo"
        status: pass
    human_judgment: false
---

# Phase 02 Plan 03: Upgrade-Preserving Admin Lifecycle Summary

**Readiness-guarded show administration, all-status relationship deletion protection, and ownership-safe tour undo on the upgraded WordPress fixture.**

## Performance

- **Duration:** 8m
- **Started:** 2026-10-04T15:46:42+02:00
- **Completed:** 2026-10-04T15:54:37+02:00
- **Tasks:** 3/3
- **Files modified:** 6

## Accomplishments

- Added a coordinator-backed readiness guard to show mutations so blocked upgrade state cannot add, edit, trash, restore, or undo show rows.
- Made artist and venue deletion unavailable whenever active or trashed shows reference the entity, at both the list view and handler boundary.
- Replaced global tour restore-marker behavior with a per-tour show ownership map that preserves overlapping deletes and intervening reassignment.
- Added disposable Compose probes that exercise the real WordPress handlers and preserve RED evidence for each behavior contract.
- Closed additional mutation-boundary gaps found by the final security audit: venue, tour, and artist writes, CSV import, empty-trash, and legacy tour mapping now check readiness before side effects; the legacy mapping route also requires its nonce. Commit `61d60fd` adds regression coverage for the blocked paths.

## Task Commits

1. **Task 1 RED: show lifecycle contract** — `e5528b2` (`test`)
2. **Task 1 GREEN: readiness-guarded show mutations** — `44e45e7` (`feat`)
3. **Task 2 RED: active and trashed dependency contract** — `30a3101` (`test`)
4. **Task 2 GREEN: all-status entity deletion guards** — `6b39262` (`feat`)
5. **Task 3 RED: isolated tour undo contract** — `97c7bef` (`test`)
6. **Task 3 GREEN: per-tour undo ownership** — `7ba699c` (`feat`)

## Validation

All commands passed in the OrbStack Compose harness on WordPress 7.1.2 and PHP 8.3:

- `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case show-lifecycle`
- `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case entity-guards`
- `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case tour-undo`

The local host has no PHP executable, so the required container matrix is the PHP validation source for this plan.

After the final security fix, the focused `show-lifecycle`, `entity-guards`, and `tour-undo` cases passed again on WordPress 7.1.2 / PHP 8.3. The lifecycle probe confirms all nine newly covered mutation routes preserve GigPress tables and options while readiness is blocked, reject forged nonce calls, and prevent CSV upload side effects. PHP 8.3 lint passed for the changed handlers and CRUD probe.

## Decisions Made

- Reused the phase coordinator result rather than duplicating upgrade-state rules in each handler.
- Preserved the legacy row marker as a compatibility hint but require a matching `gigpress_tour_restore_map` entry before any tour reattachment.
- Remove consumed or stale ownership records for the requested tour only, leaving another pending operation intact.

## TDD Gate Compliance

- RED evidence for show lifecycle, entity dependencies, and tour ownership was verified as `RED_EVIDENCE_OK` before each corresponding production change.
- Each RED commit is followed by a focused GREEN commit, and all three final integration probes pass.

## Deviations from Plan

The final security audit found that the readiness guard originally covered the show, venue/artist deletion, and tour undo paths but missed additional dispatched mutation handlers. The phase scope was extended to cover those paths and add regression probes in commit `61d60fd`; this was a security-driven completion of the existing plan threat mitigation, not a new feature.

## Known Stubs

None.

## Next Phase Readiness

Plan 02-04 can consume the new three-case mutation probe alongside the preceding upgrade matrix. Existing admin workflows retain their supported behavior while blocked upgrade state and relationship ownership remain protected.

## Self-Check: PASSED

Verified the created mutation probe, all declared modified handlers and views, commits \`e5528b2\`, \`44e45e7\`, \`30a3101\`, \`6b39262\`, \`97c7bef\`, \`7ba699c\`, and the post-audit guard fix \`61d60fd\` in Git history.

---

*Phase: 02-data-and-upgrade-preservation*
*Completed: 2026-10-04*
