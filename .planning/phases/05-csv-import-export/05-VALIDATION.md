---
phase: "05"
slug: "csv-import-export"
status: draft
nyquist_compliant: false
wave_0_complete: false
created: "2026-10-11"
---

# Phase 05 — Validation Strategy

> Planning contract; no Phase 05 implementation tests have run. Align task IDs and threat references with the checked plans before execution.

## Test Infrastructure

| Property | Value |
|---|---|
| Framework | Existing real WordPress/PHP assertion harness, Compose database, E_ALL |
| Config | `tests/compat/run.sh`, `tests/compat/compose.yaml`, `tests/compat/probe.php` |
| Quick run | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.3 --php 8.3 --scenario csv-workflows --case tracer-1.4` after first-plan routing |
| Full suite | `rtk proxy bash tests/compat/run.sh matrix --scenario csv-workflows --case all --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL` after required-case routing |
| Estimated runtime | Unmeasured; measure retained-cell cases. Cold image resolution/startup is separate. |

## Sampling Rate

- After every task commit: changed PHP lint plus the task's selected CSV case.
- After every wave: implemented CSV case aggregate; relevant administration, upgrade-preservation and public-publishing regressions for shared-helper changes.
- Before verify-work: exact required CSV case set across one resolved, pinned supported matrix, source-bound evidence validation, no warnings/fatals/plugin errors.
- Feedback target: retained-cell case assertions under 30 seconds; measure rather than claim a cold-cell bound. Report measured feedback latency in final evidence.

## Per-Task Verification Map

The planner must replace this case-level seed with its exact task IDs, waves and threat references. These are proposed new cases, not currently runnable tests.

| Case | Requirement | Secure behavior / contract | Test type | Status |
|---|---|---|---|---|
| tracer-1.4 | CSV-01, SEC-01 | Populated migrated export/import/duplicate retry retains IDs and values; textual status/read-back | Real-WP integration | Pending first slice |
| migrated-contracts | CSV-01 | Recognized 1.0–1.6 populated fixtures, same-site and fresh-destination round trips | Real-WP integration | Pending |
| import-rows | CSV-01, CSV-02, SEC-01 | All columns, strict validation before writes, row accounting, encoded hostile notices, status/association choices | Integration and DOM | Pending |
| import-retry | CSV-01, CSV-02, SEC-01 | Actual SQL failures and verified safe retry without duplicated entities or overwritten shows | Fault injection | Pending |
| upload-guards | SEC-01 | Real multipart upload; capability/method/nonce/readiness before side effects; bounded parsing/cleanup | Authenticated HTTP | Pending |
| export-filters | CSV-01, CSV-02, SEC-01 | Actual count/query equality, retained visit state, empty-result/reset, no database writes | HTTP and independent snapshots | Pending |
| export-literals | CSV-01, SEC-01 | Exact CSV values and quoting, D-17 guidance, supported/legacy statuses, no silent formula transforms | Independent parser assertions | Pending |
| conversion-confirm | CSV-02, SEC-01 | Reviewed counts/identities, Confirm/Cancel and stale/replayed intent rejection | HTTP and snapshots | Pending |
| conversion-retry | CSV-01, CSV-02, SEC-01 | Early/late INSERT/UPDATE/DELETE faults, verified identities/relations/deletion, all outcomes, recovery retry | Fault injection | Pending |

## Wave 0 Requirements

First tracer plan owns the shared harness registration and shared production seams. It must add `csv-workflows` to all runner/probe routing layers, case forwarding, nonempty assertions, required-set validation and fixtures before dependent commands are used.

- [ ] Independent CSV expectations and migrated-fixture adapter; no importer-derived expected values.
- [ ] Actual multipart HTTP upload checks plus trusted in-process fixture helper, with no production request bypass.
- [ ] Query-level SQL faults for import and conversion, snapshots and read-back mismatches.
- [ ] Source-bound matrix/evidence contract and positive/negative evidence validation.
- [ ] Retained owner-scoped browser fixture with CSV lifecycle support and safe cleanup.
- Existing harness is reused; no new testing framework installation.

## Manual-Only Verifications

| Behavior | Requirement | Why manual | Instructions |
|---|---|---|---|
| Stacked sections, upload labels/help, disclosure keyboard/focus, visible grouped results and another upload | CSV-02 | Actual browser interaction | Open owned fixture, complete keyboard upload/retry and inspect results; repeat without JavaScript |
| Real download, retained selected filters, no-match reset | CSV-01, CSV-02 | Actual browser download/visit state | Choose artist/tour/scope, download, verify selection; try empty selection then Reset |
| Reviewed conversion Confirm/Cancel and safe retry feedback | CSV-02, SEC-01 | Actual reviewed-action flow | Review identities/counts, cancel with no mutation, confirm, inspect each outcome and retry failed tour |
| Explicit spreadsheet text import and edit/re-import | CSV-01, SEC-01 | Client-specific behavior | Import all relevant columns as text, check representative literal values, edit/save and re-import; name observed client/version. D-17 accepts direct-open risk and authorizes no universal safety claim |

## Validation Sign-Off

- [ ] Every task has automated verification or an explicit first-plan dependency.
- [ ] No three consecutive tasks lack automated verification.
- [ ] All proposed routes/tests are implemented before use.
- [ ] No watch-mode commands; positive nonempty assertions and exact case membership.
- [ ] Supported targets/images are resolved once and pinned across CSV and inherited regression evidence.
- [ ] Actual human/browser/client acceptance is reported separately from automated evidence.
- [ ] Final task/threat map matches checked plans; Nyquist audit determines compliant status.

**Approval:** Pending execution and verification; this draft is not a test pass.
