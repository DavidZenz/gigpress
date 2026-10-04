---
phase: "02"
slug: "data-and-upgrade-preservation"
# status lifecycle: draft (seeded by plan-phase) → validated (set by validate-phase §6)
status: draft
nyquist_compliant: false
wave_0_complete: false
created: "2026-10-04"
---

# Phase 02 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Repository-owned disposable WordPress/PHP/MariaDB integration harness |
| **Config file** | `tests/compat/compose.yaml`, `tests/compat/run.sh` |
| **Quick run command** | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case all` (tracer and targeted cases originate in Plan 02-01; aggregate `all` is finalized in Plan 02-04) |
| **Full suite command** | `rtk bash tests/compat/run.sh matrix --scenario upgrade-preservation --case all --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL` (finalized in Plan 02-04) |
| **Estimated runtime** | To measure after Wave 0; quick probe is one disposable WordPress/PHP cell |

---

## Sampling Rate

- **After every task commit:** Run the focused upgrade-preservation cell once Wave 0 adds it; before that, run only the verification specified in that task.
- **After every plan wave:** Run the full upgrade-preservation matrix after the scenario exists.
- **Before `$gsd-verify-work`:** The full matrix must pass.
- **Max feedback latency:** Measure in Wave 0; keep the focused cell under 30 seconds where practical.

---

## Per-Task Verification Map

Task assignments below are sourced from the final Phase 02 plans. Commands remain pending until their owning execution wave creates the named scenario/case and records a passing result.

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 02-01-01 | 02-01 | 1 | DATA-01 | T-02-01, T-02-04 | Populated reconstructed 1.4 data upgrades once and remains identical on repeat load. | integration | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case tracer-1.4` | ❌ Wave 1 | ⬜ pending |
| 02-01-02 | 02-01 | 1 | DATA-01 | T-02-01, T-02-02, T-02-03 | Failure never advances `db_version`; retry is duplicate-free; unsafe metadata is preserved and scoped. | integration | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case safety-1.4` | ❌ Wave 1 | ⬜ pending |
| 02-02-01 | 02-02 | 2 | DATA-01 | T-02-05, T-02-06 | Reconstructed 1.0–1.2 sources preserve exact manifests across success, interruption, retry, and repeat load. | integration | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case versions-1.0-1.2` | ❌ Wave 2 | ⬜ pending |
| 02-02-02 | 02-02 | 2 | DATA-01 | T-02-05, T-02-06 | Reconstructed 1.3 and 1.5 paths retain deterministic generated mappings and complete state. | integration | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case versions-1.3-1.5` | ❌ Wave 2 | ⬜ pending |
| 02-02-03 | 02-02 | 2 | DATA-01 | T-02-05 | Current 1.6 performs no migration and every fixture preserves falsey/unknown settings across repeat load. | integration | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case current-1.6` and `--case settings-repeat` | ❌ Wave 2 | ⬜ pending |
| 02-03-01 | 02-03 | 2 | DATA-02 | T-02-08, T-02-09 | Create/edit/copy/trash/restore retain identity and relationships; only selected rows change; blocked upgrades cannot mutate. | integration | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case show-lifecycle` | ❌ Wave 2 | ⬜ pending |
| 02-03-02 | 02-03 | 2 | DATA-02 | T-02-08, T-02-10 | Active or trashed dependencies block artist/venue deletion in view and handler without cascade. | integration | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case entity-guards` | ❌ Wave 2 | ⬜ pending |
| 02-03-03 | 02-03 | 2 | DATA-02 | T-02-08, T-02-11 | Tour undo restores only owned eligible shows across repeated deletes and intervening reassignment. | integration | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case tour-undo` | ❌ Wave 2 | ⬜ pending |
| 02-04-01 | 02-04 | 3 | DATA-01, DATA-02 | T-02-13 | Aggregate execution requires every preservation case exactly once and fails closed for missing/failed coverage. | integration | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case all` | ❌ Wave 3 | ⬜ pending |
| 02-04-02 | 02-04 | 3 | DATA-01, DATA-02 | T-02-12, T-02-14 | Supported-runtime evidence contains only complete passing preservation and full-workflow cells at PHP 8.3+. | evidence | `rtk bash tests/compat/run.sh preservation-evidence --report .planning/phases/02-data-and-upgrade-preservation/02-PRESERVATION-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3` | ❌ Wave 3 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] Plans 02-01 and 02-02: reconstructed source fixtures and expected manifests for every recognized database version 1.0–1.5, plus separate current 1.6 steady-state coverage.
- [ ] Plan 02-01: fixture loader and `upgrade-preservation` probe snapshotting prefix-aware rows, settings, and related post IDs across first load and repeat load.
- [ ] Plans 02-01 and 02-02: failure injection for schema/data steps; assert failure leaves the final database version unchanged and retry does not duplicate data.
- [ ] Plan 02-03: CRUD sequence covering edit, copy, selected-show trash/restore, referenced artist/venue deletion guards, and repeated tour trash/undo with intervening edits.

---

## Manual-Only Verifications

All DATA-01 and DATA-02 behaviors are assigned automated integration coverage. No manual-only verification is currently identified.

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all missing test references
- [ ] No watch-mode flags
- [ ] Feedback latency target recorded after Wave 0
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
