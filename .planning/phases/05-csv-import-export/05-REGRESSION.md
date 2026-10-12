---
phase: 05-csv-import-export
status: passed
observed: 2026-10-12
source_revision: f7eaca23a052655333b23f73f2045f5342741e4d
runtime: OrbStack Docker context `orbstack` 29.8.2
---

# Cross-phase regression evidence

No project-wide `workflow.test_command` or conventional Makefile, Justfile, package, Cargo, Go, or Python test target is configured. The compatibility runner is the project's executable integration suite, so concrete prior-phase and current-phase contracts were run instead of counting a generic `true` as test evidence.

| Suite | Result |
|---|---|
| `rtk proxy bash -n tests/compat/run.sh` | PASS |
| `rtk proxy bash tests/compat/run.sh self-test` | PASS: matrix ordering, PHP support parsing, diagnostic exclusion, multipart probe binding, and Compose cleanup contracts |
| `rtk proxy bash tests/compat/run.sh menu-contract --wp 7.1.2 --php 8.3 --cases preferred,index-zero,missing,duplicate,empty,single,order-conflict,no-global-mutation` | PASS: all eight menu cases |
| `rtk proxy bash tests/compat/run.sh runtime-floor --plugin gigpress/gigpress.php --wp-lines 7.0,7.1 --supported-php 8.3 --diagnostic-php 8.2` | PASS: WordPress 7.0.7 and 7.1.3; PHP 8.3.35 → diagnostic 8.2.34 → 8.3.35; active state/data preserved and supported operation recovered |
| Phase 02 upgrade-preservation matrix and `preservation-evidence` validator | PASS: six runtime cells, 11 cases per cell |
| Phase 04 public matrix, validator, and self-test | PASS: six cells, nine cases per cell, 852 assertions, zero warnings/fatals/plugin errors; 22 corruptions rejected |
| Phase 05 CSV matrix, validator, and self-test | PASS: six cells, nine cases per cell, 54 records; 12 corruptions rejected |
| Authenticated CSV multipart HTTP smoke | PASS: 16 checks, imported/duplicate/export routes and cleanup verified |
| Focused Phase 05 `conversion-retry` | PASS: 33 assertions, including target-read failure, transactional rollback/ID collision, and rejection of nontransactional tours storage |
| Focused Phase 05 `export-filters` | PASS: 16 assertions, including editor import/export/preview with missing capability configuration |

The JavaScript-disabled browser behavior remains waived and untested per the user's acceptance. Spreadsheet-client behavior was not included in this regression run and is not claimed.
