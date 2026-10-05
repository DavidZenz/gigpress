---
phase: 03-administration-workflows
plan: "04"
task: 03-04-03
status: complete
completed: 2026-10-05
source_revision: edf959e75c7f74f692a6f57cb33749648b3d0f06
administration_assertions: 5376
runtime_cells: 6
scenario_cells: 18
build_seconds: 541
validator_seconds: 0.21
self_test_seconds: 2.89
self_test_assertions: 21
---

# Task 03-04-03: Administration Matrix Closeout

Eight administration cases pass with 896 real named assertions in each of six supported runtime cells. The same pinned targets pass the exact eleven-case preservation registry, fresh full workflows and container PHP lint. The evidence validator rejects incomplete, failed, erroneous or stale records and retains the separate browser/manual acceptance boundary.

## Owned Changes and Commits

- `230276a`: `test(03-04): require missing administration evidence to fail closed` — intentional RED contract in `tests/compat/run.sh`.
- `edf959e`: `feat(03-04): build and validate pinned administration matrix evidence` — build/validate/self-test, pinned matrix/lint/cell flags, actual runtime timings, active/readiness evidence and exact nonempty aggregate validation in `tests/compat/run.sh` and `tests/compat/probe.php`.
- This closeout, `03-ADMIN-MATRIX.md` and `03-VALIDATION.md` are the scoped evidence documentation. The parent owns the final plan summary and shared state/roadmap/requirements updates.

No production PHP, browser fixture/bootstrap, browser procedure, browser report or unrelated dirty files were changed by this task. Prior `browser-fixture` start/status/stop/smoke parsing and session ownership remain intact. No branch/worktree change, package installation, host PHP, broad staging, reset, clean, stash or bypassed Git hook was used.

## TDD Gate Compliance

`rtk proxy bash tests/compat/run.sh administration-contract-test` initially ran one named assertion and failed because the missing-report evidence validation behavior did not exist. `/tmp/gigpress-03-04-03-red.json` records the command, exit 1, target assertion, expected and actual results. `gsd_run check tdd-red-evidence` returned **RED_EVIDENCE_OK / target_test_failed** before the implementation commit. The final contract passes even after a real report exists because it checks a separate missing report in an owned private temporary directory.

GREEN verified the missing-report contract, rejection of a malformed/schema-empty record, container lint and a pinned WordPress 7.1.2/PHP 8.3.35 administration aggregate. All eight cases were active and ready with positive checks and zero errors. The parent's browser-discovered corrected-update regression added six entry checks and its committed fix is included in every matrix source fingerprint, bringing the total from 890 to 896. No separate refactor was needed.

## Runtime and Source Identity

Resolution occurred once at **2026-10-05T06:03:24Z** using WordPress.org's version-check endpoint and PHP.net's upstream supported-versions page. WordPress patches **7.0.6** and **7.1.2**, official immutable Docker image IDs and exact PHP patches were pinned throughout administration, preservation, fresh workflows and lint. PHP 8.2 is excluded from supported evidence.

| Branch | Exact PHP | Official image ID |
|---|---|---|
| 8.3 | 8.3.35 | `sha256:4abf7a450ee477dde967584f8174d7e03221d224c4971a0c38d84e7254426e64` |
| 8.4 | 8.4.26 | `sha256:85ee71a393b0f3f7a45b2e293978f1c9ab94fb421161080e54f911eeb2501bdc` |
| 8.5 | 8.5.11 | `sha256:9d881655fccdcfd19b779320ca819310b51061e1ba3c086012ecbf69a177d521` |

The machine record fingerprints **61 tracked PHP/JS/CSS/harness/Compose files**. Source snapshot `edf959e75c7f74f692a6f57cb33749648b3d0f06` and cell-observed revisions `edf959e75c7f74f692a6f57cb33749648b3d0f06` / `e7e70cee34eb804cb511d306651362456751d2a4` have identical committed source. The latter is a browser documentation commit. Validation checks ancestry, exact file set, current SHA-256 fingerprints and source equality against each observed revision; documentation-only HEAD advances do not invalidate identical tested code.

## Commands and Observed Results

| Command | Result |
|---|---|
| `rtk proxy bash tests/compat/run.sh administration-evidence --action build --report .planning/phases/03-administration-workflows/03-ADMIN-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3` | PASS, 541 seconds including resolution/lint/18 scenario cells/owned teardown |
| Same command with `--action validate` | PASS, 0.21 seconds |
| Same command with `--action self-test` | PASS, 21 named checks, 2.89 seconds |
| `rtk proxy bash tests/compat/run.sh administration-contract-test` | PASS, one named missing-report assertion |
| `rtk proxy bash -n tests/compat/run.sh` | PASS |
| `rtk proxy git diff --check` | PASS |

The exact resolved matrix commands, pinned versions/images, scenario outputs, named checks and durations are persisted in `03-ADMIN-MATRIX.md`. Container lint checked these eleven tracked PHP files on all three supported branches: `gigpress.php`, `admin/new.php`, `admin/handlers.php`, `admin/settings.php`, `admin/shows.php`, `tests/compat/probe.php`, the three `administration-*.php` case modules, `upgrade-preservation-crud.php` and `browser-bootstrap.php` (**33 syntax checks**, all PASS).

Administration counts per cell: entry-create **9**, entry-recovery **130**, entry-controls **73**, settings-save **54**, settings-sections **170**, list-single **30**, list-navigation **266**, list-bulk **164**. Every runtime has **896** checks, totalling **5,376**; exact cases, uniqueness, positive counts, active plugin/readiness and zero warnings/fatals/plugin errors are validated. All six preservation cells contain the original exact eleven-case registry and all six fresh-workflow cells pass create/edit/read, shortcode, RSS, iCalendar, CSV and duplicate preservation.

The inherited WordPress 7.1.2/PHP 8.3 preservation aggregate passed on the same pinned official image and source in **45 seconds before teardown**. A redundant standalone invocation of the plan's original cell command subsequently stalled before startup output in `docker compose pull`; a Docker status diagnostic also stalled. The parent directed termination of only those owned commands and reuse of the already measured equivalent matrix cell. Both terminated sessions returned exit **137**. This report does **not** claim that aborted retry passed. No further rerun or broad Docker cleanup was attempted.

## Actual Validator Output

```json
{"status":"PASS","schema":"gigpress-administration-evidence/v1","cells":6,"administration_cases":8,"assertion_count":5376,"preservation_cases":11,"warnings":0,"fatals":0,"plugin_errors":0,"source_revision":"edf959e75c7f74f692a6f57cb33749648b3d0f06"}
```

The self-test parsed the real clean report as a passing control, then invoked the public validator against twenty separately corrupted private temporary reports. Every corruption exited nonzero with the validator's explicit invalid-evidence result. Named checks: missing_case, duplicate_case, empty_checks, failed_check, failed_case, warning, fatal, plugin_error, missing_cell, duplicate_cell, stale_source, foreign_revision, altered_wp, altered_php, altered_image, missing_runtime, preservation_missing, workflows_failed, lint_missing and browser_overclaim. All copies were deleted; the clean record was not modified for negative tests.

## Preservation, Cleanup and Remaining Acceptance

All eighteen matrix cells exited successfully after removing only their randomized owned Compose projects and volumes; cleanup failure now fails a cell. Build temporary data and all validator/contract copies were removed. The parent's browser report records successful owned-session teardown and tab closure. The redundant retry stalled in pull before any up/start output; only its identified local process tree and the owned status command were terminated. A final live Docker inventory could not be observed while that diagnostic stalled, so no additional inventory claim is made. Ignored `.results` output and nonsecret `/tmp/gigpress-03-04-03-*.log` diagnostics remain available for the parent; no credentials/session files are committed.

Fresh workflows use synthetic fresh fixtures. Preservation fixtures are reconstructed from repository evidence. No live backup/site was tested; migrated public/CSV integration belongs to Phases 04/05. T-03-19 is covered by exact positive case/check/runtime/source validation and actual corruption rejection. No new production network endpoint, schema or authority boundary was added outside the plan's threat register.

`03-VALIDATION.md` now reflects observed integration and HTTP results and sets `wave_0_complete: true`. It retains `status: draft` and `nyquist_compliant: false`. `03-BROWSER.md` remains **human_needed**: genuine disabled-JS entry/list flows, complete Tab/Enter navigation and automatic notice focus, new-entity/radio/no-time/midnight recovery sequences, and mixed list browser outcomes remain pending. The ADMIN-03/unclassified edge and all three descriptor-less product prohibitions remain unresolved/flagged-unverified. Matrix/PHP/HTTP assertions do not certify them.

## Self-Check: PASSED

Both source task commits exist; the two source files and all three owned evidence documents exist. The clean validator, corruption self-test, missing-report contract, supported lint and all required matrix scenarios passed. No owned source changes remain uncommitted. The parent retains responsibility for final plan/shared tracking and any cross-phase manual-check ledger entries.
