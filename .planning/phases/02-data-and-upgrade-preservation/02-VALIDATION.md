---
phase: "02"
slug: "data-and-upgrade-preservation"
# status lifecycle: draft (seeded by plan-phase) → validated (set by validate-phase §6)
status: validated
nyquist_compliant: true
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
| 02-01-01 | 02-01 | 1 | DATA-01 | T-02-01, T-02-04 | Populated reconstructed 1.4 data upgrades once and remains identical on repeat load. | integration | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case tracer-1.4` | ✅ `probe.php` + migration module | ✅ green in fresh aggregate matrix |
| 02-01-02 | 02-01 | 1 | DATA-01 | T-02-01, T-02-02, T-02-03 | Failure never advances `db_version`; retry is duplicate-free; unsafe metadata is preserved and scoped. | integration | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case safety-1.4` | ✅ `probe.php` + migration module | ✅ green in fresh aggregate matrix |
| 02-02-01 | 02-02 | 2 | DATA-01 | T-02-05, T-02-06 | Reconstructed 1.0–1.2 sources preserve exact manifests across success, interruption, retry, and repeat load. | integration | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case versions-1.0-1.2` | ✅ `probe.php` + migration module | ✅ green in fresh aggregate matrix |
| 02-02-02 | 02-02 | 2 | DATA-01 | T-02-05, T-02-06 | Reconstructed 1.3 and 1.5 paths retain deterministic generated mappings and complete state. | integration | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case versions-1.3-1.5` | ✅ `probe.php` + migration module | ✅ green in fresh aggregate matrix |
| 02-02-03 | 02-02 | 2 | DATA-01 | T-02-05 | Current 1.6 performs no migration and every fixture preserves falsey/unknown settings across repeat load. | integration | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case current-1.6` and `--case settings-repeat` | ✅ `probe.php` + migration module | ✅ green in fresh aggregate matrix |
| 02-03-01 | 02-03 | 2 | DATA-02 | T-02-08, T-02-09 | Create/edit/copy/trash/restore retain identity and relationships; only selected rows change; blocked upgrades cannot mutate. | integration | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case show-lifecycle` | ✅ `probe.php` + CRUD module | ✅ aggregate and sparse-request regression green |
| 02-03-02 | 02-03 | 2 | DATA-02 | T-02-08, T-02-10 | Active or trashed dependencies block artist/venue deletion in view and handler without cascade. | integration | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case entity-guards` | ✅ `probe.php` + CRUD module | ✅ green in fresh aggregate matrix |
| 02-03-03 | 02-03 | 2 | DATA-02 | T-02-08, T-02-11 | Tour undo restores only owned eligible shows across repeated deletes and intervening reassignment. | integration | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case tour-undo` | ✅ `probe.php` + CRUD module | ✅ green in fresh aggregate matrix |
| 02-04-01 | 02-04 | 3 | DATA-01, DATA-02 | T-02-13 | Aggregate execution requires every preservation case exactly once and fails closed for missing/failed coverage. | integration | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case all` | ✅ explicit 11-case registry | ✅ green in all six fresh cells |
| 02-04-02 | 02-04 | 3 | DATA-01, DATA-02 | T-02-12, T-02-14 | Supported-runtime evidence contains only complete passing preservation and full-workflow cells at PHP 8.3+. | evidence | `rtk bash tests/compat/run.sh preservation-evidence --report .planning/phases/02-data-and-upgrade-preservation/02-PRESERVATION-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3` | ✅ report and parser | ✅ six-cell evidence and parser green |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] Plans 02-01 and 02-02: reconstructed source fixtures and expected manifests for every recognized database version 1.0–1.5, plus separate current 1.6 steady-state coverage.
- [ ] Plan 02-01: fixture loader and `upgrade-preservation` probe snapshotting prefix-aware rows, settings, and related post IDs across first load and repeat load.
- [ ] Plans 02-01 and 02-02: failure injection for schema/data steps; assert failure leaves the final database version unchanged and retry does not duplicate data.
- [ ] Plan 02-03: CRUD sequence covering edit, copy, selected-show trash/restore, referenced artist/venue deletion guards, and repeated tour trash/undo with intervening edits.

---

## Manual-Only Verifications

All DATA-01 and DATA-02 behaviors have automated integration coverage. No manual-only
verification remains.

---

## Validation Sign-Off

- [x] All tasks have `<automated>` verification
- [x] Sampling continuity: no 3 consecutive tasks without automated verification
- [x] Wave 0 test references exist in the repository harness
- [x] No watch-mode flags
- [x] Current aggregate and six-cell evidence completed after the optional-request-fields change
- [x] `nyquist_compliant: true` set in frontmatter

**Approval:** complete — all automated Phase 02 evidence is green.

## Validation Audit 2026-10-04

| Metric | Count |
|--------|-------|
| Gaps found | 2 |
| Resolved | 2 |
| Escalated | 0 |

The focused `optional-request-fields` behavior passed on WordPress 7.1.2/PHP
8.3.35 with zero plugin warnings. A subsequent completed OrbStack run against source
revision `592d86806a637b2ee509124537950799d4a7227e` passed all 11 aggregate cases
and the full-workflows scenario in every WordPress 7.0.6/7.1.2 × PHP
8.3.35/8.4.26/8.5.11 cell, with zero warnings, fatals, and plugin errors. The
`preservation-evidence` validator and PHP 8.3 lint also passed.

After closing the T-02-08 mutation-readiness gap in source revision
`61d60fd9c9afb09b65a653c8b7e3c0935ee5057b`, both six-cell matrices were run again
and passed. The lifecycle case now also proves blocked valid-nonce calls across all
nine affected mutation handlers leave GigPress tables/options unchanged, prevent CSV
uploads, reject forged requests without a nonce, and preserve the valid legacy tour-map
action.

### Mutation-readiness guard RED/GREEN evidence

- **RED:** The updated `show-lifecycle` probe (including the nine-handler readiness
  assertions) was run against pre-guard source revision `592d86806a637b2ee509124537950799d4a7227e`
  in an isolated temporary checkout. The WordPress 7.1.2/PHP 8.3.35 cell returned
  `FAIL` (`Post-upgrade show lifecycle did not preserve handler semantics`), as
  expected before the guard changes.
- **GREEN:** On source revision `61d60fd9c9afb09b65a653c8b7e3c0935ee5057b`, the same
  focused command passed: `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3
  --scenario upgrade-preservation --case show-lifecycle` (WordPress 7.1.2/PHP 8.3.35,
  `manifest_matches: true`, `repeat_matches: true`). The probe exercises add/update
  artist, add/update venue, add/update tour, CSV import, empty trash, and legacy tour
  mapping while readiness is blocked, checks table/option snapshots and upload absence,
  and verifies forged nonce rejection for each request.
- The refreshed six-cell aggregate/full-workflow matrix records PASS with zero warnings,
  fatals, or plugin errors in all supported cells. The evidence parser also passed:
  `rtk bash tests/compat/run.sh preservation-evidence --report
  .planning/phases/02-data-and-upgrade-preservation/02-PRESERVATION-MATRIX.md
  --wp-lines 7.0,7.1 --php-min 8.3`.

## Validation Audit 2026-10-04 — Dynamic Runtime Resolution

| Metric | Count |
|--------|-------|
| Gaps found | 0 |
| Resolved | 0 |
| Escalated | 0 |

On source revision `a3591fb13521bbddaea84e9be479aea302cee1b3`, the runner resolved each
WordPress line's latest stable patch once and reused it across PHP branches, alongside
the PHP branches currently listed upstream at or above the PHP 8.3 minimum. Both
complete matrices were rerun and passed across WordPress 7.0.6 and 7.1.2 with PHP
8.3.35, 8.4.26, and 8.5.11. All 11 required preservation cases and the full
administration, shortcode, RSS, iCalendar, and CSV workflows passed in every cell
with zero warnings, fatals, or plugin errors. The evidence validator passed after
checking every runtime pairing, consistent WordPress patch per line, image tag and
digest, status, and error count.

The PHP-floor recovery lifecycle passed on both WordPress lines while moving from
PHP 8.3.35 to diagnostic-only PHP 8.2.34 and back, preserving plugin activation and
stored data. The controlled WordPress menu warning diagnostic also passed on PHP 8.2
and remains diagnostic-only; it does not make PHP 8.2 a supported target. All tracked
PHP files passed syntax linting on PHP 8.3, 8.4, and 8.5. The eight-case menu contract
and compatibility metadata checks passed.
