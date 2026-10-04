---
phase: 03-administration-workflows
plan: "01"
subsystem: ui
tags: [wordpress, php, jquery, administration, recovery, tdd]
requires:
  - phase: 02-data-and-upgrade-preservation
    provides: Database readiness guards and the eleven-case upgrade preservation contract
provides:
  - Native date entry with editable raw recovery and explicit save outcomes
  - Safe reuse of completed artist, venue, tour and related-post creation on retry
  - Optional time, last-day guidance and progressive correction controls
  - Fail-closed eight-case administration registry and three real WordPress entry cases
affects: [03-02, 03-03, 03-04, verification]
actuals:
  tokens: 30120
  tasks: 3
  commits: 6
commits: 6
plan_head_before: a08de8948c79019bb1ea63c8c5e40c477d6387ac
plan_head_after: a731f81bd4109a278e34dd55e79fbe3590f943f4
tech-stack:
  added: []
  patterns: [raw-state separated from normalized persistence, explicit save outcomes, completed-identity retry recovery, progressive enhancement]
key-files:
  created: [tests/compat/administration-entry.php]
  modified: [admin/new.php, admin/handlers.php, scripts/gigpress-admin.js, css/gigpress-admin.css, tests/compat/probe.php, tests/compat/run.sh, tests/compat/upgrade-preservation-crud.php]
key-decisions:
  - "Retain raw received strings separately from normalized persistence and select replacement dates only through an explicit checkbox."
  - "Carry completed related-creation IDs into recovered selections and hidden retry bookkeeping rather than repeating successful creation."
  - "Require real nonempty assertions for each explicit administration case; absent later-plan modules remain failing cases."
patterns-established:
  - "Show saves return status, mode, confirmed show_id, raw_state, field_errors, system_errors and created_ids."
  - "No-time remains 00:00:01; real midnight remains 00:00:00; valid calendar end dates retain established expiration semantics."
requirements-completed: [ADMIN-01, UX-01]
coverage:
  - id: entry-save
    description: Native date saves, explicit saved identity and add-another behavior
    requirement: ADMIN-01
    verification:
      - kind: integration
        ref: tests/compat/administration-entry.php#entry-create
        status: pass
    human_judgment: false
  - id: entry-recovery
    description: Complete rejected-state recovery, guards, no-op edits, copy immutability and completed-creation reuse
    requirement: ADMIN-01
    verification:
      - kind: integration
        ref: tests/compat/administration-entry.php#entry-recovery
        status: pass
      - kind: integration
        ref: tests/compat/upgrade-preservation-crud.php#show-lifecycle
        status: pass
    human_judgment: false
  - id: entry-storage-and-markup
    description: Optional time and end-date storage boundaries, labels and real help/error targets
    requirement: UX-01
    verification:
      - kind: integration
        ref: tests/compat/administration-entry.php#entry-controls
        status: pass
    human_judgment: false
  - id: entry-browser-interaction
    description: Actual native picker, incomplete typed dates, keyboard focus and no-JS correction behavior
    requirement: UX-01
    verification: []
    human_judgment: true
    rationale: Plan 03-04 owns disposable HTTP browser evidence; PHP markup and source assertions cannot establish actual interaction.
duration: 21min
completed: 2026-10-04
status: complete
---

# Phase 03 Plan 01: Show Entry and Recovery Summary

**Native date entry and optional time now save through guarded WordPress handlers, preserve raw correction text, and reuse completed related creations after failed retries.**

## Performance

- **Started:** 2026-10-04T20:31:38Z
- **Implementation completed:** 2026-10-04T20:51:59Z
- **Duration:** 21 minutes including context loading, RED/GREEN verification and regressions
- **Tasks:** 3
- **Files changed:** 8 source/harness files
- **Actual tokens:** ceil(120479 realized diff characters / 4) = 30120, measured on the eight source/harness files between the recorded plan heads
- **Measured commits:** 6 task commits, before summary/state metadata commits

## Accomplishments

- Real native-date creation returns a confirmed saved ID, Edit saved show and View list links, then a fresh add form. Existing sticky defaults change only after confirmed success.
- All received scalar strings remain separate from persistence normalization. Invalid dates render editable text under their authoritative key, with a separately labeled replacement picker and explicit no-JS replacement choice. Invalid new keys never fall through to legacy date values.
- Every related creation is checked immediately. Successful IDs survive both final-write failure and another rejected retry; existing identities are selected rather than created again. Unchanged updates succeed, absent targets fail, and copies preserve their source.
- Hours retain their 00–23 values while 12-hour labels include AM/PM; minutes offer all 00–59 values. No-time and midnight remain distinct. End-date wording explains the existing daily cutoff without adding a start/end ordering restriction.
- Server-rendered correction fields remain reachable without JavaScript. Enhancements initialize from checked/selected state, keep keyboard focus, reveal errored sections, enable minutes after hour selection, and focus relevant feedback only after a save/load outcome.
- The explicit administration registry fails closed on absent/empty cases and exact-set aggregate mismatches. All eleven inherited upgrade cases and the existing full workflows still pass.

## Task Commits

| Task | RED commit | GREEN commit | Result |
|------|------------|--------------|--------|
| 03-01-01 Native date tracer and case dispatch | `3e605cb` | `62d9dac` | 9 passing entry-create assertions; tracer rerun passed before expansion |
| 03-01-02 Rejected-field recovery and safe retries | `8aa9f98` | `0394acf` | 130 passing entry-recovery assertions and inherited show-lifecycle PASS |
| 03-01-03 Optional time, last-day and correction controls | `48fc1a0` | `a731f81` | 67 passing entry-controls assertions |

## Files Created/Modified

- `admin/new.php`: native dates, escaped raw fallback, retained modes/identities, explicit replacement controls, labels/help/errors, optional-time/minute choices and guarded existing welcome dismissal.
- `admin/handlers.php`: show-only raw/domain validation, capability/nonce/readiness boundaries, normalized preparation, strict writes/read-back, explicit outcomes, completed-ID retries and linked notices. Other entity-handler contracts remain intact.
- `scripts/gigpress-admin.js`: state-based reveal/minute enablement, relevant feedback focus and summary-link targets; sortable-artist behavior retained.
- `css/gigpress-admin.css`: scoped section, help, error and focus styles using the existing WordPress form/notice classes.
- `tests/compat/administration-entry.php`: new real WordPress entry-create, entry-recovery and entry-controls cases, including controlled failures at artist/venue/tour/post/show writes.
- `tests/compat/probe.php`: explicit eight-case administration registry, lazy family modules, nonempty case records, fresh real-plugin child activation, failure details and measured case duration.
- `tests/compat/run.sh`: cell/matrix administration scenario/case forwarding and strict jq result/registry assertions; inherited contracts retained.
- `tests/compat/upgrade-preservation-crud.php`: capture explicit returned outcomes and IDs in the existing legacy request adapter; retain lifecycle identity, relationship, blocked-write and optional-request assertions.

## Verification

All commands ran from `/Users/davidzenz/gigpress` on `codex/phase-01-04-menu`, with normal commit hooks. Runtime evidence is WordPress **7.1.2**, PHP **8.3.35**, image `wordpress:php8.3-apache`, image ID `sha256:4abf7a450ee477dde967584f8174d7e03221d224c4971a0c38d84e7254426e64`. This is a proven pinned cell, not a claim about the latest runtime patches.

| Command | Result |
|---------|--------|
| `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario administration-workflows --case entry-create` | PASS, 9 assertions; also passed again in aggregate child execution |
| `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario administration-workflows --case entry-recovery` | PASS, 130 assertions; also passed again in aggregate child execution |
| `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario administration-workflows --case entry-controls` | PASS, 67 assertions including four welcome-dismissal preservation/guard checks |
| `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case show-lifecycle` | PASS |
| `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case all` | PASS, exact eleven-case registry; all warnings/fatals/plugin-error counts zero |
| `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario full-workflows` | PASS: admin create/edit/read, public shortcode, RSS, iCalendar, CSV and duplicate preservation |
| `rtk proxy bash tests/compat/run.sh lint --php-branches 8.3 --files admin/new.php,admin/handlers.php,tests/compat/administration-entry.php,tests/compat/probe.php,tests/compat/upgrade-preservation-crud.php` | PASS, all five files syntax clean |
| `rtk proxy node --check scripts/gigpress-admin.js`; `rtk proxy bash -n tests/compat/run.sh`; `rtk proxy git diff --check` | PASS |
| `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario administration-workflows --case all` | Expected exit 2: entry-create/recovery/controls PASS; the five required settings/list cases have empty checks and FAIL because their later-plan modules are absent |

The final delivered cases total **206 named assertions**, with zero warnings, fatals or plugin errors. Snapshot assertions prove denied capability, invalid nonce and blocked readiness write nothing; invalid dates/shapes validate before creation; no-op updates and retry reuse preserve identities. Controlled SQL failures are test fixtures, not production fault hooks.

### Measured timings

- Cold disposable controls cell, including image checks, fixture startup/bootstrap, case and teardown: **16.17 seconds**, measured by `rtk proxy /usr/bin/time -p bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario administration-workflows --case entry-controls`.
- Warm in-process controls callback including real handler, database and render assertions: **0.0561 seconds**, measured by `microtime(true)` in the case wrapper. This excludes WordPress/container startup and is not an HTTP/browser latency claim.
- Aggregate cold fixture with delivered and missing cases: **14.45 seconds**; warm delivered callbacks were entry-create **0.0137 s**, recovery **0.1662 s**, controls **0.0446 s** before the four added welcome assertions.
- These are observations on the existing local image/cache state; an uncached download/startup has no claimed time bound.

## TDD Gate Compliance

Each task observed intentional failing assertions before implementation, verified its RED record with `gsd_run check tdd-red-evidence`, committed RED tests, then committed passing GREEN implementation. All three records returned `RED_EVIDENCE_OK`; the task behavior predicate reported behavior-adding and the phase MVP/TDD applicability was honored despite the global default being false.

| Task | RED command case / exit | Expected failure | GREEN evidence |
|------|-------------------------|------------------|----------------|
| 03-01-01 | administration-workflows / entry-create / 2 | Native-date row/outcome, sentinel and fresh-add success flow not yet implemented | Same case PASS; tracer feedback rerun PASS |
| 03-01-02 | administration-workflows / entry-recovery / 2 | Invalid date fallback, raw/new-marker state and safe completed-ID recovery absent | Same case PASS, 130 named checks; lifecycle PASS |
| 03-01-03 | administration-workflows / entry-controls / 2 | AM/PM labels, last-day help, all minutes, no-blur and minute enablement assertions false | Same case PASS, final 67 named checks |

RED evidence records were persisted at `/tmp/gigpress-03-01-01-red.json`, `/tmp/gigpress-03-01-02-red.json` and `/tmp/gigpress-03-01-03-red.json` during execution; the table and test/implementation commit pairs preserve the durable gate history. No separate refactor was necessary.

## Decisions Made

Followed D-01–04/D-14–15/D-18: authoritative native/explicit replacement values, unchanged no-time and expiration storage, raw correction strings, guarded saves, truthful partial completion, and real linked feedback. The explicit outcome adapter is local to show handling. The eight-case registry intentionally cannot pass until Plans 03-02/03-03 deliver their modules.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Aggregate child probes lacked the real plugin under WP_INSTALLING**
- **Found during:** Task 03-01-03 final aggregate verification.
- **Issue:** The child inherited an active-plugin record, but WP_INSTALLING skips active-plugin loading and activate_plugin skips already-active plugins; entry callbacks then lacked GIGPRESS table constants.
- **Fix:** Reuse the existing fresh-activation boundary for administration child probes, and retain child failure details so incomplete evidence remains diagnosable.
- **Files:** `tests/compat/probe.php`.
- **Verification:** All three delivered children PASS; five absent required modules remain FAIL with no false aggregate PASS.
- **Commit:** `a731f81`; WINDOWS entry 5 marked fixed.

**2. [Rule 1 - Bug] Restore existing welcome-dismissal behavior after the renderer rewrite**
- **Found during:** Task 03-01-03 preservation review.
- **Issue:** The shortened informational welcome notice had dropped its established dismissal action.
- **Fix:** Restore the action with configured capability, a dedicated nonce and readiness before updating only the welcome setting.
- **Files:** `admin/new.php`, `tests/compat/administration-entry.php`.
- **Verification:** Signed link exists, invalid nonce and absent capability write nothing, and authorized dismissal changes only welcome; four passing checks.
- **Commit:** `a731f81`; WINDOWS entry 6 marked fixed.

Warm case timing instrumentation in the owned probe was added to satisfy the plan's measured cold/warm requirement. The lifecycle fixture deliberately treats nullable stored external URL as the browser's empty scalar string and uses returned saved IDs; those are the planned compatibility-adapter adjustments. No packages, schema, runtime floor or public/CSV implementation changed.

## Issues Encountered

None outstanding in the implemented slice.

## Authentication Gates

None. Authorized OrbStack and scoped Git writes used the existing sandbox escalation path.

## Pending Browser Evidence and Downstream Review

- Plan 03-04 must exercise the native picker, incomplete typed-input sanitization, Tab/Space/Enter, actual focus and feedback announcement, end-date/new-entity reveal, recovery, and no-JS correction on the disposable HTTP fixture. WINDOWS entry **7** remains open for that evidence.
- The descriptor-less product prohibition remains **flagged-unverified** for downstream review; storage assertions do not certify its wording or browser experience.
- ADMIN-01 and UX-01 are listed above as this plan's declared contributions; the shared-ID readiness gate currently returns **0/2 ready**, so global requirements remain incomplete until sibling summaries exist.
- No known implementation stubs block this plan. The five absent settings/list case modules are required later-plan work and already fail closed.

## Next Phase Readiness

Ready for Plan 03-02 settings work and Plan 03-03 list work. They should use `gigpress_administration_settings_case($case)` and `gigpress_administration_list_case($case)` in their lazily loaded family modules, preserving the explicit registry, positive named checks and zero-error contract. Plan 03-04 owns runtime matrix and real browser evidence.

## Self-Check: PASSED

All eight changed source/harness files and this summary exist. All six task commit objects exist. The measured plan ledger is six commits between the recorded before/after heads; no task changes remain uncommitted.
