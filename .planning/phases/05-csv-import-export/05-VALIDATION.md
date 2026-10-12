---
phase: 05-csv-import-export
status: draft
nyquist_compliant: false
wave_0_complete: true
requirements:
  - CSV-01
  - CSV-02
  - SEC-01
updated: 2026-10-12
---

# Phase 05 Validation Strategy

This record maps every planned task to the evidence it must produce. It is intentionally draft: no Phase 05 command or human workflow is recorded as passed by planning, and `nyquist_compliant` remains false until the exact matrix, browser observations, assumptions, and threat mitigations are complete.

## Evidence policy

- A scenario file existing is not evidence that it ran.
- Automated completion requires the exact command, zero exit status, resolved runtime/image identity, current source/result fingerprints, and the expected contract binding in `05-CSV-MATRIX.md`.
- Human completion requires a named browser or spreadsheet client/version plus a per-row observation in `05-BROWSER.md`; unobserved rows remain pending.
- `csv-evidence validate` must reject missing, extra, duplicate, stale, failed, mismatched, or corrupt records. Its self-test must corrupt only copies and leave the authoritative report unchanged.
- Every executable task below defines the condition that makes its command fail; a failure cannot be converted into a prose pass.
- Container-backed commands remain pending until the Task 05-01-01 read-only `rtk docker info` precondition succeeds. The planning-time OrbStack socket denial establishes no daemon/image availability result; execution requests sandbox escalation for that read-only probe when needed.
- Any edit to a fingerprinted production, fixture, dispatcher, runner, or browser-bootstrap file makes affected reports provisional until their assigned producer rebuilds them and the final validator runs.

## Wave and dependency map

| Wave | Plan | Depends on | User-visible capability |
|------|------|------------|-------------------------|
| 1 | 05-01 | none | One migrated 1.4 show crosses the real import/export path; all 1.0-1.6 migrated/fresh-destination contracts follow. |
| 2 | 05-02 | 05-01 | Complete-row import validation, truthful outcomes, actual multipart guards, and retry behavior. |
| 3 | 05-03 | 05-02 | Exact export selection/count/download plus the stacked Import/Export screen and literal guidance. |
| 3 | 05-04 | 05-02 | Previewed, confirmed, exact-name, journaled, recoverable tour conversion. |
| 4 | 05-05 | 05-03, 05-04 | Exact runtime matrix, corruption checks, owned browser fixture, and evidence-derived validation. |

Wave 3 is intentionally parallel: 05-03 and 05-04 have no overlapping modified files.

## Per-task automated validation

| Task | Req | Planned command | Failure signal | Producing artifact | Status |
|------|-----|-----------------|----------------|--------------------|--------|
| 05-01-01 | CSV-01, CSV-02, SEC-01 | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario csv-workflows --case tracer-1.4` | Nonzero exit, missing real boundary, column/value drift, guard failure, or round-trip mismatch | `tests/compat/csv-workflows.php` | PASS (final matrix: six runtimes) |
| 05-01-02 | CSV-01, CSV-02 | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario csv-workflows --case migrated-contracts` | Nonzero exit, missing 1.0-1.6 fixture, source mutation, fresh-destination drift, or schema/version change | `tests/compat/csv-migrated.php` | PASS (final matrix: six runtimes) |
| 05-02-01 | CSV-02, SEC-01 | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario csv-workflows --case import-rows` | Nonzero exit, partial-row write, wrong outcome/count/detail, duplicate overwrite, invalid UTF-8 handling, or status-rule drift | `tests/compat/csv-import.php` | PASS (final matrix: six runtimes) |
| 05-02-02a | CSV-02, SEC-01 | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario csv-workflows --case upload-guards` | Nonzero exit, non-multipart shortcut, capability/nonce/type/size/path guard bypass, unsafe rendered notice, or retained temp file | `tests/compat/csv-import.php` | PASS (final matrix: six runtimes) |
| 05-02-02b | CSV-02, SEC-01 | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario csv-workflows --case import-retry` | Nonzero exit, duplicate overwrite, repeat insert, lost result/recovery action, or changed retry result | `tests/compat/csv-import.php` | PASS (final matrix: six runtimes) |
| 05-03-01 | CSV-01, CSV-02, SEC-01 | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario csv-workflows --case export-filters` | Nonzero exit, missing all-dates/upcoming/past assertion, selection/count/rows disagree, unauthorized query/emission, deleted or wrong-scope row, empty attachment, lost no-match choices, or Reset not restoring all three defaults | `tests/compat/csv-export.php` through the 05-01 `csv-workflows` registry | PASS (final matrix: six runtimes) |
| 05-03-02 | CSV-01, CSV-02, SEC-01 | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario csv-workflows --case export-literals` | Nonzero exit, UI/label/disclosure/reactive-preview/AJAX-action/no-JavaScript-fallback drift, wrong 23-column order, wrong time/status/blank-date rule, changed UTF-8 literal, or missing status-0 attention | `tests/compat/csv-export.php` through the 05-01 `csv-workflows` registry | PASS (focused WP 7.1.2 / PHP 8.3.35 rerun; refreshed 54-row matrix validates) |
| 05-04-01 | CSV-02, SEC-01 | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario csv-workflows --case conversion-confirm` | Nonzero exit, incomplete preview or per-show original/intended snapshot, preview/Cancel write, intent guard bypass, wrong exact-name branch, or retry duplicate | `tests/compat/csv-conversion.php` through the 05-01 `csv-workflows` registry | PASS (final matrix: six runtimes) |
| 05-04-02a | CSV-02, SEC-01 | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario csv-workflows --case conversion-retry` | Nonzero exit, lost stage/error/snapshot, no independent continuation, absent readback/ownership compare, deliberate reassignment overwritten, conflicted source deleted, incorrect retry, double delete, or false success | `tests/compat/csv-conversion.php` through the 05-01 `csv-workflows` registry | PASS (final matrix: six runtimes) |
| 05-04-02b | CSV-01, CSV-02 | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case all` | Nonzero exit, schema/version contract change, or prior preservation regression | Existing upgrade scenario | PASS (final matrix: six runtimes; 11/11 preservation cases) |
| 05-05-01a | CSV-01, CSV-02, SEC-01 | `rtk proxy bash tests/compat/run.sh csv-evidence --action build --report .planning/phases/05-csv-import-export/05-CSV-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3` | Nonzero exit, missing/failed exact `csv-workflows` member/runtime, unresolved image identity, or absent command/status/source/result/contract field | `05-CSV-MATRIX.md` provisional producer proof | PASS (final-source build) |
| 05-05-01b | CSV-01, CSV-02, SEC-01 | `rtk proxy bash tests/compat/run.sh csv-evidence --action validate --report .planning/phases/05-csv-import-export/05-CSV-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3` | Nonzero exit or exact registry/runtime/command/status/source/result/contract evidence missing, stale, mismatched, duplicated, or failed | `05-CSV-MATRIX.md` provisional producer proof | PASS (final-source validate) |
| 05-05-01c | CSV-01, CSV-02, SEC-01 | `rtk proxy bash tests/compat/run.sh csv-evidence --action self-test --report .planning/phases/05-csv-import-export/05-CSV-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3` | Nonzero exit, accepted corruption, changed authoritative report, or leaked temporary artifact | `05-CSV-MATRIX.md` provisional producer proof | PASS (12 corruption classes rejected) |
| 05-05-02 | CSV-02, SEC-01 | `rtk proxy bash tests/compat/run.sh csv-fixture --action smoke --wp 7.1.2 --php 8.3 --case all` | Nonzero exit, unavailable route/action/auth prerequisite, missing ownership metadata, cleanup failure, or guard bypass | `tests/compat/CSV-BROWSER.md` | PASS (HTTP smoke; human observation pending) |
| 05-05-03a | CSV-01, CSV-02, SEC-01 | `rtk proxy bash tests/compat/run.sh csv-evidence --action build --report .planning/phases/05-csv-import-export/05-CSV-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3` after Task 05-05-02's final fingerprinted source edit | Nonzero exit, missing/failed final `csv-workflows` member/runtime, unresolved image identity, or missing final-source fingerprint | Final `05-CSV-MATRIX.md` | PASS (final-source build) |
| 05-05-03b | CSV-01, CSV-02, SEC-01 | `rtk proxy bash tests/compat/run.sh csv-evidence --action validate --report .planning/phases/05-csv-import-export/05-CSV-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3` | Nonzero exit, stale/missing/duplicate/mismatched registry, runtime, command, status, source, result, or contract field | Final `05-CSV-MATRIX.md` | PASS |
| 05-05-03c | CSV-01, CSV-02, SEC-01 | `rtk proxy bash tests/compat/run.sh csv-evidence --action self-test --report .planning/phases/05-csv-import-export/05-CSV-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3` | Nonzero exit, accepted corruption, changed authoritative report, or leaked temporary artifact | Final `05-CSV-MATRIX.md` | PASS (12 corruption classes rejected) |
| 05-05-03d | CSV-01, SEC-01 | `rtk proxy bash tests/compat/run.sh public-evidence --action build --report .planning/phases/04-public-publishing/04-PUBLIC-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3` | Nonzero exit, stale final-runner fingerprint, or failed public case/runtime | Refreshed `04-PUBLIC-MATRIX.md` | PASS (refreshed public matrix) |
| 05-05-03e | CSV-01, SEC-01 | `rtk proxy bash tests/compat/run.sh public-evidence --action validate --report .planning/phases/04-public-publishing/04-PUBLIC-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3` | Nonzero exit or refreshed public report is missing, stale, mismatched, duplicated, corrupt, or failed | Refreshed `04-PUBLIC-MATRIX.md` | PASS |
| 05-05-03f | CSV-01, SEC-01 | `rtk proxy bash tests/compat/run.sh public-evidence --action self-test --report .planning/phases/04-public-publishing/04-PUBLIC-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3` | Nonzero exit, accepted inherited-public corruption, or changed authoritative report | Refreshed `04-PUBLIC-MATRIX.md` | PASS (22 corruption classes rejected) |
| 05-05-03g | CSV-01, SEC-01 | `rtk proxy bash tests/compat/run.sh matrix --scenario upgrade-preservation --case all --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL` | Nonzero exit, missing/failed preservation cell/case, runtime error, or exact results not used to refresh the report | Refreshed `02-PRESERVATION-MATRIX.md` | PASS (6 cells; 11 cases each) |
| 05-05-03h | CSV-01, SEC-01 | `rtk proxy bash tests/compat/run.sh preservation-evidence --report .planning/phases/02-data-and-upgrade-preservation/02-PRESERVATION-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3` | Nonzero exit or refreshed preservation report is missing an exact supported cell, recognized fixture, passing case, runtime identity, or required final-runner binding | Refreshed `02-PRESERVATION-MATRIX.md` | PASS (after current matrix refresh) |

## Execution results for Plan 05-05

The final-source CSV producer rebuilt 54 rows (nine exact cases across six WordPress/PHP cells) after the D-02 table correction; validation and all 12 corruption checks passed. The focused `export-literals` rerun passed on WordPress 7.1.2 / PHP 8.3.35 with the new 23-column format/example contract. The owned `csv-fixture --action smoke --wp 7.1.2 --php 8.3 --case all` passed 14 checks and cleaned up its temporary services. The final-source Phase 04 public report built and validated all six cells/nine cases (852 assertions, zero warnings/fatals/plugin errors); its 22 corruption checks passed.

The exact Phase 02 preservation matrix passed all 11 cases in all six cells. Diagnosis showed its old lifecycle assertions expected the retired one-click tour conversion and called CSV import without the required POST action; the test now verifies safe legacy rejection and current request guards. The refreshed report distinguishes the historical full-workflows evidence, which was not rerun in this task.

The fixture observed by the user is running in OrbStack at `http://127.0.0.1:32768`, with session `/var/folders/1l/t8mktk5j58g75tthg0q79qhw0000gn/T//gigpress-browser-iZKiGu/session.json`. The user identified macOS and the Codex app integrated browser; its version is not exposed. D-02 through D-17 have recorded outcomes in `05-BROWSER.md`; D-01 remains pending because a true JavaScript-disabled browser observation is unavailable in the embedded browser. No spreadsheet client has been observed.

## Exact Phase 05 evidence registry

The `csv-evidence` report must contain exactly these nine member cases for every resolved supported runtime. `all` is an aggregate selector and is not a registry member.

| Scenario | Case | Contract |
|----------|------|----------|
| csv-workflows | tracer-1.4 | Production import/export happy path for one migrated 1.4 show |
| csv-workflows | migrated-contracts | Recognized 1.0-1.6 and fresh-destination preservation |
| csv-workflows | import-rows | Complete-row validation and truthful outcomes |
| csv-workflows | import-retry | Duplicate-safe correction and retry |
| csv-workflows | upload-guards | Actual multipart, auth/file, cleanup, and CR04 rendering guards |
| csv-workflows | export-filters | All-dates/upcoming/past selection/count/query parity and empty no-download/retain/Reset |
| csv-workflows | export-literals | UI contract, 23 columns, status/time/date, UTF-8 exact literals |
| csv-workflows | conversion-confirm | Preview/Cancel/Confirm, per-show original/intended snapshot, and exact-name resolution |
| csv-workflows | conversion-retry | Durable journal, ownership comparison, conflict preservation, readback, independent faults, convergent owned retry |

## Required human evidence

All rows begin pending. Record them in `05-BROWSER.md`; never infer them from the CLI cases.

| ID | Decision coverage | Observation | Required identity | Status |
|----|-------------------|-------------|-------------------|--------|
| H-05-01 | D-01, D-02, D-03, D-04 | With JavaScript disabled, Import is first, Export remains visible, disclosure is operable, related-post is visible/unchecked with exact-ID explanation, and labels are exact. | Codex app integrated browser on macOS (version not exposed) + fixture URL | Partial: D-02 through D-04 pass; user confirmed D-01 is unavailable in this browser and the actual no-JavaScript observation remains pending |
| H-05-02 | D-05, D-06, D-07, D-08, D-13 | Actual multipart upload shows all four outcome counts/details, row/identity/reason/guidance, show-list/another-upload, correction/retry, duplicate no-overwrite, and status handling. | Codex app integrated browser on macOS (version not exposed) + fixture URL + fixture name | Pass: direct browser inspection confirmed the 1/1/1/1 counts, expanded row details, correction/retry guidance, and recovery actions; D-06, D-07, D-08, and D-13 also pass |
| H-05-03 | D-09, D-10, D-11 | Nonempty export shows actual artist/tour/all-dates-or-upcoming-or-past scope/count and downloads matching rows; empty export produces no download, retains the current choices and offers Reset filters; choosing Reset restores All artists/All tours/All dates, while a later visit starts at those defaults. | Codex app integrated browser on macOS (version not exposed) + fixture URL + user-confirmed downloaded scopes | Pass |
| H-05-04 | D-12, D-17 | Visible note and disclosure describe the bounded text-import workflow; exact formula-like/leading-plus values are retained. If a spreadsheet is exercised, record text-import/save/re-import only for the named client. | Codex app integrated browser on macOS (version not exposed); no spreadsheet client used | Pass: D-12 and D-17 pass; optional client workflow remains unobserved |
| H-05-05 | D-14, D-15, D-16 | Preview lists complete names/counts; Cancel is unchanged; Confirm/retry covers exact-one, none, ambiguity, independent failure, retained evidence, and final reassignment/deletion. | Codex app integrated browser on macOS (version not exposed) + fixture URL + user-confirmed 26-show outcome | Pass |

D-17 exact CSV values are retained by the automated contract. The observed HTTP smoke confirms only route readiness and text-import guidance presence; it does not observe a spreadsheet client. Excel-specific workflow and optional client acceptance remain pending. Direct-open interpretation risk remains accepted; no rewrite/block transformation is introduced.

## Edge and failure coverage

| Boundary | Required evidence |
|----------|-------------------|
| Empty/header-only import | One deterministic rejected/no-row result, zero writes, actionable guidance |
| Single-record import | Exactly one classified outcome and no hidden second write |
| BOM/encoding | Only a BOM at the first header is accepted/stripped; valid UTF-8 values stay exact; invalid encoding is actionable; no normalization/transcoding |
| Complete-row atomicity | Validation finishes before any row write; invalid rows leave no artist/tour/gig/post partials |
| Duplicate/retry | Existing show remains unchanged, duplicate is truthful, corrected retry converges once |
| Empty export | No attachment header/body, exact visible notice, current choices retained, explicit Reset filters offered, and defaults restored only when Reset is chosen |
| Literal export | Formula-like and leading-plus values decode exactly; no universal spreadsheet-safety claim |
| Optional time/date/status | 00:00:01 sentinel differs from real 00:00:00; absent End date is blank; status uses CR07 text; preexisting `0` is retained/flagged |
| Conversion ambiguity | More than one exact artist match yields no writes |
| Conversion fault stages | Early/late insert, update, delete, readback, and journal-write failures remain durable/actionable; per-show original/intended ownership is compared; a deliberate reassignment after partial progress is not overwritten and retains the source; owned retry converges |
| Hostile CR04 surfaces | Notice values, filenames, and system errors remain destination-safe in HTML, headers, logs, and CLI output |

## Threat-to-evidence map

| Threat ref | Plan | Mitigation evidence | Status |
|------------|------|---------------------|--------|
| T-05-01 | 05-01 | Complete-record validation, field formats, persisted identity/field readback before imported | PASS: exact matrix case tracer-1.4 |
| T-05-02 | 05-01 | One terminal outcome per parsed row, fixture-owned identity, count equality, unchanged duplicate/retry snapshots | PASS: import/retry outcome and snapshot cases |
| T-05-03 | 05-01 | Exact selected-case evidence dispatch, nonempty assertions, and zero runtime/plugin errors | PASS: exact registry and runtime checks |
| T-05-04 | 05-02 | Import capability, POST/action, nonce, and readiness reject before file reads/moves/writes | PASS: actual multipart authorization, POST, nonce, and readiness cases |
| T-05-05 | 05-02 | Actual uploaded origin, bounded nonempty file, exact structure, and owned temp cleanup | PASS: uploaded origin, file checks, size guard, cleanup |
| T-05-06 | 05-02 | Complete-row identity/write readback and stage-aware retry after query failure | PASS: import row persistence and retry cases |
| T-05-07 | 05-02 | Destination-safe CR04 row, filename, and bounded system-error rendering | PASS: hostile filename and visible result guidance checks |
| T-05-08 | 05-03 | Normalized filter/query and displayed count/download parity | PASS: export filter/count parity case |
| T-05-09 | 05-03 | Export capability/nonce rejection before query/headers/body | PASS: export capability and nonce guards |
| T-05-10 | 05-03 | Exact literal preservation plus D-17 bounded text-import guidance/accepted client risk | Partial: exact literals and scoped Excel-specific text-import copy verified; client workflow unobserved |
| T-05-11 | 05-04 | Owner/expiry/nonce/capability/source/replay-bound conversion intent | PASS: guarded preview/confirm conversion case |
| T-05-12 | 05-04 | All-candidate exact-name resolution, original/intended per-show journal snapshots, ownership comparison, deliberate-reassignment conflict preservation, mutation readback, and idempotent owned retry | PASS: ownership, conflict, readback, and retry cases |
| T-05-13 | 05-04 | Per-tour durable stage/failure results and independent continuation | PASS: independent continuation and journaled failure cases |
| T-05-14 | 05-05 | Exact unified matrix registry, runtime identities, post-final-edit Phase 05 build, affected Phase 02/04 report refresh, final validators, and corruption self-tests | PASS: CSV and Phase 04 evidence validate; Phase 02 matrix and validator pass |
| T-05-15 | 05-05 | Browser fixture ownership/session/status/check/cleanup evidence | Partial: automated smoke and owned session pass; human UI observations pending |
| T-05-16 | 05-05 | Named human clients, per-row observations, absent-evidence pending rule | Pending human observation |
| T-05-SC | all | No package install planned; any introduced install stops for legitimacy audit/checkpoint | PASS: no package installation introduced |

## Source coverage gate

| Source class | Required set | Planned coverage | Status |
|--------------|--------------|------------------|--------|
| GOAL | Understand import/export tasks/results; retain established exchange; reject unsafe mutation | Plans 05-01 through 05-05 | Complete in plans; evidence pending |
| REQ | CSV-01, CSV-02, SEC-01 | Every requirement appears in plan frontmatter and executable tasks | Complete in plans; evidence pending |
| RESEARCH | 23-column/value/time/status contract; import atomicity/multipart/CR04; migrated fixtures; export state; conversion journal/faults; exact matrix/browser lifecycle | Plans 05-01 through 05-05 and exact registry above | Complete in plans; evidence pending |
| CONTEXT | D-01 through D-17 | Tasks and H-05-01 through H-05-05 | Complete in plans; evidence pending |
| DEFERRED | FUTURE-02 arbitrary mappings/new formats | Explicitly excluded | Not a gap |

## Assumption and API gates

- CSV-01 unclassified behavior assumption remains unresolved until the source-fingerprinted registry and browser flow reveal no additional consumer or undocumented mapping.
- SEC-01 unclassified authorization assumption remains unresolved until every planned negative capability/nonce/file/rendering/conversion path is evidenced.
- The pre-plan assumption-delta detector reported no planning-critical delta; execution must still record any discovered delta rather than silently changing scope.
- The phase introduces no external service API. WordPress/GigPress calls are local product interfaces covered by the scenarios and source fingerprints.

## Completion rule

Set `wave_0_complete: true` only after every producer scenario, fixture, runner lane, report schema, and browser lifecycle named above exists. Set `nyquist_compliant: true` and advance `status` only when:

1. after the final fingerprinted source change, the exact Phase 05 build succeeds and its validate/self-test commands accept only the current matrix evidence,
2. refreshed Phase 04 public build/validate/self-test and refreshed Phase 02 upgrade-preservation matrix/report validation succeed against the final shared runner,
3. H-05-01 through H-05-05 contain named observations or an explicitly accepted pending external-client row consistent with D-17,
4. every T-05-01 through T-05-16 mitigation is evidenced,
5. the two unclassified assumptions are resolved or explicitly accepted by the developer, and
6. no source item is missing or silently reduced.
