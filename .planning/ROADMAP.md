# Roadmap: GigPress

## Overview

Modernize GigPress in compatibility-first slices. Establish the WordPress 7.0+ and PHP 8.3+ support baseline and resolve the reported admin-menu warning, then preserve existing stored data and deliver the approved improvements to administration, public listings, and CSV workflows. Existing records, settings, data model, published output, theme overrides, and CSV contracts remain compatibility boundaries throughout.

## Phases

- [ ] **Phase 1: Compatibility Baseline and Menu Diagnosis** - Establish supported runtime metadata and warning-free core workflows.
- [ ] **Phase 2: Data and Upgrade Preservation** - Keep existing records, relationships, settings, and CRUD behavior usable through updates.
- [ ] **Phase 3: Administration Workflows** - Improve show entry, show-list management, and settings while keeping established behavior.
- [ ] **Phase 4: Public Publishing** - Make default show listings responsive and preserve public output and theme override contracts.
- [ ] **Phase 5: CSV Import/Export** - Improve task clarity and outcome feedback while preserving CSV behavior and protecting mutations.

## Phase Details

### Phase 1: Compatibility Baseline and Menu Diagnosis

**Goal**: Site owners can run GigPress on the selected WordPress and PHP support matrix, and the admin-menu warning is traced and resolved.
**Mode:** mvp
**Depends on**: Nothing (first phase)
**Requirements**: COMP-01, COMP-02, COMP-03, COMP-04
**Success Criteria** (what must be TRUE):
  1. A controlled late-menu fixture reproduces the exact `separator-gigpress` key and the diagnostic identifies that fixture callback; GigPress's own equivalent late `separator-gp` mutation is corrected and this checkout's admin menu is warning-free on the supported WordPress/PHP matrix. The unavailable live site's callback identity is recorded as unprovable from repository evidence and is not inferred from the fixture.
  2. Site owners can activate GigPress and complete its existing administration, public-display, feed, and CSV workflows on WordPress 7.0 and the latest 7.1 release with PHP 8.3 and every newer PHP branch still supported upstream at validation time, without PHP warnings or fatal errors.
  3. Plugin metadata and readme declare WordPress 7.0 and PHP 8.3 minimums; the tested-up-to value names only a WordPress release that has passed compatibility checks.
  4. On an already-active site below PHP 8.3, GigPress remains active but loads no normal modules, hooks, functions, or workflows; only a persistent plugin-manager compatibility notice is registered, and normal behavior resumes automatically without that notice on PHP 8.3+.

**Plans**: 5 plans

Plans:
**Wave 1**
- [ ] 01-01-PLAN.md — Build the OrbStack-backed compatibility tracer, matrix runner, workflow probes, and active-but-inert runtime-floor contract.

**Wave 2** *(blocked on Wave 1 completion)*
- [ ] 01-02-PLAN.md — Reproduce and attribute the exact warning key in a controlled fixture, then document the repository-evidence boundary.
- [ ] 01-03-PLAN.md — Remove PHP 8 parser blockers and implement the active-but-inert WordPress 7.0/PHP 8.3 runtime contract.

**Wave 3** *(blocked on Wave 2 completion)*
- [ ] 01-04-PLAN.md — Replace late menu mutation with pure ordering and standard-order fallback.

**Wave 4** *(blocked on Wave 3 completion)*
- [ ] 01-05-PLAN.md — Run the full compatibility matrix and publish evidence-backed metadata.

**UI hint**: yes
**Research flags**: Reproduce the reported WordPress 7.1.2/PHP 8.2 warning as diagnostic context, inspect the final menu arrays and active ordering callbacks, verify the active-but-inert below-floor guard, and run the supported WordPress/PHP matrix through OrbStack's Docker-compatible Compose runtime. PHP 8.2 is not a support target and host PHP is not required.

### Phase 2: Data and Upgrade Preservation

**Goal**: Site owners can update GigPress and keep their existing records, relationships, settings, and show-management behavior usable.
**Mode:** mvp
**Depends on**: Phase 1
**Requirements**: DATA-01, DATA-02
**Success Criteria** (what must be TRUE):
  1. After updating GigPress, existing show, artist, venue, and tour records, their IDs and relationships, and saved settings remain present and usable without data loss.
  2. Site owners can create, edit, copy, trash, and restore shows and continue managing their artist, venue, and tour relationships using the existing data model.

**Plans**: TBD
**Research flags**: Inventory historical schema versions and upgrade branches; verify representative existing databases, settings, statuses, IDs, relationships, and linked records. Keep schema changes out of scope unless evidence requires a separately versioned migration.

### Phase 3: Administration Workflows

**Goal**: Site owners can enter show dates and times, manage show lists, and find settings with clearer guidance.
**Mode:** mvp
**Depends on**: Phase 2
**Requirements**: ADMIN-01, ADMIN-02, ADMIN-03, UX-01
**Success Criteria** (what must be TRUE):
  1. Site owners can distinguish show date, optional time, multi-day, and expiration fields; invalid values receive field-specific text feedback and remain available for correction.
  2. Site owners can filter shows by scope, artist, tour, venue, sort, and page size; filter choices stay visible through filtering and pagination, bulk actions affect only selected shows, and the result is reported.
  3. Site owners can find settings in clear groups with contextual help, while existing option keys, saved values, and setting meanings remain usable.
  4. Changed administration controls have associated labels and semantic table headings where applicable, work by keyboard, and provide text-based success and error feedback.

**Plans**: TBD
**UI hint**: yes

### Phase 4: Public Publishing

**Goal**: Site owners can publish readable show listings while existing public displays, feeds, and theme customizations continue to work.
**Mode:** mvp
**Depends on**: Phase 2
**Requirements**: PUB-01, PUB-02
**Success Criteria** (what must be TRUE):
  1. Existing shortcodes, widgets, related-post displays, RSS feeds, and iCalendar feeds continue to work with their established output contracts after the update.
  2. The bundled show listing is readable at a 320 CSS-pixel viewport without page-level horizontal scrolling, with existing show details and links available.
  3. Child-theme, parent-theme, and `wp-content/gigpress-templates` overrides continue to resolve with their existing filenames, variables, and CSS hooks.

**Plans**: TBD
**UI hint**: yes
**Research flags**: Verify real theme override layouts and resolver order; check RSS XML and iCalendar output against representative and hostile values, including punctuation and newlines, without applying HTML escaping to machine-readable formats.

### Phase 5: CSV Import/Export

**Goal**: Site owners can understand import/export tasks and their results while CSV exchanges retain established behavior and unsafe mutations are rejected.
**Mode:** mvp
**Depends on**: Phases 2 and 3
**Requirements**: CSV-01, CSV-02, SEC-01
**Success Criteria** (what must be TRUE):
  1. Site owners can import and export using the existing GigPress columns, supported filters, related-post option, and row-handling behavior.
  2. Import and export screens clearly label each task and report actionable results, including imported, duplicate, skipped, or rejected row counts and applicable reasons.
  3. Invalid inputs on changed administration and import/export paths are rejected with text feedback; unauthorized or nonce-invalid mutations do not change records, and output is safely encoded for its destination.

**Plans**: TBD
**UI hint**: yes
**Research flags**: Use representative CSV fixtures to verify duplicate/invalid rows, partial failures, row counts and reasons, spreadsheet safety, and import/export round trips while retaining current column and filter behavior.

## Progress

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Compatibility Baseline and Menu Diagnosis | 0/5 | Not started | - |
| 2. Data and Upgrade Preservation | 0/TBD | Not started | - |
| 3. Administration Workflows | 0/TBD | Not started | - |
| 4. Public Publishing | 0/TBD | Not started | - |
| 5. CSV Import/Export | 0/TBD | Not started | - |
