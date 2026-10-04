---
phase: 03-administration-workflows
plan: "02"
subsystem: ui
tags: [wordpress, php, settings-api, preservation, accessibility, tdd]
requires:
  - phase: 03-01
    provides: Eight-case administration registry and real show-entry/sticky update helpers
  - phase: 02-data-and-upgrade-preservation
    provides: Preserved settings baselines and upgrade coordinator guarantees
provides:
  - Registered Settings API sanitizer with stored-baseline editable overlays
  - Six visible settings sections, jump navigation, labels and contextual help
  - Real WordPress settings-save and settings-sections cases with 224 named assertions
affects: [03-03, 03-04, verification]
actuals:
  tokens: 12316
  tasks: 2
  commits: 4
commits: 4
plan_head_before: 57884312c808a36c2c9f2aeb02c7ceff9c8fce19
plan_head_after: 65ddca0060f3a30c57c0cf37f00a4f9c06c1b023
tech-stack:
  added: []
  patterns: [stored baseline with validated editable overlay, explicit unchecked controls, uncommon current-value choices, scoped settings sections]
key-files:
  created: [tests/compat/administration-settings.php]
  modified: [gigpress.php, admin/settings.php, css/gigpress-admin.css]
key-decisions:
  - "Protect stored metadata and unknown keys during Settings API form submissions; allow trusted programmatic partial updates to merge into storage."
  - "Keep uncommon current choices represented in the form and accept them only while equal to storage; supported deliberate replacements remain available."
  - "Use an explicit country-name select with the existing long/empty domain, and preserve falsey checkbox types on unchanged saves."
requirements-completed: [ADMIN-03, UX-01]
coverage:
  - id: settings-preservation
    description: Registered option update and reload preserve protected, unknown, nested and falsey settings while editable values change
    requirement: ADMIN-03
    verification:
      - kind: integration
        ref: tests/compat/administration-settings.php#settings-save
        status: pass
    human_judgment: false
  - id: settings-discovery-and-markup
    description: Six visible groups, associated labels/help, escaped output, jump targets and one save action
    requirement: UX-01
    verification:
      - kind: integration
        ref: tests/compat/administration-settings.php#settings-sections
        status: pass
    human_judgment: false
  - id: settings-browser-request-and-keyboard
    description: Real options.php HTTP save/nonce/capability requests and actual keyboard focus/jump operation
    requirement: UX-01
    verification: []
    human_judgment: true
    rationale: Plan 03-04 owns disposable HTTP/browser evidence; callback and markup checks cannot establish these interactions.
duration: 12min
completed: 2026-10-04
status: complete
---

# Phase 03 Plan 02: Settings Organization and Preservation Summary

**All 34 existing editable settings now appear in six visible sections and save through a registered WordPress callback that preserves stored metadata, unknown values and disabled settings.**

## Performance

- Started: 2026-10-04T20:56:17Z.
- Duration: approximately 12 minutes including RED/GREEN runs and inherited regressions.
- Tasks: 2; changed source/harness files: 4.
- Actual tokens: ceil(49,261 realized diff characters / 4) = 12,316, measured over the four source/harness files between the recorded plan heads.
- Measured commits: 4 task commits, before summary/state metadata commits.

## Accomplishments

- Registered `gigpress_sanitize_settings` on the existing `gigpress` group and `gigpress_settings` option, using the normal WordPress sanitize/update/read-back boundary.
- Replaced browser-owned hidden metadata with storage-owned preservation. The callback never calls `update_option` recursively and does not create a new persistence representation.
- Grouped all editable controls with exact headings, stable jump targets, short help, date/time examples, explicit labels, radio fieldsets/legends and one Save changes action. Advanced is immediately visible.
- Kept existing `long`/empty country choices, `y`/`n` schema choices, permission capabilities, related positions and category IDs. Uncommon current select/radio choices remain represented and unchanged saves preserve exact values/types.
- Added positive real WordPress save and markup assertions through the pre-existing registry; both cases pass with zero warnings, fatals and plugin errors.

## Task Commits

| Task | Stage | Commit | Result |
|------|-------|--------|--------|
| 03-02-01 | RED | `ac106e7` | Registered-save preservation assertions fail intentionally |
| 03-02-01 | GREEN | `04a10ef` | Focused sanitizer and storage-owned metadata preservation |
| 03-02-02 | RED | `08d251f` | Six-section/label/help/current-value assertions fail intentionally |
| 03-02-02 | GREEN | `65ddca0` | Complete settings layout, explicit disabled values, escaped choices and full save coverage |

No separate refactor commit was needed. Production commits passed the supplied-root pin, expected-branch and protected-branch assertions; hooks were retained. Existing show-entry CSS and unrelated config/runtime files were preserved.

## Section and Key Mapping

| Section / heading target | Existing editable keys |
|--------------------------|------------------------|
| Display & formatting / `gp-settings-display-formatting` | `date_format`, `date_format_long`, `time_format`, `alternate_clock`, `display_country`, `country_view` |
| Show labels & links / `gp-settings-show-labels-links` | `shows_page`, `noupcoming`, `nopast`, `artist_label`, `tour_label`, `external_link_label`, `buy_tickets_label`, `age_restrictions`, `artist_link`, `target_blank` |
| Related posts / `gp-settings-related-posts` | `related_position`, `related_heading`, `autocreate_post`, `related_category`, `category_exclude`, `relatedlink_date`, `relatedlink_city`, `relatedlink_notes`, `related` |
| Feeds / `gp-settings-feeds` | `rss_head`, `display_subscriptions`, `rss_title`, `rss_limit` |
| Permissions / `gp-settings-permissions` | `user_level` |
| Advanced / `gp-settings-advanced` | `output_schema_json`, `load_jquery`, `disable_css`, `disable_js` |

All groups use the same form and WordPress form-table layout. Country display uses a select so both established values and a current uncommon value can remain explicit. Fourteen numeric flag controls submit a hidden zero before the checkbox; changing enabled flags to unchecked stores integer zero. An already-disabled false/empty/zero setting retains its exact existing scalar type on an unchanged submission. Non-scalar stored editable values are disabled and kept rather than implicitly replaced.

## Sanitizer and Exact Read-Back

The form boundary is a POST with `option_page=gigpress` and `action=update`. WordPress `options.php` retains capability and nonce authority. In that context the callback starts with stored settings, checks presence with `array_key_exists`, validates only editable keys and retains prior values on malformed inputs with Settings API text errors. Hidden and unknown submitted keys cannot replace storage. Missing controls do not imply unchecked state.

Outside that form context, trusted programmatic arrays merge into storage with `array_replace`, preserving untouched keys and allowing existing coordinator/default/welcome writes. This is exercised through real WordPress `update_option` and a real `gigpress_add_show` call; no fake option API is used.

Named read-back checks establish:

- `artist_label` changes to `Performers`; the entire resulting array equals the independent baseline plus that one change. Forged `db_version`, `welcome`, `default_date` and unknown nested input leave stored authority intact.
- Unknown scalar/nested values and false, zero, empty and null keys preserve exact PHP types. Protected sticky metadata and unrendered keys remain in the exact array comparison.
- Repeated partial and complete form submissions are idempotent. Malformed top-level arrays, nested editable values, negative feed limits and unsafe URL schemes retain storage and produce text errors.
- Current uncommon country, position, schema, capability and category choices survive a full unchanged save. Arbitrary new unsupported choices are rejected, while explicit supported replacements work.
- Every flag disables correctly; all supported radio/capability/country choices save; feed limits empty/0/1/250 retain the existing fallback and numeric meanings.
- The real show handler saves sticky date `2032-05-06`, no-time `00:00:01`, selected artist/venue/tour IDs and age choice while retaining unknown/protected settings.

Markup checks parse actual renderer output and independently assert the six section/key mappings, control/help/label targets, radio legends, one form/save, visible Advanced, safe attributes, safely encoded date examples/category titles and unique IDs within the owned settings surface. They do not certify actual browser keyboard behavior.

## Verification

All container cells use WordPress **7.1.2**, PHP **8.3.35**, official image `wordpress:php8.3-apache`, image ID `sha256:4abf7a450ee477dde967584f8174d7e03221d224c4971a0c38d84e7254426e64`.

| Command | Result |
|---------|--------|
| `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario administration-workflows --case settings-save` | PASS; final 54 named assertions, including real sticky-handler read-back |
| `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario administration-workflows --case settings-sections` | PASS; final 170 named assertions |
| Same settings-save command after task 1 commit | Tracer feedback gate PASS before expansion |
| `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case settings-repeat` | PASS; inherited repeated settings/bootstrap preservation across all recognized source fixtures |
| `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case all` | PASS; exact eleven-case registry, zero warnings/fatals/plugin errors |
| `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario full-workflows` | PASS; admin CRUD, public shortcode, RSS, iCalendar, CSV and duplicate preservation |
| `rtk proxy bash tests/compat/run.sh lint --php-branches 8.3 --files gigpress.php,admin/settings.php,tests/compat/administration-settings.php` | PASS; all three changed PHP files syntax clean |
| `rtk proxy git diff --check` | PASS |
| `rtk proxy /usr/bin/time -p bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario administration-workflows --case all` | Expected exit 2: entry-create/recovery/controls and settings-save/sections PASS; only absent list-single/navigation/bulk have empty checks and FAIL pending 03-03 |

The committed-source final aggregate used revision `65ddca0060f3a30c57c0cf37f00a4f9c06c1b023`. It reran both settings cases with **224** assertions and all earlier entry cases with **206** assertions; all five delivered cases had zero warnings, fatals and plugin errors. Supported matrix expansion belongs to 03-04.

### Measured timings

- Cold administration aggregate, including startup/bootstrap, five delivered callbacks, three absent cases and teardown: **17.78 seconds**. Its expected FAIL status records incomplete later-plan registry coverage.
- Warm settings-save callback in the committed aggregate: **0.0923 seconds**; warm settings-sections: **0.0137 seconds**.
- These local cached-runtime observations exclude HTTP/browser interaction and do not establish uncached startup limits.

## TDD Gate Compliance

Both tasks passed `gsd_run check tdd-red-evidence` with `RED_EVIDENCE_OK`, committed failing test coverage before implementation, then passed GREEN. The task behavior predicate returned true and phase-level MVP/TDD applicability was honored despite the project-wide default being false. Evidence was persisted at `/tmp/gigpress-03-02-01-red.json` and `/tmp/gigpress-03-02-02-red.json`; the table above and committed tests retain the durable history.

Task 1 RED ran 13 real assertions with zero runtime errors and failed registration/protected-array/idempotence/malformed-input preservation. Task 2 RED ran 169 real section assertions with zero runtime errors and failed section/label/help/current-choice/escaping behavior. The tracer was verified again after its GREEN commit before expansion.

## Decisions Made

Followed D-09–13/D-18: one immediately visible page, six exact section headings, one save, labeled controls and short help, escaped values/examples, existing option names/domains and stored settings authority. Validation uses the existing [WordPress registration callback](https://developer.wordpress.org/reference/functions/register_setting/) and [option update](https://developer.wordpress.org/reference/functions/update_option/) contracts; Context7/ctx7 was unavailable, so primary documentation and bundled core source supplied API grounding.

## Deviations from Plan

None — source ownership, grouping, save boundary and preservation contract followed the plan. Routine GREEN corrections retained falsey editable types and the established empty-feed-limit behavior rather than changing those meanings.

## Issues Encountered

- Docker access required the authorized sandbox escalation for disposable OrbStack cells; there was no authentication gate.
- The expanded integration fixture needed the real handlers module loaded explicitly before using the prior entry helper. This setup correction is included in `65ddca0`.
- WordPress's bundled `options-head.php` already displays Settings API notices. Removed a redundant newly added notice call and limited the duplicate-ID assertion to the owned settings surface; inherited fixture `invalid_home`/`invalid_siteurl` notices outside it are unrelated. All owned control/help/jump IDs are unique.
- No packages were installed, no production stubs remain and no new trust surface outside the plan threat model was introduced.

## Remaining Browser Items and Flagged Assumptions

Plan 03-04 must verify actual `options.php` HTTP save, invalid nonce and unauthorized denial, plus keyboard jump/focus behavior. Callback saves are explicitly not HTTP submissions. The pending verification is recorded as WINDOWS entry **8**, `unrun-verify`; entry 7 continues to track prior show-entry browser evidence.

The spec-less `ADMIN-03/unclassified` shape edge remains unresolved for human scope review. The descriptor-less product prohibition remains flagged-unverified in the projectProhibitions projection; this summary does not fabricate a check descriptor or resolve that planning flag. The implemented six-section/unchanged-save behaviors have the concrete assertions listed above.

`requirements.ready-ids` returned no ready IDs and blocked `ADMIN-03`/`UX-01` because sibling plans still own shared verification. Planning requirements remain unchecked until those sibling plans finish. The frontmatter IDs record this plan's delivered coverage, not full-phase acceptance.

## Next Phase Readiness

03-03 can supply the three remaining list cases. 03-04 can use the existing settings case IDs and stable heading/control IDs for its browser fixture, guard requests and supported matrix. No list/entry/probe/runner source was modified by 03-02.

## Self-Check: PASSED

All four changed source/harness files exist; all four task commit objects exist. The persisted plan ledger measures four commits between the recorded heads, and no task source changes remain uncommitted. The summary is on disk at the canonical plan path.
