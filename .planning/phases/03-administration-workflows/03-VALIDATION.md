---
phase: "03"
slug: "administration-workflows"
status: draft
nyquist_compliant: false
wave_0_complete: false
created: "2026-10-04"
---

# Phase 03 — Validation Strategy

> Draft validation contract for planning and execution. No Phase 03 implementation tests have run. The planner must replace the provisional map with actual task IDs, waves, threat IDs, and commands tied to the files its plans create.

## Test Infrastructure

| Property | Value |
|----------|-------|
| Framework | Existing OrbStack/Compose real WordPress PHP probe and disposable MariaDB harness |
| Config file | `tests/compat/compose.yaml`; dispatcher `tests/compat/run.sh` |
| Existing quick regression | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case show-lifecycle` |
| Existing full preservation | `rtk proxy bash tests/compat/run.sh matrix --wp-lines 7.0,7.1 --php-supported upstream --wp-patches latest --php-min 8.3 --scenario upgrade-preservation --case all --error-reporting E_ALL` |
| Existing workflow smoke | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario full-workflows` |
| New administration checks | MISSING — Wave 0 must extend the existing dispatcher/probe before new scenario/case commands are runnable |
| Estimated runtime | Unmeasured for Phase 03; cold container starts/pulls are not a sub-30-second sampler |

The explicit `7.1.2` patch above is a proven prior-phase command, not a claim that it remains latest. Resolve current WordPress patches and supported PHP branches at the phase gate. Each automated command must exit nonzero on an assertion/error and prove a nonempty required assertion set; zero assertions must never pass.

## Sampling Rate

- After every task commit: lint changed PHP and run the relevant administration case once the plan has created it; retain the nearest existing lifecycle regression for preservation.
- After every plan wave: run the new administration cases covered by that wave and applicable existing lifecycle/settings regressions.
- Before phase verification: all Phase 03 integration cases, preservation regressions, supported runtime matrix, and recorded browser acceptance must be complete.
- Target warm feedback latency: under 30 seconds for a focused sampler. Measure during execution; report longer measured latency and cold-start overhead honestly.
- No watch-mode commands. Docker access requires the authorized execution context; lack of sandbox socket access does not establish runtime unavailability.

## Provisional Requirement Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| Planner assigns | Planner assigns | Planner assigns | ADMIN-01 | Planner assigns | Guarded writes, lossless invalid state, identity preserved | Real WP integration + browser | MISSING — Wave 0 administration form cases; existing show-lifecycle is regression only | New assertions missing | Pending |
| Planner assigns | Planner assigns | Planner assigns | ADMIN-02 | Planner assigns | Confirm/cancel and selected-only writes, truthful outcomes | Real WP integration + browser | MISSING — Wave 0 list/selection/confirmation cases | New assertions missing | Pending |
| Planner assigns | Planner assigns | Planner assigns | ADMIN-03 | Planner assigns | Real option save/reload preserves protected, unknown, falsey values | WordPress options integration | MISSING — Wave 0 actual settings save cases; bootstrap checks alone insufficient | New assertions missing | Pending |
| Planner assigns | Planner assigns | Planner assigns | UX-01 | Planner assigns | Escaped text feedback, labeled/keyboard controls | Rendered markup + browser | MISSING — Wave 0 markup assertions; manual browser checks below | New assertions missing | Pending |

This map is an input to the planner, not evidence that any requirement is covered. Final plan verification must use the populated per-task map.

## Wave 0 Requirements

- [ ] Extend existing runner/probe with an explicit administration scenario/case registry and fail-closed, nonempty assertion results; mark all proposed files/flags/cases NEW in plans.
- [ ] Date/time fixtures: native and legacy request adapters, invalid raw values and impossible stored dates, optional-time sentinel versus true midnight, uncommon existing minutes, multi-day on/off, unchanged expiration semantics.
- [ ] Form recovery/save fixtures: every input retained, new-entity marker/reveal state, related-post radio/notes, edit identity, copy source preservation, unchanged update success, blocked readiness, failed write after related creation and retry without duplicate creation.
- [ ] List fixtures: all filters/navigation links, zero/single/multiple pages, page size distinct from SQL limit, reset preserving scope/sort/size, per-user scope/size persistence and request-only sort.
- [ ] Mutation fixtures: no/duplicate/malformed selection, confirm/cancel/bypass, invalid nonce/capability/readiness, individual and bulk trash, mixed missing/already-trashed/failed IDs, unchanged unselected rows, undo only confirmed changes, post-action page clamping.
- [ ] Settings fixtures: six sections and one save; actual registered sanitizer/options submission and reload; unknown scalar/nested values, false/zero/empty values, protected hidden/sticky metadata, unchecked flags, unknown untouched radio/select values.
- [ ] Provide a disposable browser fixture or explicit existing supported local-site procedure. Record setup/teardown and keyboard/no-JS checks without assuming the current callback-only runner exposes HTTP.
- [ ] No new test framework or host PHP install required; reuse container-owned PHP and existing assertions.

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Picker and rejected date correction | ADMIN-01, UX-01 | Native controls sanitize invalid values; PHP rendering cannot prove browser interaction | Enter/pick valid dates; submit invalid raw dates through the supported correction path; verify exact received input remains editable and summary links reach the affected controls. Check incomplete native date input separately; do not claim recovery of text the browser never submits. |
| Optional time and multi-day keyboard operation | ADMIN-01, UX-01 | Focus, reveal and disabled control behavior need browser evidence | Use Tab/Space/Enter to toggle multi-day, select/clear time, and correct end date; verify no forced blur and usable no-JS correction. |
| Filter, selection and confirmation flow | ADMIN-02, UX-01 | Keyboard selection/cancel/navigation cannot be established from PHP callbacks alone | Filter, paginate, reset, select rows, confirm/cancel individual/bulk trash; verify explicit count/identities, visible retained choices and result text. Repeat no-JS confirmation. |
| Settings discovery/save | ADMIN-03, UX-01 | Jump-link focus and discoverability require interaction | Reach all six sections by keyboard; confirm Advanced visible, labels/help associations, one save action, preserved values after save/reload. |
| Errors and success announcements | UX-01 | Assistive feedback and deliberate focus need interaction | Trigger errors and successful adds; follow each summary link, verify clear notice/focus, saved-show edit/list links, and a fresh add form with no accidental saved-show overwrite. |

## Validation Sign-Off

- [ ] Every final task has an automated verify with observable failure direction or a specific Wave 0 dependency.
- [ ] No three consecutive tasks without an automated check.
- [ ] All MISSING commands/files are assigned to earlier or same-task creation with explicit execution order.
- [ ] No watch-mode flags or empty passing assertion registries.
- [ ] Warm feedback latency measured against the target; full matrix timing recorded separately.
- [ ] Manual browser evidence recorded with actual environment and outstanding gaps.
- [ ] `wave_0_complete` and `nyquist_compliant` updated only when their evidence exists.

**Approval:** Pending implementation and validation evidence.
