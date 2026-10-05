---
phase: 04-public-publishing
plan: 01
subsystem: testing
tags: [wordpress, compatibility, publishing, rss, ical]

# Dependency graph
requires:
  - phase: 02-data-and-upgrade-preservation
    provides: Reconstructed legacy/current fixtures, migration readiness checks, independent manifests, and linked-post snapshots.
provides:
  - Fail-closed selected-case public-publishing dispatcher and migrated tracer.
  - Public-output evidence for all recognized 1.0–1.6 source fixtures and populated current state.
  - Separate supplemental public-only value manifest for later renderer and serializer plans.
  - Labelled no-JavaScript Google Calendar and iCalendar actions beside show date/time.
affects: [04-02, 04-03, 04-04, 04-05]

# Actuals, measured from the realized diff using chars/4.
actuals:
  tokens: 8762
  tasks: 2
  commits: 2
commits: 2
plan_head_before: 30063f5e03ae6e23a89880d0032f89f1b00ddc2d
plan_head_after: 7f62194cad7716dc284f54d759d02564b0ae8fd8

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Public compatibility cases use independent fixture expectations and reject missing, empty, or malformed evidence.
key-files:
  created:
    - tests/compat/public-publishing.php
    - tests/compat/public-tracer.php
    - tests/compat/public-migrated.php
    - tests/compat/fixtures/public-publishing/supplemental.php
    - tests/compat/fixtures/public-publishing/child/gigpress-templates/shows-list.php
  modified:
    - tests/compat/run.sh
    - tests/compat/probe.php
    - templates/shows-list.php
key-decisions:
  - "Keep persisted show_id and existing relationships as the identity anchor across public destinations."
  - "Keep supplemental hostile and layout values separate from canonical migration fixtures."
patterns-established:
  - "Selected compatibility dispatch loads only the requested registered module and validates nonempty boolean checks."
  - "Public read checks compare saved state before and after repeated and concurrent anonymous requests."
requirements-completed: [PUB-01, PUB-02]

# Coverage metadata
coverage:
  - id: D1
    description: "The reconstructed 1.4 migrated show is published through shortcode, legacy wrapper, widget, related-post, RSS, iCalendar, and a child-theme override while reads preserve its snapshot."
    requirement: PUB-01
    verification:
      - kind: e2e
        ref: "rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario public-publishing --case tracer-1.4"
        status: pass
    human_judgment: false
  - id: D2
    description: "Every recognized legacy source and populated current state preserves its independent migration manifest and publishes its expected public records and feeds."
    requirement: PUB-01
    verification:
      - kind: e2e
        ref: "rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario public-publishing --case migrated-contracts"
        status: pass
      - kind: e2e
        ref: "rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case all"
        status: pass
    human_judgment: false
  - id: D3
    description: "A separate supplemental manifest records Unicode, hostile text and URL, status, grouping, country, and date/time boundaries for later public cases."
    verification: []
    human_judgment: true
    rationale: "This plan creates the supplemental input manifest; dedicated later layout and serializer cases will exercise these values."
  - id: D4
    description: "The bundled first-show calendar actions are labelled visible links beside date/time without JavaScript."
    requirement: PUB-02
    verification:
      - kind: e2e
        ref: "tracer-1.4 calendar_ticket_labels assertion in tests/compat/public-tracer.php"
        status: pass
    human_judgment: true
    rationale: "Final responsive layout and viewport acceptance are reserved for the browser evidence in Plan 04-05."

# Metrics
duration: 20min
completed: 2026-10-05
status: complete
---

# Phase 04 Plan 01: Migrated Public Publishing Summary

**Public publishing is verified against migrated 1.0–1.6 fixture states, with independently checked feeds, linked posts, and unchanged read snapshots.**

## Performance

- **Duration:** 20 min
- **Started:** 2026-10-05T09:42:00Z (recovery execution; approximate)
- **Completed:** 2026-10-05T10:02:09Z
- **Tasks:** 2
- **Files modified:** 8

## Accomplishments

- Verified the committed 1.4 tracer on WordPress 7.1.2 / PHP 8.3.35 across shortcode, legacy wrapper, widget, related post, anonymous RSS and iCalendar endpoints, and a child-theme override. All 19 named checks passed, including repeated/concurrent reads and unchanged snapshots.
- Added migrated publishing checks for each tracked source fixture from 1.0 through 1.6. The checks validate source-specific schemas, settings, relationships, linked posts, output membership, feed order, and no-op behavior for 1.6.
- Added a separately identified public-only supplemental manifest for long Unicode/unbroken text, rich notes, hostile values, grouping, status, country, and date/time boundaries.
- Kept ordinary labelled Google Calendar and iCalendar links beside date/time in the bundled show partial; the legacy `$scope != 'past'` behavior remains.

## Task Commits

Each task was committed atomically:

1. **Task 1: Publish one migrated 1.4 show through every public destination** - `6a4b80c` (`feat`)
2. **Task 2: Extend migrated publishing to every recognized version and current state** - `7f62194` (`feat`)

**Plan metadata:** recorded in the final documentation commit.

## Files Created/Modified

- `tests/compat/run.sh` - Registered the public scenario and its exact case allow-list.
- `tests/compat/probe.php` - Seeds and dispatches public cases inside the real plugin probe.
- `tests/compat/public-publishing.php` - Enforces the case registry and fail-closed result contract.
- `tests/compat/public-tracer.php` - Exercises the migrated 1.4 public path and theme override.
- `tests/compat/public-migrated.php` - Exercises all seven recognized fixture/current states.
- `tests/compat/fixtures/public-publishing/supplemental.php` - Stores separate public-only boundary inputs.
- `tests/compat/fixtures/public-publishing/child/gigpress-templates/shows-list.php` - Supplies an owned child-theme override fixture.
- `templates/shows-list.php` - Presents labelled per-show calendar actions beside date/time.

## Verification

- `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario public-publishing --case tracer-1.4` — PASS; 19 named boolean assertions, four anonymous HTTP responses returned 200, zero plugin errors/warnings.
- `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case all` — PASS; all 11 inherited preservation cases passed.
- `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario public-publishing --case migrated-contracts` — PASS; all seven source states passed 11 named aggregate checks. Each state returned four 200 anonymous feed responses and identical pre/post read snapshots.
- Unknown `--case unknown-case` — rejected by the runner with exit code 2 before container startup.
- `git diff --check` — clean before the Task 2 commit.

## Decisions Made

- Persisted `show_id` and existing relationships remain the identity anchor across public destinations.
- Supplemental public-only values remain separate from canonical migration evidence.
- The 1.1 reconstructed source has no status column. Its migration defaults both rows to the established current status, so its independently expected RSS membership/order is `[113, 111]`; the related-post and selected iCalendar checks remain tied to linked show 111.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking issue] Load migration evidence helpers in the selected migrated case**
- **Found during:** Task 2
- **Issue:** The public probe loaded migration helpers for `tracer-1.4`, but the newly selected `migrated-contracts` module could run without those helpers.
- **Fix:** The migrated case now loads the existing migration helper module when selected.
- **Files modified:** `tests/compat/public-migrated.php`
- **Verification:** The migrated-contracts cell passed for all seven source states.
- **Committed in:** `7f62194`

**2. [Rule 1 - Test expectation bug] Match the 1.1 output to its historical schema**
- **Found during:** Task 2
- **Issue:** The first assertion expected only show 111 even though the 1.1 schema predates the status column and migration publishes both migrated rows.
- **Fix:** Recorded the independently expected RSS order `[113, 111]`, checked both show markers in shortcode/widget output, and kept the related post and selected calendar request bound to linked show 111.
- **Files modified:** `tests/compat/public-migrated.php`
- **Verification:** The migrated-contracts cell passed with exact RSS/iCalendar IDs and order.
- **Committed in:** `7f62194`

**Total deviations:** 2 auto-fixed (Rule 3: 1; Rule 1: 1).
**Impact on plan:** Closed helper-loading and historical expectation gaps without changing production migration or public query behavior.

## Issues Encountered

- The initial required test invocation was denied access to the repository-owned Docker-compatible engine. The authorized elevated invocation ran successfully through OrbStack; no external service or data store was used.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

Plan 04-02 can build the remaining responsive layout and override cases on the now-runnable selected public case contract. Final viewport judgment and full registry aggregation remain assigned to later Phase 04 plans.

---
*Phase: 04-public-publishing*
*Completed: 2026-10-05*

## Self-Check: PASSED

- All eight planned files exist.
- Task commits `6a4b80c` and `7f62194` exist in Git history.
- The plan ledger measures two task commits from `30063f5e03ae6e23a89880d0032f89f1b00ddc2d` through `7f62194cad7716dc284f54d759d02564b0ae8fd8`.
