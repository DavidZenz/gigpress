---
phase: "03"
slug: "administration-workflows"
status: draft
nyquist_compliant: false
wave_0_complete: true
created: "2026-10-04"
---

# Phase 03 — Validation Strategy

> Execution evidence is recorded in the completed slice summaries, 03-ADMIN-MATRIX.md and 03-04-MATRIX-CLOSEOUT.md. The eight-case registry, all case modules, HTTP fixture and evidence modes now exist and run with positive assertions. Wave 0 is complete. Status remains draft and nyquist_compliant false until required browser observations and outstanding review items are resolved; matrix results cannot certify those items.

## Test Infrastructure

| Property | Value |
|----------|-------|
| Framework | Existing OrbStack/Compose real WordPress PHP probe and disposable MariaDB harness |
| Config file | `tests/compat/compose.yaml`; dispatcher `tests/compat/run.sh` |
| Existing quick regression | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case show-lifecycle` |
| Existing full preservation | `rtk proxy bash tests/compat/run.sh matrix --wp-lines 7.0,7.1 --php-supported upstream --wp-patches latest --php-min 8.3 --scenario upgrade-preservation --case all --error-reporting E_ALL` |
| Existing workflow smoke | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario full-workflows` |
| Administration checks | `administration-workflows` exact eight-case registry; 896 assertions after the browser-discovered corrected-update regression |
| Browser/HTTP checks | `browser-fixture` owned loopback start/status/stop and entry/settings/guards/all HTTP smoke; actual observations belong to 03-BROWSER.md |
| Evidence validator | `administration-evidence --action build\|validate\|self-test`; validates source fingerprints, pinned runtime identities, exact cases and real corruption failures |
| Measured runtime | Focused cached aggregate: 14 seconds plus teardown; full supported build and fast validator timings are recorded in 03-04-MATRIX-CLOSEOUT.md |

Task 03-04-03 resolves current WordPress patches and supported PHP branches once per build, then pins WordPress versions and immutable official Docker image IDs across all administration, preservation, fresh-workflow and PHP lint runs. See the matrix record for the actual resolution timestamp and identities. Each automated command exits nonzero on missing/failed assertions or errors; zero assertions cannot pass.

## Sampling Rate

- After every task commit: lint changed PHP and run the relevant administration case once the plan has created it; retain the nearest existing lifecycle regression for preservation.
- After every plan wave: run the new administration cases covered by that wave and applicable existing lifecycle/settings regressions.
- Before phase verification: all Phase 03 integration cases, preservation regressions, supported runtime matrix, and recorded browser acceptance must be complete.
- Target warm feedback latency: under 30 seconds for a focused sampler. Measure during execution; report longer measured latency and cold-start overhead honestly.
- No watch-mode commands. Docker access requires the authorized execution context; lack of sandbox socket access does not establish runtime unavailability.

## Requirement Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 03-01-01 | 03-01 | 1 | ADMIN-01, UX-01 | T-03-01,02,05 | Authorized picker-to-save, sentinel and saved ID/fresh add | Real WP integration | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario administration-workflows --case entry-create` | Exists; completed slice and aggregate evidence | PASS integration; 9 checks |
| 03-01-02 | 03-01 | 1 | ADMIN-01, UX-01 | T-03-01,02,03,04 | Raw recovery, blocked snapshots, ID-safe retry/copy/edit | Real WP integration | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario administration-workflows --case entry-recovery` | Exists; completed slice and aggregate evidence | PASS integration; 130 checks |
| 03-01-03 | 03-01 | 1 | ADMIN-01, UX-01 | T-03-02,03,04 | Date/time/expiration storage and escaped label/error targets | WP integration + rendered markup | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario administration-workflows --case entry-controls` | Exists; completed slice and aggregate evidence | PASS integration; 73 checks |
| 03-02-01 | 03-02 | 2 | ADMIN-03, UX-01 | T-03-06,07,09 | Registered save/reload preserving baseline and programmatic updates | WordPress Options API integration | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario administration-workflows --case settings-save` | Exists; completed slice and aggregate evidence | PASS integration; 54 checks |
| 03-02-02 | 03-02 | 2 | ADMIN-03, UX-01 | T-03-07,08,09 | Six visible groups, explicit unchecked/current unknown values | WP integration + rendered markup | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario administration-workflows --case settings-sections` | Exists; completed slice and aggregate evidence | PASS integration; 170 checks |
| 03-03-01 | 03-03 | 2 | ADMIN-02, UX-01 | T-03-10,11,13,14 | Owner-bound preview/confirm/cancel, real selected-row write | Real WP integration | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario administration-workflows --case list-single` | Exists; completed slice and aggregate evidence | PASS integration; 30 checks |
| 03-03-02 | 03-03 | 2 | ADMIN-02, UX-01 | T-03-12,14,15 | Request domains, retained choices, narrow reset/safe pages | WP query/render/link integration | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario administration-workflows --case list-navigation` | Exists; completed slice and aggregate evidence | PASS integration; 266 checks |
| 03-03-03 | 03-03 | 2 | ADMIN-02, UX-01 | T-03-10,11,13,14,15 | Exact IDs, truthful per-ID results, changed-only undo | Real WP integration | `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario administration-workflows --case list-bulk` | Exists; completed slice and aggregate evidence | PASS integration; 164 checks |
| 03-04-01 | 03-04 | 3 | ADMIN-01, UX-01 | T-03-16,17,18,20 | Isolated authenticated HTTP save/read-back and owned teardown | Real HTTP + independent WP read-back | `rtk proxy bash tests/compat/run.sh browser-fixture --action smoke --wp 7.1.2 --php 8.3 --case entry` | Exists; committed HTTP fixture | PASS HTTP tracer; browser separate |
| 03-04-02 | 03-04 | 3 | ADMIN-01, ADMIN-02, ADMIN-03, UX-01 | T-03-06,10,16,17,18,20 | Actual options.php authority/save and observed browser/no-JS paths | HTTP snapshots + actual browser | `rtk proxy bash tests/compat/run.sh browser-fixture --action smoke --wp 7.1.2 --php 8.3 --case all` | Exists; def9b51 HTTP expansion | PASS HTTP 48 checks; browser acceptance separate |
| 03-04-03 | 03-04 | 3 | ADMIN-01, ADMIN-02, ADMIN-03, UX-01 | T-03-05,19 | Exact nonempty case/runtime/source evidence, existing preservation | Supported integration matrix + evidence validator | `rtk proxy bash tests/compat/run.sh administration-evidence --action build --report .planning/phases/03-administration-workflows/03-ADMIN-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3`; then same mode `--action validate` and `--action self-test` | Exists; 03-ADMIN-MATRIX.md and closeout | PASS 6 runtime cells / 5376 checks; validator / 21 self-test checks PASS |

Each `<automated>` in the plans is immediately followed by `<fails_when>` with observable failure direction. Positive assertion counts, exact named-case equality and zero runtime errors are required; an empty all/check predicate cannot pass. Threat references above use the phase-wide IDs T-03-01 through T-03-20; T-03-SC is the reserved repeated supply-chain row. ASVS level 1 is active with high-severity blocking. No package installation is scoped.

Inherited regressions are reused verbatim from Phase 02: `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case show-lifecycle`, `--case settings-repeat` and `--case all`. The syntax gate is the tracked container-owned `lint --php-branches ... --files ...`; NEW owned PHP files must be staged before lint because that existing runner requires git-tracked paths. Observed administration/preservation/fresh-workflow results and exact pinned commands are recorded in 03-ADMIN-MATRIX.md; the inherited WordPress 7.1.2/PHP 8.3 aggregate also passed as a pinned matrix cell. A redundant standalone retry stalled in Docker pull and was terminated; see the closeout report.

## Waves and Ownership

| Wave | Plans | Exclusive whole-file ownership | Needs → creates |
|------|-------|-------------------------------|-----------------|
| 1 | 03-01 | new.php, handlers.php, JS, CSS, run.sh, probe.php, old CRUD adapter, NEW entry case | Phase 02 patterns → real entry outcome/recovery and eight-case dispatch contract |
| 2 | 03-02 + 03-03 | Settings: gigpress.php/settings.php/CSS/NEW settings case; List: shows.php/handlers.php/old CRUD adapter/NEW list case | 03-01 outcome/registry → preserved grouped settings and confirmed retained-state list |
| 3 | 03-04 | run.sh/probe.php, NEW browser bootstrap/override/procedure/evidence/matrix, validation | All three slices → real HTTP/browser observations and supported evidence |

Wave 2 has zero whole-file overlap. List pagination metadata is local, using the existing shared output helper without writing gigpress.php. Server-rendered list confirmation does not require an asset write. No checkpoint is planned; genuinely unobserved browser interactions become end-of-phase human-check items. New production decisions/storage migrations are not introduced.

## Wave 0 Requirements

Wave 0 means assertion/dispatch creation before the matching implementation, folded into each leading tracer/expansion rather than a standalone horizontal infrastructure plan. The first registry and entry-create assertions are created in 03-01-01; later cases are created in their same task before their new command runs. Actual test creation/result plumbing is part of each task, not an assumed pre-existing command.

- [x] Extend existing runner/probe with an explicit administration scenario/case registry and fail-closed, nonempty assertion results; mark all proposed files/flags/cases NEW in plans.
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

- [x] Every final task has an automated verify with observable failure direction or a specific Wave 0 dependency.
- [x] No three consecutive tasks without an automated check.
- [x] All MISSING commands/files are assigned to earlier or same-task creation with explicit execution order.
- [x] No watch-mode flags or empty passing assertion registries.
- [x] Warm feedback latency measured against the target; full matrix timing recorded separately.
- [x] Manual browser evidence recorded with actual environment and outstanding gaps.
- [x] `wave_0_complete` and `nyquist_compliant` updated only when their evidence exists.

**Approval:** Automated matrix/evidence gates passed. Phase acceptance remains pending the required browser checks in 03-BROWSER.md and downstream review of ADMIN-03/unclassified plus the three descriptor-less prohibitions.

## Multi-Source Coverage Audit

Every GOAL/REQ/RESEARCH/CONTEXT item below has a concrete plan/task. Public publishing/CSV migration integration, entity deletion improvements, repair screens, runtime/framework/schema replacement and other-phase work are excluded by the canonical boundary, not dropped source items.

| Source | ID | Required outcome or constraint | Plan/task | Status |
|--------|----|--------------------------------|-----------|--------|
| GOAL | Phase 03 | Enter dates/times, manage show lists and find settings with clearer guidance | 03-01–04 | COVERED |
| GOAL | SC-1 | Clear date/optional time/multi-day/expiration with retained invalid values | 03-01-01–03 | COVERED |
| GOAL | SC-2 | Visible retained choices, selected-only bulk and reported result | 03-03-01–03 | COVERED |
| GOAL | SC-3 | Clear contextual settings groups with usable keys/values/meanings | 03-02-01–02 | COVERED |
| GOAL | SC-4 | Labels/headings, keyboard, text success/errors | 03-01–03 plus 03-04-02 | COVERED |
| REQ | ADMIN-01 | Complete add/edit entry and correction | 03-01, 03-04 | COVERED |
| REQ | ADMIN-02 | Complete list/filter/selection/outcome | 03-03, 03-04 | COVERED |
| REQ | ADMIN-03 | Complete grouped preserving settings save | 03-02, 03-04 | COVERED |
| REQ | UX-01 | Changed-control semantics and actual keyboard/text feedback | All four plans | COVERED |
| RESEARCH | Raw state/outcome | Separate raw strings, checked/radio choices and normalized writes | 03-01-01–02 | COVERED |
| RESEARCH | Side-effect retry | Surface creation failures; reuse completed artist/venue/tour/post IDs after show failure | 03-01-02 | COVERED |
| RESEARCH | Native date sanitization | Editable raw fallback, explicit precedence, no-JS correction; incomplete browser text observed honestly | 03-01-02–03, 03-04-02 | COVERED |
| RESEARCH | Calendar adapter | Exact checkdate/native domain; legacy absence-only adapter; no new date-range/order rule | 03-01-01–03 | COVERED |
| RESEARCH | Time/expiration | No-time vs midnight, uncommon minutes, 12/24 values, unchanged cutoff | 03-01-01–03 | COVERED |
| RESEARCH | Identity/no-op writes | Stable edit/copy ID, strict false/read-back, successful add fresh form | 03-01-01–02 | COVERED |
| RESEARCH | Canonical navigation | All domains/URLs, size vs SQL limit, zero/single/clamped pages, request-only sort | 03-03-01–02 | COVERED |
| RESEARCH | Explicit confirmation | Server preview/Confirm/Cancel, real single/bulk IDs and guard/bypass checks | 03-03-01–03, 03-04-02 | COVERED |
| RESEARCH | Truthful outcomes | Per-ID changed/already/missing/stale/failed results, reasons and changed-only undo | 03-03-03 | COVERED |
| RESEARCH | Actual option boundary | Registered pure idempotent stored merge, programmatic writes, true options.php HTTP submit | 03-02-01, 03-04-02 | COVERED |
| RESEARCH | Untouched settings | Hidden/unknown scalar/nested/falsey baseline, explicit unchecked domains, unknown select/radio | 03-02-01–02 | COVERED |
| RESEARCH | Semantics/progressive UI | Labels/linked summary/help/table headings, no forced blur, focus/no-JS | 03-01-03, 03-02-02, 03-03, 03-04-02 | COVERED |
| RESEARCH | Real-WP harness | New cases/dispatch/aggregation/nonempty assertions, preserve old 11-case registry | 03-01-01, 03-04-03 | COVERED |
| RESEARCH | Browser transport | Existing fixture has no HTTP ports; create loopback isolated transport/procedure/teardown | 03-04-01–02 | COVERED |
| RESEARCH | Runtime/evidence | Resolve supported patches/branches, measured timing, no host PHP/new package requirement | 03-04-03 | COVERED |
| RESEARCH | Security/tier ownership | Correct WordPress/browser/DB responsibility, capability/nonce/readiness, encoded outputs | All plans/threat models | COVERED |
| CONTEXT | D-01 | Start/end picker and optional configured clock | 03-01-01/03, 03-04-02 | COVERED |
| CONTEXT | D-02 | Exact optional labels/visible minutes/sentinel | 03-01-01/03 | COVERED |
| CONTEXT | D-03 | Multi-day reveal/help/unchanged expiration | 03-01-03 | COVERED |
| CONTEXT | D-04 | Linked field text and all received recovery values | 03-01-02/03 | COVERED |
| CONTEXT | D-05 | Scope/size only persisted; all active navigation choices | 03-03-02 | COVERED |
| CONTEXT | D-06 | Narrow reset retaining scope/sort/size at page 1 | 03-03-02 | COVERED |
| CONTEXT | D-07 | Explicit selected-count Confirm/Cancel | 03-03-01/03 | COVERED |
| CONTEXT | D-08 | Per-ID reasons/counts/retained state | 03-03-03 | COVERED |
| CONTEXT | D-09 | One page/jump links/one save | 03-02-01/02 | COVERED |
| CONTEXT | D-10 | Exact six section groups | 03-02-02 | COVERED |
| CONTEXT | D-11 | Short explanations/examples/guidance | 03-02-02 | COVERED |
| CONTEXT | D-12 | Advanced immediately visible/jump link | 03-02-02 | COVERED |
| CONTEXT | D-13 | Keys/values/meanings, hidden/unknown/falsey save preservation | 03-02-01/02 | COVERED |
| CONTEXT | D-14 | Success stays add, saved Edit/List, next form and identity | 03-01-01/02 | COVERED |
| CONTEXT | D-15 | Available next step and correction/retry on system failure | 03-01-02 | COVERED |
| CONTEXT | D-16 | Individual identified Confirm/Cancel | 03-03-01 | COVERED |
| CONTEXT | D-17 | Exact empty-match text and narrow reset | 03-03-02 | COVERED |
| CONTEXT | D-18 | Labels/headings/keyboard/text, capability/nonce/readiness | All four plans | COVERED |

## Spec-less Edge and Prohibition Accounting

Input `/tmp/gigpress-03-edge-coverage.json` contains 10 surfaced items total, including one unclassified item. Nine classified edges are resolved to explicit acceptance truths in frontmatter; the unclassified item remains a flagged assumption. No edge is dismissed. No backstop is needed for the nine evidenced shapes; the one unclassified row must not be automatically backstopped/resolved.

| Requirement/category | Explicit acceptance truth or flag | Plan |
|----------------------|----------------------------------|------|
| ADMIN-01/adjacency | Equal start/end stays valid and retains existing multi-day/expiration meaning; no new date-order rule | 03-01 |
| ADMIN-01/empty | Empty required dates rejected/readable/editable; optional time stays sentinel; existing one-row/copy/update covered | 03-01 |
| ADMIN-01/encoding | ASCII canonical calendar parsing; received Unicode names/notes/punctuation preserved and escaped in recovery | 03-01 |
| ADMIN-01/ordering | 12/24 labels retain chronological hour values; uncommon existing minutes survive unchanged | 03-01 |
| ADMIN-02/adjacency | Duplicate selected IDs counted once; distinct equal-valued IDs remain separate and unselected rows unchanged | 03-03 |
| ADMIN-02/empty | Empty/malformed selection zero writes; zero/single/multi-page metadata safe; no-match narrow reset | 03-03 |
| ADMIN-02/ordering | Stable show_id tie breaker after date/expire/time prevents equal-key pagination duplicates/omissions | 03-03 |
| ADMIN-03/unclassified | Unresolved flagged assumption in 03-02; supplied preservation tests do not resolve unidentified intent | 03-02 |
| UX-01/empty | Empty submit/no-match/no-selection receive text feedback and existing correction/reset/reselect action | 03-01/03 |
| UX-01/encoding | Hostile/Unicode values and notices remain destination-encoded text with real label/error/help targets | 03-01–03 |

Prohibition recall ran per requirement before precision filtering. Raw candidates included data loss, required time, changed cutoff, merged events, lost correction, silent reset, hidden Advanced, filter-expanded selection, misleading Cancel, unsupported save success, global preferences, lost keyboard feedback, injection, nonce/capability bypass and leaked fixture credentials. Correctness/hygiene candidates were dropped into the edge/case work; injection/authority/privacy canon was referred to `$gsd-secure-phase` and the STRIDE controls rather than minted as product prohibitions. Three genuine product-intent prohibitions remain: existing event cutoff/optional time must not be redefined (03-01), confirmation must not present filtered rows as selected or imply Cancel continues trash (03-03), and grouping must not hide Advanced or imply unchanged-save configuration reset (03-02).

These three items were passed through installed `probe-core.cjs` `projectProhibitions`; its canonical projection is `{statement, status: unresolved}`. The exact descriptor-less projections appear as `must_haves.prohibitions` in the respective plans. No `check_*` descriptor or test/judgment outcome is fabricated. They route flagged-unverified downstream and remain visible review items; the matrix cannot silently certify them.

## Discovery and Active Contributions

## Validation Audit 2026-10-05

The active validate-phase hook cross-referenced all four completed PLAN/SUMMARY pairs and eleven tasks against the actual eight-case modules, real HTTP dispatcher and source-bound matrix. ADMIN-01, ADMIN-02 and ADMIN-03 have positive integration/HTTP checks; UX-01 has positive semantics/data checks and the actual browser subset in 03-BROWSER.md, with complete interactive acceptance PARTIAL. No missing automated implementation test is inferred from a browser-only observation. No new tests or implementation changes were required by this audit.

| Metric | Count |
|--------|-------|
| Tasks mapped | 11 |
| Required administration cases | 8 |
| Passing supported scenario cells | 18 |
| Administration assertions | 5376 |
| Actual evidence control/corruption checks | 21 |
| Pending grouped browser procedures | 3 |

Manual-only acceptance is the exact three groups in 03-BROWSER.md: genuinely disabled JavaScript entry/list; full keyboard traversal/automatic notice focus; complete new-choice/radio/no-time/midnight/mixed browser outcomes. The ADMIN-03 unclassified edge and descriptor-less prohibitions remain flagged-unverified for review. Per the explicit 03-04 evidence contract, status remains draft and nyquist_compliant=false while these required items remain unresolved; wave_0_complete=true reflects the delivered positive registry/case infrastructure. This is a partial audit, not a compliance certification. The generic workflow's validated status is not used to erase the stricter plan's missing-evidence boundary.

Existing source-pattern/official-documentation discovery is current in 03-RESEARCH/03-PATTERNS; no new dependency choice is required. Historical digest selected Phase 01 harness and Phase 02 preservation/lifecycle patterns; graph status was disabled. Project skill directories/AGENTS.md were absent on disk; user-supplied RTK and workflow rules applied. Estimate calibration measured factor 1, sample_count 0, confidence low for every plan. No tests or production changes occurred during planning.

The real assumption-delta query returned detected=true for optional; 03-01 records no-change because show identity remains show_id and time was already optional with 00:00:01. No ORM/schema paths or schema push are scoped. The real installed api-coverage.cjs detector examined the final four plans plus the registered roadmap phase section and returned detected=true from the existing WordPress Settings API surface. Scope inspection confirms a false external-integration signal: local WordPress option registration/save and synthetic loopback HTTP validation introduce no external API/service. Parent owns the required reasoned COVERAGE.md non-integration declaration; no capability matrix is fabricated.

All four plan frontmatter validations and plan-structure queries passed with zero errors/warnings; task counts are 3/2/3/3. These are planning artifact checks, not implementation execution evidence.
