---
phase: 03-administration-workflows
plan: "03"
subsystem: ui
tags: [wordpress, php, show-list, confirmation, preservation, accessibility, tdd]
requires:
  - phase: 03-01
    provides: Eight-case administration registry and real guarded show-entry helpers
  - phase: 03-02
    provides: Preserved settings registration and grouped settings cases
  - phase: 02-data-and-upgrade-preservation
    provides: Database readiness, identity and relationship preservation contracts
provides:
  - Validated visible list state with per-user scope and page-size preferences only
  - Owned explicit selected-show preview, Confirm and Cancel protocol
  - Verified per-ID trash outcomes, changed-only Undo and clamped return navigation
  - Three real WordPress list cases with 460 named assertions
affects: [03-04, verification, admin-show-management]
actuals:
  tokens: 18443
  tasks: 3
  commits: 6
commits: 6
plan_head_before: 6ea26b7146c8b4965bb15f9671e941f87330be24
plan_head_after: 8a8339990c9cac2c49b55eab146801391b894395
tech-stack:
  added: []
  patterns: [canonical list state, owned expiring confirmation intent, guarded per-row status writes, verified changed-only undo]
key-files:
  created: [tests/compat/administration-list.php, .planning/phases/03-administration-workflows/03-03-EXECUTION.json]
  modified: [admin/shows.php, admin/handlers.php, tests/compat/upgrade-preservation-crud.php]
key-decisions:
  - "Persist only scope and integer page size; entity filters, sort and page position remain request state."
  - "Bind trash to the current owner, exact deduplicated ordered IDs and stored return state, consuming the expiring intent before writes."
  - "Count only strict verified status transitions and scope Undo to changed IDs; recount and clamp return links after writes."
requirements-completed: [ADMIN-02, UX-01]
coverage:
  - id: explicit-owned-trash-confirmation
    description: Real preview/Cancel/Confirm, identity/count, owner/expiry/nonce/capability/readiness/selection/replay protections
    requirement: ADMIN-02
    verification:
      - kind: integration
        ref: tests/compat/administration-list.php#list-single
        status: pass
    human_judgment: false
  - id: retained-list-navigation
    description: Visible retained choices, narrow reset, preference lifetime, safe clamping and stable equal-key identity pages
    requirement: ADMIN-02
    verification:
      - kind: integration
        ref: tests/compat/administration-list.php#list-navigation
        status: pass
    human_judgment: false
  - id: truthful-selected-bulk-results
    description: Exact selected-only snapshots, deduplication, failed/missing/stale/already outcomes, changed-only Undo and last-page return
    requirement: ADMIN-02
    verification:
      - kind: integration
        ref: tests/compat/administration-list.php#list-bulk
        status: pass
    human_judgment: false
  - id: list-browser-keyboard-and-no-js
    description: Actual HTTP/no-JS single/bulk selection and confirmation, keyboard use and feedback announcement
    requirement: UX-01
    verification: []
    human_judgment: true
    rationale: Plan 03-04 owns the disposable HTTP fixture and actual browser observations; callback/markup checks cannot certify these interactions.
duration: 16min
completed: 2026-10-04
status: complete
---

# Phase 03 Plan 03: Retained Show List and Confirmed Trash Summary

**Show lists retain visible choices through navigation and review exact selected identities before guarded trash, with truthful per-show results and Undo for verified changes only.**

## Performance

- Started: 2026-10-04T21:10:35.844440Z; duration approximately 16 minutes.
- Tasks: 3; changed source/harness files: 4.
- Actual tokens: ceil(73,769 realized diff characters / 4) = 18,443 over the four source/harness files between the recorded heads.
- Measured task commits: 6 before summary/state metadata commits. No separate refactor was needed.

## Accomplishments

- One canonical state owns scope, artist/tour/venue, sort, integer page size and page position. Scope and size retain existing per-user storage; sort never reads or writes legacy `gigpress_sort`, and entity filters remain request-only.
- Filter selections, scope links, pagination, Edit/Copy, preview/Cancel/results, empty-trash return and Undo links carry the normalized state. Reset clears artist/tour/venue and uses page 1 while retaining scope/sort/size. Empty matches explain “No shows match these filters” and offer the same Reset filters action.
- Prepared relationship predicates and allowlisted sort govern queries. Count comes before page calculation; zero/one/multiple pages have local safe metadata and out-of-range pages clamp. SQL offset/limit remains separate from visible page size. `show_id` follows date/expiration/time in the same sort direction, preserving distinct equal-valued rows across pages.
- A clicked row supplies only its explicit `trash_single_id`, independently of checked bulk boxes. Bulk uses only named row `show_id[]` values; select-all headers have no selection IDs. Unique checkbox IDs, associated labels, semantic headings and text notices are rendered without nested forms.
- Changed confirmation/result text and URLs are escaped; existing prepared show HTML is constrained with `wp_kses_post` while retaining intended details and links.
- The inherited upgraded lifecycle now obtains the real preview's issued token and rendered confirmation nonce as POST, retaining legacy date components, copy-source immutability, relationship/identity snapshots, selected-only trash/restore and blocked-readiness coverage.

## Confirmation and Result Contract

The primary flow is POST with `gpaction=delete`, `trash_stage=preview|confirm|cancel`. GET never confirms or changes rows. Preview requires configured capability, valid `gigpress-action` nonce, readiness and a complete valid explicit selection. Integers/decimal digit strings must be positive, canonical and bounded; malformed/nested/keyed/negative/zero/float/boolean values reject the whole selection. Deduplication retains first-seen ordering.

A random 40-character token identifies a 15-minute WordPress transient containing owner, expiry, exact IDs, normalized return state, review rows and safe identity text. Confirm and Cancel have separate nonce actions. Confirm rechecks capability/nonce/readiness plus owner/expiry/exact selection, then claims and consumes the intent before mutation. A short-lived operation claim uses WordPress's unique option insertion and is removed in `finally`; read-back/consumption checks also prevent delayed replay. Cancel consumes only a matching owned intent and writes no show rows. Submitted return choices cannot replace the stored authority.

Every selected ID receives one result: `changed`, `already_trashed`, `missing`, `stale` or `failed`. A row edited since preview or carrying an ineligible status is skipped with an explanation. Writes include the previous row as their prepared predicate; strict one-row success and exact read-back establish a status-only transition. False writes are failures; zero writes are stale, never changes. All unsuccessful selections have reasons and an available Edit or List/reselect link. Missing/deleted entries use List guidance.

Only `changed_ids` drive counts and Undo. The list is recounted after writes and all result/Undo URLs clamp to a real page. Show Undo retains its signed existing restore contract, configured capability/readiness protection, status-based identity semantics and selected-only operation; already restored/missing rows are skipped with truthful counts. Pagination temporarily receives only the clean list URL so Undo/action/nonce query parameters cannot leak into page links.

The transient, generated password and affected-row contracts follow the official [WordPress transient API](https://developer.wordpress.org/reference/functions/set_transient/), [random token API](https://developer.wordpress.org/reference/functions/wp_generate_password/) and [database update API](https://developer.wordpress.org/reference/classes/wpdb/update/). Context7/ctx7 was unavailable; primary documentation supplied API grounding. No packages or schema changes were introduced.

## Task Commits and TDD Gate Compliance

| Task | RED | GREEN | Intentional RED / final evidence |
|------|-----|-------|--------------------------------|
| 03-03-01 | `7fdaedf` | `b21cb41` | 23 failing real assertions with zero errors; final list-single 30 PASS |
| 03-03-02 | `d34d539` | `137568e` | 254 assertions exposed missing retained state/labels/page metadata; final list-navigation 266 PASS |
| 03-03-03 | `84ef630` | `8a83399` | 90 assertions exposed missing per-ID actions, zero-write classification/repeated Undo text/result clamping; final list-bulk 164 PASS |

All three RED records passed `gsd_run check tdd-red-evidence` as `RED_EVIDENCE_OK` before implementation. They were persisted at `/tmp/gigpress-03-03-01-red.json`, `/tmp/gigpress-03-03-02-red.json` and `/tmp/gigpress-03-03-03-red.json`; committed test/implementation pairs and this manifest preserve the durable history. The behavior predicate returned true. The phase-level MVP/TDD applicability was honored despite the global default being false. The committed single-show tracer passed again before expansion. Root pin, exact expected branch and protected-branch assertions passed for every commit, with hooks retained.

## Verification

All container cells use WordPress **7.1.2**, PHP **8.3.35**, official image `wordpress:php8.3-apache`, image ID `sha256:4abf7a450ee477dde967584f8174d7e03221d224c4971a0c38d84e7254426e64`.

| Exact command | Result |
|---------------|--------|
| `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario administration-workflows --case list-single` | PASS; 30 named assertions and committed tracer feedback gate |
| `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario administration-workflows --case list-navigation` | PASS; 266 named assertions |
| `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario administration-workflows --case list-bulk` | PASS; expanded case rerun in final aggregate with 164 named assertions |
| `rtk proxy /usr/bin/time -p bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario administration-workflows --case all` | PASS on committed source `8a8339990c9cac2c49b55eab146801391b894395`; exact eight cases, **890 assertions**, zero warnings/fatals/plugin errors |
| `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case show-lifecycle` | PASS including final real rendered confirmation nonce and full previous-row write predicates |
| `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case all` | PASS; unchanged exact eleven-case registry, zero warnings/fatals/plugin errors |
| `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario full-workflows` | PASS; existing admin create/edit/read, public shortcode, RSS, iCalendar, CSV and duplicate preservation |
| `rtk proxy bash tests/compat/run.sh lint --php-branches 8.3 --files admin/shows.php,admin/handlers.php,tests/compat/administration-list.php,tests/compat/upgrade-preservation-crud.php` | PASS; all four owned PHP files |
| `rtk proxy git diff --check` | PASS |

The final aggregate retains prior entry **206** and settings **224** assertions plus list **460** assertions. List coverage includes exact status-only full snapshots; invalid/no-selection inputs; stale edits/removal/unsupported status; controlled false and zero writes; foreign/expired/replayed/canceled/mismatched requests; GET/direct/nonce/capability/readiness guards; Undo guards; same-valued unselected rows; narrow reset; two-user preferences; safe zero/single/out-of-range pages; stable three-page ascending/descending identities; and action-free pagination after Undo.

Cold aggregate including startup/bootstrap/eight child cases/teardown: **17.82 seconds** on the local cached runtime. Warm committed callback timings: list-single **0.1348 s**, list-navigation **0.3218 s**, list-bulk **0.1645 s**. These exclude HTTP/browser interaction and do not establish uncached startup limits. Durable compact results and exact source metadata are in `03-03-EXECUTION.json`; runtime matrix expansion belongs to 03-04.

## Deviations from Plan

**1. [Rule 3 - Blocking] Bring one-page safety forward into the tracer**
- Found during task 03-03-01 GREEN: the legacy renderer indexed null pagination metadata when a one-page request carried `gp-page`, causing warnings and invalid SQL before the row control could be verified.
- Added a local offset-zero/page-size fallback in task 1, then replaced it with the complete normalized/clamped metadata in planned task 2.
- Files: `admin/shows.php`; commits `b21cb41` and `137568e`. Tracer and final zero/single/multiple-page cases PASS. WINDOWS entry **10** is fixed.

The test query interceptor was adjusted to match quoted IDs when the production predicate expanded to the full previous row. This fixture correction preserves controlled false/zero writes; no assertion was weakened. Clean pagination URLs and truthful repeated Undo were part of the planned guarded return/results work.

## Issues Encountered and Authentication Gates

No outstanding implementation issues or authentication gates. Authorized scoped Git and OrbStack escalation was used; no branches/worktrees were switched. Entry/settings work, bootstrap/assets/probe/runner and unrelated config/cache/runtime files were preserved. No automatic approval rejection occurred.

## Known Stubs and Threat Surface

No implementation stubs block the plan. “Selected entry #(not available)” is a real normalized unavailable-entity choice that keeps the current filter visible, backed by a missing entity lookup; it is not mock content. All introduced request/state/confirmation/row-write/Undo surfaces are covered by T-03-10 through T-03-15. No endpoint, schema or authentication surface outside the plan threat model was introduced.

## Pending Browser Evidence and Next Phase Readiness

03-04 must record actual HTTP/no-JS and keyboard single/bulk selection, Confirm/Cancel, retained list choices and per-ID feedback; WINDOWS entry **9** tracks this pending verification. Default WordPress select-all enhancement and real focus/announcement behavior remain browser observations, even though IDs/labels/headings/forms/text are covered here. No browser evidence is claimed by PHP probes.

The descriptor-less product prohibition remains **flagged-unverified** for downstream review. Summary frontmatter records this plan's ADMIN-02/UX-01 contributions; the shared `requirements.ready-ids` gate returns **0/2 ready** because 03-04 also declares both IDs. Global requirements remain incomplete until sibling coverage is complete.

## Self-Check: PASSED

All four declared source/harness files, this summary and the execution manifest exist. All six task commit objects exist. The persisted plan ledger measures six task commits between the recorded heads, and no owned source changes remain uncommitted.
