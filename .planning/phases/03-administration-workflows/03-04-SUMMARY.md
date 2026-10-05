---
phase: 03-administration-workflows
plan: "04"
subsystem: testing
tags: [wordpress, php, administration, http, browser, preservation, evidence, tdd]
requires:
  - phase: 03-01
    provides: Guarded date entry, raw recovery and administration registry
  - phase: 03-02
    provides: Preserving grouped settings and save cases
  - phase: 03-03
    provides: Retained list navigation, owned confirmations and per-ID outcomes
provides:
  - Owned loopback disposable real WordPress browser and authenticated HTTP fixture
  - Real options.php save and authority/nonce/selected-ID negative snapshots
  - Actual browser observations with explicit outstanding human acceptance
  - Supported pinned administration/preservation/workflow matrix and fail-closed evidence validator
affects: [verification, administration-uat, phase-04, phase-05]
actuals:
  tokens: 18513
  tasks: 3
  commits: 8
commits: 8
plan_head_before: 9c4ef69b66f2f1f237ac38489ab6da9639a34026
plan_head_after: b210224
tech-stack:
  added: []
  patterns: [owned disposable loopback transport, real authenticated HTTP snapshots, pinned runtime source fingerprints, corrupted evidence rejection]
key-files:
  created:
    - tests/compat/browser-bootstrap.php
    - tests/compat/compose.browser.yaml
    - tests/compat/ADMIN-BROWSER.md
    - .planning/phases/03-administration-workflows/03-BROWSER.md
    - .planning/phases/03-administration-workflows/03-ADMIN-MATRIX.md
    - .planning/phases/03-administration-workflows/03-04-EXECUTION.json
  modified:
    - tests/compat/run.sh
    - tests/compat/probe.php
    - tests/compat/administration-entry.php
    - admin/new.php
    - .planning/phases/03-administration-workflows/03-VALIDATION.md
key-decisions:
  - "Browser observations remain distinct from HTTP/PHP assertions; unavailable no-JS and unobserved keyboard sequences require human acceptance."
  - "Resolve runtime targets once and pin immutable images; allow documentation-only revisions only when the exact source fingerprint remains unchanged."
  - "Render persisted values after a successful corrected edit; preserve raw received values only for rejected outcomes."
requirements-completed: [ADMIN-01, ADMIN-02, ADMIN-03, UX-01]
coverage:
  - id: real-authenticated-http-boundaries
    description: Owned loopback entry/options.php/selected-trash requests and independent denied-write snapshots
    requirement: ADMIN-03
    verification:
      - kind: e2e
        ref: tests/compat/run.sh#browser-fixture-smoke-all
        status: pass
    human_judgment: false
  - id: supported-administration-evidence
    description: Exact eight-case positive matrix, inherited preservation/workflows, lint and corrupted evidence rejection
    requirement: ADMIN-01
    verification:
      - kind: integration
        ref: .planning/phases/03-administration-workflows/03-ADMIN-MATRIX.md
        status: pass
      - kind: integration
        ref: tests/compat/run.sh#administration-evidence-self-test
        status: pass
    human_judgment: false
  - id: corrected-edit-persisted-display
    description: Successful corrected edits display saved dates and remove rejection controls
    requirement: ADMIN-01
    verification:
      - kind: integration
        ref: tests/compat/administration-entry.php#corrected_update_renders_saved_start
        status: pass
      - kind: manual_procedural
        ref: .planning/phases/03-administration-workflows/03-BROWSER.md#observed-entry-behavior
        status: pass
    human_judgment: false
  - id: complete-browser-keyboard-and-no-js-acceptance
    description: Actual entry/list/settings keyboard, native picker, no-JS and complete recovery/outcome acceptance
    requirement: UX-01
    verification: []
    human_judgment: true
    rationale: Genuine no-JS, complete keyboard traversal/notice focus and several full browser recovery/mixed-outcome sequences remain pending in 03-BROWSER.md.
duration: 535min elapsed including stalled executors
completed: 2026-10-05
status: complete
---

# Phase 03 Plan 04: Real Administration Transport and Pinned Evidence Summary

**Real authenticated administration requests and 18 pinned scenario cells prove save/authority/preservation boundaries, with actual browser observations and explicit remaining human checks.**

## Performance

- Started: 2026-10-04T21:31:24Z; completed: 2026-10-05T06:26:14Z. Elapsed time includes the interrupted original and retry executors; it is not active execution time.
- Tasks: 3; eight source RED/GREEN commits plus scoped evidence documentation.
- Final HTTP all fixture: 17 seconds, 48 named assertions, zero PHP/HTTP errors, cleanup PASS.
- Supported evidence build: 541 seconds, including resolution/lint/18 cells/teardown. Validator: 0.21 seconds; actual corruption self-test: 2.89 seconds.
- Corrected-edit focused case: 73 assertions PASS; warm probe 0.0788 seconds, cold fixture about 12 seconds.

## Accomplishments

- Random owned Compose projects expose synthetic WordPress only on loopback; private credentials/session files and validated project labels constrain access and teardown.
- Actual login/cookies/rendered nonces and form POST prove entry and options.php saves. Independent snapshots prove protected/unknown/falsey settings, unselected records and unauthorized/invalid-nonce/bypass/intent/replay guards.
- Browser calendar, equal dates, minute 17, multi-day Space, invalid received-value correction, summary-link focus, six settings jump targets, settings save, trash Confirm/Cancel/Undo and pagination were observed. Unobserved interactions are listed explicitly in 03-BROWSER.md.
- Six supported runtime cells each passed 896 administration assertions, exact eleven-case preservation and fresh full workflows. All eleven changed PHP files linted on all three branches (33 checks).
- Source-bound evidence validates 61 tracked PHP/JS/CSS/harness/Compose files, exact runtime/image/case/check identities and zero errors. A clean control plus twenty genuinely corrupted reports produced 21 passing self-test checks.

## Task Commits

1. 03-04-01: RED `c9d3163`; GREEN `1d51131` — owned real HTTP entry tracer.
2. 03-04-02: RED `2488ca4`; GREEN `def9b51` — actual settings and mutation-guard HTTP aggregation.
3. Browser-discovered correction: RED `ef92b95`; GREEN `63f0ace` — successful corrected edits render persisted values.
4. 03-04-03: RED `230276a`; GREEN `edf959e` — pinned matrix build/validate/self-test.

Browser evidence: `e7e70ce`; matrix closeout: `b210224`. Each task retains real fail-first evidence and subsequent passing behavior; no missing RED was retroactively invented.

## Runtime and Source Evidence

Targets resolved once at 2026-10-05T06:03:24Z: WordPress 7.0.6 and 7.1.2 × PHP 8.3.35, 8.4.26 and 8.5.11. Immutable official image IDs, per-case checks, durations and source fingerprints are recorded in 03-ADMIN-MATRIX.md. Snapshot `edf959e75c7f74f692a6f57cb33749648b3d0f06` and documentation revision `e7e70ce` contain identical measured source. PHP 8.2 is diagnostic-only.

Per administration cell: entry-create 9; entry-recovery 130; entry-controls 73; settings-save 54; settings-sections 170; list-single 30; list-navigation 266; list-bulk 164. Total 896/cell and 5,376 over six cells, all active/ready with zero warnings/fatals/plugin errors.

All six preservation cells retain the exact eleven-case registry. All six full-workflows cells exercise fresh synthetic CRUD/public/feed/CSV paths; migrated public/feed/template and CSV integration remain assigned to Phases 04/05. The actual WordPress 7.1.2/PHP 8.3 preservation matrix cell passed in 45 seconds before teardown. A redundant standalone retry and Docker diagnostic stalled and were terminated (exit 137); no extra PASS is claimed.

## Deviations from Plan

**Rule 1 — Corrected-edit display bug.** Actual browser correction persisted successfully but rendered stale rejected date text/replacement controls. Four rendering assertions failed while storage/identity checks passed. admin/new.php now reloads the persisted row only on successful updates, derives dates/time and removes raw rejection controls. RED/GREEN and browser retest prove the fix; failed-state recovery remains separate.

**Recovery.** The user authorized interrupting an executor stalled roughly eight hours and retrying. The retry also stopped reporting; parent completed HTTP/browser work and a narrowly owned executor completed Task 3. 03-04-RECOVERY.md retains that reconciliation. Existing completed Plans 01–03 were preserved.

## Threat Flags

- T-03-16: synthetic users/data, loopback_only=true, private same-user session metadata; no credentials committed.
- T-03-17: repository/project/label ownership and private metadata validated before stop; foreign metadata contract RED/GREEN and independent owned cleanup.
- T-03-18: actual WordPress admin/subscriber sessions, rendered nonces and denied-write snapshots; 48 HTTP checks PASS.
- T-03-19: exact positive eight-case/runtime/source evidence, zero errors and twenty real corruption rejections.
- T-03-20: all eighteen matrix cells and both parent smoke/browser sessions report owned teardown; temporary copies removed. Aborted redundant pull did not produce startup output; no unobserved Docker inventory claim.
- T-03-SC: no dependencies or package installation added. Phase-wide ASVS L1/high threat verification remains owned by secure-phase.

## Issues Encountered

Available browser control exposes no genuine JavaScript-disable setting or engine version. Complete Tab/Enter traversal, automatic notice focus, new-choice/radio/no-time/midnight recovery and mixed list browser outcomes remain required human items. Existing integration evidence does not certify those observations. The exact pending procedures are in 03-BROWSER.md.

ADMIN-03/unclassified and three descriptor-less product prohibitions remain flagged downstream review items. No descriptor/outcome or product intent is fabricated from matrix results.

## User Setup Required

No service or package setup. OrbStack provided the disposable fixtures. Human acceptance can start/stop a fresh owned fixture using ADMIN-BROWSER.md.

## Next Phase Readiness

All planned implementation/evidence tasks are delivered; Phase 03 is awaiting review, security, regression and goal verification. Global requirements and phase advancement remain pending until required acceptance is resolved. Validation retains draft/nyquist=false and wave_0_complete=true honestly.

## Self-Check: PASSED

All task source commits and planned files exist; full matrix and actual validator/self-test passed on identical committed source, browser observations and teardown are recorded, and unavailable required interactions remain explicit.

## Post-review current-source evidence

Subsequent review fixes and HTTP-discovered entity-page loading corrections are certified by the rebuilt 03-ADMIN-MATRIX.md and 03-REVIEW-CLOSEOUT.md. This document retains its original task history/counts/source. Current source has 6384 administration checks across six runtimes,54 HTTP checks and39 supported PHP syntax checks. Browser/intent acceptance remains human-needed.
