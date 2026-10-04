---
phase: 02-data-and-upgrade-preservation
verified: 2026-10-04T19:23:28Z
status: passed
score: 10/11 must-haves verified (1 deferred)
covered_files:
  - .planning/phases/02-data-and-upgrade-preservation/02-01-PLAN.md
  - .planning/phases/02-data-and-upgrade-preservation/02-01-SUMMARY.md
  - .planning/phases/02-data-and-upgrade-preservation/02-02-PLAN.md
  - .planning/phases/02-data-and-upgrade-preservation/02-02-SUMMARY.md
  - .planning/phases/02-data-and-upgrade-preservation/02-03-PLAN.md
  - .planning/phases/02-data-and-upgrade-preservation/02-03-SUMMARY.md
  - .planning/phases/02-data-and-upgrade-preservation/02-04-PLAN.md
  - .planning/phases/02-data-and-upgrade-preservation/02-04-SUMMARY.md
  - admin/artists.php
  - admin/db.php
  - admin/handlers.php
  - admin/venues.php
  - gigpress.php
  - tests/compat/compose.yaml
  - tests/compat/fixtures/upgrade-preservation/1.0.php
  - tests/compat/fixtures/upgrade-preservation/1.1.php
  - tests/compat/fixtures/upgrade-preservation/1.2.php
  - tests/compat/fixtures/upgrade-preservation/1.3.php
  - tests/compat/fixtures/upgrade-preservation/1.4.php
  - tests/compat/fixtures/upgrade-preservation/1.5.php
  - tests/compat/fixtures/upgrade-preservation/1.6.php
  - tests/compat/probe.php
  - tests/compat/run.sh
  - tests/compat/upgrade-preservation-crud.php
  - tests/compat/upgrade-preservation-migrations.php
covered_digest: "v2:sha256:575eb7424043747c1586e0ef6d4b12af380635bf12bbf9a33d80a61f3ca56e72"
behavior_unverified: 0
overrides_applied: 0
decision_coverage:
  honored: 12
  total: 12
  not_honored: []
deferred:
  - truth: "Public shortcode, RSS/iCalendar, and CSV import/export workflows were not run against the same upgraded legacy fixture."
    addressed_in: "Phases 4 and 5"
    evidence: "Phase 4 success criterion 1 covers existing shortcode, widget, related-post, RSS, and iCalendar behavior after update; Phase 5 success criterion 1 covers preservation of established CSV columns, filters, related-post option, and row handling."
---

# Phase 2: Data and Upgrade Preservation — Verification Report

**Phase Goal:** As a site owner, I want to update GigPress while preserving existing records, relationships, settings, and show-management workflows, so that I can upgrade without data loss or disrupting routine show management.
**Verified:** 2026-10-04T19:23:28Z
**Status:** passed
**Re-verification:** No — initial verification

## User Flow Coverage

User story: “As a site owner, I want to update GigPress while preserving existing records, relationships, settings, and show-management workflows, so that I can upgrade without data loss or disrupting routine show management.”

| Step | Expected | Evidence | Status |
|------|----------|----------|--------|
| Update an existing GigPress installation | Existing rows, entity IDs, settings, relationships, and linked posts survive migration and repeat loads. | The WordPress probe seeds reconstructed 1.0–1.6 fixtures, activates the real plugin, and compares database/option snapshots with explicit fixture manifests; six runtime cells passed. | ✓ VERIFIED |
| Continue show management | Create, edit, copy, selected trash, and restore work after migration while preserving expected IDs, content, and entity relationships. | `show-lifecycle` invokes the real handlers on the upgraded 1.4 fixture; `admin/new.php` and `admin/shows.php` wire the existing forms and actions to those handlers. | ✓ VERIFIED |
| Manage artist, venue, and tour relationships | Active or trashed dependencies prevent destructive entity deletion; tour undo restores only shows owned by that operation and preserves reassignment. | `entity-guards` renders the real list views and calls handlers; `tour-undo` checks per-tour ownership and reassignment on the upgraded fixture. | ✓ VERIFIED |
| Reach the outcome | The site's migrated data remains usable through its existing show-management model. | The preservation matrix covers migration snapshots, retry behavior, and post-upgrade CRUD across WordPress 7.0.6/7.1.2 and PHP 8.3.35/8.4.26/8.5.11. Fixtures are reconstructed; no live site or backup was tested. | ✓ VERIFIED |

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | After updating GigPress, existing show, artist, venue, and tour records, their IDs and relationships, and saved settings remain present and usable without data loss. | ✓ VERIFIED | `tracer-1.4`, `versions-1.0-1.2`, `versions-1.3-1.5`, `current-1.6`, and `settings-repeat` compare prefix-aware rows/options and explicit manifests; all 11 registered cases pass in every supported cell. The evidence is reconstructed fixture coverage, not a live-site backup. |
| 2 | Site owners can create, edit, copy, trash, and restore shows and continue managing artist, venue, and tour relationships using the existing data model. | ✓ VERIFIED | `show-lifecycle`, `entity-guards`, and `tour-undo` invoke production handlers and list-view functions against the upgraded fixture and assert exact row/relationship outcomes. |
| 3 | Interrupted or unprovable upgrades retain the old completion marker, journal verified steps, block unsafe mutations, and allow safe retry with duplicate-free data. | ✓ VERIFIED | `safety-1.4` injects failures at schema/data/final-marker boundaries; retry and mutation-readiness cases compare snapshots and check authorization. `admin/db.php` writes the final version only after verified steps and read-back. |
| 4 | Fresh, current, recognized legacy, and unsafe metadata states are distinguished; unknown states are not guessed or overwritten. | ✓ VERIFIED | The `metadata-classification` probe exercises fresh/current/legacy and malformed, unrecognized, or newer metadata conditions; bootstrap blocks unsafe metadata before migration. |
| 5 | Every recognized database source version 1.0–1.5 reaches 1.6 through a verified path, while populated 1.6 remains unchanged on repeat load. | ✓ VERIFIED | Version fixtures and migration probe cover direct source paths and current steady state; all case manifests and repeat assertions pass in the recorded matrix. |
| 6 | Incomplete upgrade state cannot be mutated through migration-dependent handlers, even if a callback is reached. | ✓ VERIFIED | `show-lifecycle` checks blocked add/update/trash/undo and nine additional mutation routes, including CSV upload side effects and invalid nonces; snapshots remain identical. |
| 7 | Artist and venue deletion is prevented while active or trashed shows reference the entity, without cascading or reassigning shows. | ✓ VERIFIED | The upgraded-fixture `entity-guards` probe checks both rendered delete controls and authoritative handlers for active, trashed, and unreferenced entities. |
| 8 | Tour delete/undo restores only shows owned by that operation and does not overwrite intervening reassignment. | ✓ VERIFIED | `tour-undo` tests overlapping/repeated deletes, separate ownership maps, reassignment, and retry behavior. |
| 9 | The aggregate registry executes every required preservation case once and fails closed for missing/duplicate/failed coverage. | ✓ VERIFIED | `probe.php` requires both migration and CRUD modules, checks the explicit 11-case registry, isolates each case, and validates each result before returning PASS. |
| 10 | Supported runtime evidence covers the latest WordPress 7.0 and 7.1 patches with PHP 8.3 and newer currently supported PHP branches, with no warnings or fatal errors. | ✓ VERIFIED | Recorded matrix: WordPress 7.0.6 and 7.1.2 × PHP 8.3.35, 8.4.26, and 8.5.11; every aggregate and full-workflow cell reports zero warnings, fatals, and plugin errors. PHP 8.2 is explicitly diagnostic-only. |
| 11 | The existing public/CSV full-workflow suite is run against the same migrated legacy database. | ✗ FAILED — DEFERRED | The full-workflow runner creates a separate disposable WordPress project and starts with a fresh database; it does not consume the migrated fixture. Phase 4 and Phase 5 roadmap criteria cover these remaining public and CSV surfaces, so this item is deferred there. |

**Score:** 10/11 must-haves verified; one plan-specific public/CSV integration check is deferred to later phases.

### Deferred Items

| # | Item | Addressed In | Evidence |
|---|------|--------------|----------|
| 1 | Run shortcodes, widgets/related-post displays, RSS, and iCalendar using the migrated legacy fixture. | Phase 4 | Roadmap Phase 4 success criterion 1 requires these existing public surfaces to continue working after update. |
| 2 | Run CSV import/export behavior using the migrated legacy fixture. | Phase 5 | Roadmap Phase 5 success criterion 1 requires established CSV columns, filters, related-post option, and row handling to remain compatible. |

The deferred item does not block DATA-01 or DATA-02. No other gaps remain after deferral.

### Required Artifacts

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `admin/db.php`, `gigpress.php` | Verified migration coordinator and bootstrap gate | ✓ VERIFIED | Present, substantive, and wired; coordinator classifies version state, journals verified steps, merges settings without resetting existing values, and advances the final marker after read-back. |
| `tests/compat/fixtures/upgrade-preservation/1.0.php`–`1.6.php` | Reconstructed input plus independent expected manifests | ✓ VERIFIED | All seven fixtures exist; migration probes load the selected source fixture and compare database snapshots to its explicit manifest. |
| `tests/compat/upgrade-preservation-migrations.php` | Historical migration, retry, and current-state assertions | ✓ VERIFIED | Substantive version-matrix tests are dispatched by `probe.php` and read each fixture from the versioned fixture directory. |
| `admin/handlers.php`, `admin/artists.php`, `admin/venues.php` | Existing show/relationship workflows and deletion protection | ✓ VERIFIED | Real handlers retain the existing model; shared dependency checks protect active and trashed references in views and handlers. |
| `tests/compat/upgrade-preservation-crud.php` | Post-upgrade lifecycle and relationship assertions | ✓ VERIFIED | Exercises actual handlers and captures view output against the reconstructed upgraded fixture. |
| `tests/compat/run.sh`, `tests/compat/probe.php`, `tests/compat/compose.yaml` | Isolated, fail-closed WordPress/PHP matrix and evidence validation | ✓ VERIFIED | Explicit Compose isolation, required-case aggregation, exact runtime evidence, and read-only evidence parser are present and connected. |
| `02-PRESERVATION-MATRIX.md` | Auditable support cells and scope boundary | ✓ VERIFIED | Exact six cells, image IDs, zero-error counts, required case statuses, PHP 8.2 exclusion, and reconstructed-fixture/no-live-site caveat are recorded. Current report validator passed. |

### Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `gigpress.php` | `admin/db.php` | `gigpress_db_bootstrap()` before module loading | ✓ WIRED | Manual trace confirms bootstrap is called before admin modules and hooks are registered. |
| `admin/db.php` | upgrade journal and `gigpress_settings` | `gigpress_upgrade_state`, verified step journal, final-marker read-back | ✓ WIRED | Implemented using `completed` journal entries; the PLAN's literal `completed_steps` pattern is stale, but the journal and marker-last path are present. |
| `tests/compat/probe.php` | migration/CRUD modules and fixture files | explicit case registry and version dispatch | ✓ WIRED | Probe requires both aggregate modules and dispatches named version cases to the migration module and fixtures. The PLAN pattern is declared at the dispatch site, not the target module, which explains the query miss. |
| `admin/artists.php`, `admin/venues.php` | `admin/handlers.php` | shared dependency predicate for display and deletion enforcement | ✓ WIRED | Both views call `gigpress_entity_has_show_dependencies()`; handlers enforce the same all-status rule. The PLAN's multi-file `from` value is not accepted by the generic link checker; manual trace passes. |
| `tests/compat/run.sh` | `02-PRESERVATION-MATRIX.md` | `preservation-evidence` parser | ✓ WIRED | Parser validates the exact six cells and support boundaries without rerunning containers; current invocation passed. |

### Data-Flow Trace

| Artifact | Data | Source | Produces Real Data | Status |
|----------|------|--------|--------------------|--------|
| `admin/db.php` and migration probes | Shows, artists, venues, tours, settings, linked posts | WordPress `$wpdb` tables/options seeded from version-specific fixture inputs | Yes; expected results are explicit fixture manifests independent of migration output | ✓ FLOWING |
| `admin/shows.php` / `admin/new.php` | Existing show values and entity selections | Database reads from GigPress tables and saved settings; submitted fields flow to production handlers | Yes; migrated row values are read and handler writes are read back by probes | ✓ FLOWING |

### Behavioral Spot-Checks and Probe Execution

| Behavior | Command / evidence | Result | Status |
|----------|-------------------|--------|--------|
| Upgrade preservation and post-upgrade CRUD across supported matrix | Recorded `matrix --scenario upgrade-preservation --case all --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL`, source `a3591fb13521bbddaea84e9be479aea302cee1b3` | Six cells pass; all 11 required cases pass with zero warnings, fatals, or plugin errors | ✓ PASS |
| Existing full workflows | Recorded `matrix --scenario full-workflows --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL`, source `a3591fb13521bbddaea84e9be479aea302cee1b3` | Six cells pass on fresh per-cell databases; does not prove those surfaces on migrated data (see Deferred Items) | ✓ PASS — LIMITED SCOPE |
| Evidence record and runtime boundary | `rtk bash tests/compat/run.sh preservation-evidence --report .planning/phases/02-data-and-upgrade-preservation/02-PRESERVATION-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3` | `status: PASS`; matrix source has no code changes between its source revision and current HEAD | ✓ PASS |

The full container matrices were not rerun during this verification; their recorded source is unchanged at HEAD, and the parser was run against the current report.

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|-------------|-------------|-------------|--------|----------|
| DATA-01 | 02-01, 02-02, 02-04 | Existing entities, relationships, settings, and content survive update. | PASS | Version fixtures 1.0–1.6, snapshot/manifests, retry and repeated-load cases, and the six-cell aggregate matrix. |
| DATA-02 | 02-03, 02-04 | Site owners can create/edit/copy/trash/restore shows and manage artist/venue/tour relationships. | PASS | Post-upgrade lifecycle, dependency, and tour-undo probes use production handlers and views; all pass in the aggregate matrix. |

No Phase 02 requirements are orphaned from the plans.

### Test Quality Audit

| Test File | Linked Requirement | Active | Skipped | Circular | Assertion Level | Verdict |
|-----------|-------------------|--------|---------|----------|-----------------|---------|
| `tests/compat/upgrade-preservation-migrations.php` | DATA-01 | Yes | 0 | No | Behavioral/value: independent fixture manifests, exact IDs/rows/options, repeat and retry assertions | PASS |
| `tests/compat/upgrade-preservation-crud.php` | DATA-02 | Yes | 0 | No | Behavioral/value: handler writes, exact row relationships, blocked-state snapshots, and view output | PASS |
| `tests/compat/probe.php` / `tests/compat/run.sh` | DATA-01, DATA-02 | Yes | 0 | No | Behavioral aggregation and fail-closed result checks | PASS |

**Disabled tests on requirements:** 0. **Circular patterns:** 0. The temporary CSV written by the CRUD probe is synthetic blocked-request input, not generated expected output.

### Decision Coverage

All 12 trackable decisions in `02-CONTEXT.md` are honored in plans, implementation, tests, or phase artifacts. No decision-coverage warnings.

### Anti-Patterns Found

| File | Pattern | Severity | Impact |
|------|---------|----------|--------|
| None | No unresolved debt markers, stubs, or implementation placeholders found in the reviewed phase code and probes. | — | — |

### Human Verification Required

None for DATA-01 or DATA-02. The WordPress integration harness exercises the migration and existing show-management handlers; the separate public/CSV migrated-data checks are explicitly deferred above.

### Gaps Summary

Both roadmap success criteria and DATA-01/DATA-02 are verified. The upgrade coordinator, historical fixture manifests, repeat/retry behavior, post-upgrade CRUD, dependency guards, and tour undo are wired and exercised by the recorded 11-case matrix. The report's six-cell runtime evidence is current with HEAD and its evidence validator passes. The only incomplete plan-specific extension is running public and CSV workflows against the same migrated fixture; later roadmap phases explicitly cover those surfaces, so it is deferred and does not block Phase 02.

---

_Verified: 2026-10-04T19:23:28Z_  
_Verifier: the agent (gsd-verifier)_
