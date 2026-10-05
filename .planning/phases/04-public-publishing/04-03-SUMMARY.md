---
phase: 04-public-publishing
plan: 03
subsystem: ui
tags: [wordpress, public-html, escaping, json-ld, compatibility]

# Dependency graph
requires:
  - phase: 04-public-publishing
    provides: Migrated public fixtures and compatible bundled listing layout from plans 04-01 and 04-02.
provides:
  - Plain machine values alongside compatible safe prepared HTML fragments.
  - Script-safe main and related JSON-LD from exact plain event values.
  - Contextual output escaping across main, related, widget, grouped-heading, and subscription surfaces.
  - Parsed hostile-value evidence with unchanged storage snapshots.
affects: [04-04, 04-05, public-templates, feeds]

# Actuals measured on the plan estimate scale: chars/4 over the realized code diff.
actuals:
  tokens: 11609
  tasks: 3
  commits: 3
commits: 3
plan_head_before: f41588a3075a4682a10eff79a47360a09008248a
plan_head_after: ebc563bf130e62612e7afbbd855291832088670d

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Preserve established HTML fragment keys while exposing separate plain machine values.
    - Escape untrusted text and URLs at the final HTML destination; retain KSES-approved notes.
    - Hex-escape HTML-sensitive JSON characters before embedding structured data in script elements.

key-files:
  created:
    - tests/compat/public-html.php
  modified:
    - gigpress.php
    - output/gigpress_shows.php
    - output/gigpress_related.php
    - templates/shows-list.php
    - templates/shows-list-start.php
    - templates/shows-list-footer.php
    - templates/sidebar-list.php
    - templates/related.php
    - templates/shows-artist-heading.php
    - templates/sidebar-artist-heading.php

key-decisions:
  - "Keep every legacy prepared HTML key and owner-template variable, adding plain values for machine output."
  - "Keep valid migrated ticket destinations while rejecting unsafe protocols at HTML destinations."
  - "Escape grouped artist headings and configured table labels after the hostile parser case exposed those adjacent raw outputs."

patterns-established:
  - "Plain values are used for JSON-LD; safe prepared fragments remain available to existing template callers."
  - "Bundled headings and labels that consume stored or configured text use contextual escaping."

requirements-completed: [PUB-01, PUB-02]

# Coverage metadata
coverage:
  - id: D1
    description: "Main and grouped listings preserve compatible fragments while plain JSON-LD values parse exactly and cannot close their script element."
    requirement: PUB-01
    verification:
      - kind: e2e
        ref: "rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario public-publishing --case html-json"
        status: pass
    human_judgment: false
  - id: D2
    description: "Related, widget, subscription, and bundled partial outputs keep intended links, classes, rich notes, and inert hostile values without changing stored snapshots."
    requirement: PUB-02
    verification:
      - kind: e2e
        ref: "rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario public-publishing --case html-json"
        status: pass
    human_judgment: false

# Metrics
duration: 23min
completed: 2026-10-05
status: complete
---

# Phase 04 Plan 03: Safe Public HTML and JSON-LD Summary

**Public listings now retain their template contract while rendering hostile values inertly and serializing exact event data safely.**

## Performance

- **Duration:** 23 min, including recovery of the already committed first two tasks.
- **Started:** 2026-10-05T10:28:57Z
- **Completed:** 2026-10-05T10:51:00Z
- **Tasks:** 3
- **Files modified:** 11

## Accomplishments

- Added a plain prepared-value seam while retaining existing HTML-bearing keys and template variables; main and related JSON-LD now use plain values and HTML-sensitive WordPress JSON encoding.
- Extended the hostile-value parser case across grouped and ungrouped main output, linked related output, widget output, ticket/calendar links, and subscription links. It checks exact event values, intended script counts, permitted note formatting, unsafe protocols, and unchanged storage snapshots.
- Escaped configured labels and stored values at bundled partial boundaries without changing layout, classes, status visibility, saved ticket destinations, or compact surfaces.

## Task Commits

Each task was committed atomically:

1. **Task 04-03-01: Carry one hostile migrated show safely through main HTML and JSON-LD** - `a329142` (`feat`)
2. **Task 04-03-02: Extend plain-value and script-safe output to related and widget paths** - `240943a` (`feat`)
3. **Task 04-03-03: Close contextual encoding at every bundled partial destination** - `ebc563b` (`fix`)

**Plan metadata:** Task commits are measured above; this summary, STATE.md, and ROADMAP.md are included in the final documentation commit.

## Files Created/Modified

- `tests/compat/public-html.php` - Hostile main, grouped, related, widget, and subscription parser checks with storage snapshots.
- `gigpress.php` - Plain-value preparation and safe compatible prepared fragments.
- `output/gigpress_shows.php` - Plain-value main JSON-LD and script-safe serialization.
- `output/gigpress_related.php` - Plain-value related JSON-LD with enabled and disabled behavior preserved.
- `templates/shows-list.php`, `templates/shows-list-start.php`, `templates/shows-list-footer.php` - Context-safe labels, classes, and subscription attributes.
- `templates/sidebar-list.php`, `templates/sidebar-artist-heading.php` - Context-safe widget values and grouped headings.
- `templates/related.php`, `templates/shows-artist-heading.php` - Context-safe related and main grouped headings.

## Decisions Made

- Preserved the legacy fragment contract and added plain values for machine consumers.
- Retained the valid saved ticket URL from the migrated fixture while using protocol-filtered final URL output.
- Escaped the table header label and grouped artist headings after the hostile parser case demonstrated those related bundled paths were still raw.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 2 - Missing Critical Functionality] Escaped raw table labels and grouped artist headings**
- **Found during:** Task 3 (bundled partial encoding)
- **Issue:** The parser case found a hostile configured artist label rendered in `shows-list-start.php` and hostile stored artist markup rendered in grouped headings, outside the four partials named by the task.
- **Fix:** Applied text escaping to the table label and both bundled artist heading templates; extended the existing parsed output assertions to cover grouped heading behavior.
- **Files modified:** `templates/shows-list-start.php`, `templates/shows-artist-heading.php`, `templates/sidebar-artist-heading.php`, `tests/compat/public-html.php`
- **Verification:** The focused `html-json` compatibility cell passed all 27 named assertions with zero plugin errors.
- **Committed in:** `ebc563b`

**Total deviations:** 1 auto-fixed (Rule 2)
**Impact on plan:** The adjacent bundled outputs were required for the stated hostile-value and grouped-output contracts; no layout or storage behavior changed.

## Issues Encountered

- OrbStack access was denied by the default sandbox on the first test attempt. The explicitly authorized elevated retry ran successfully.
- The first parser run identified the unescaped table label and grouped heading paths; those were corrected before the passing run.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Main, related, widget, grouped-heading, calendar, and subscription public output passed the focused parsed contract. Plan 04-04 can proceed.
- Final browser measurement and calendar-client acceptance remain assigned to plan 04-05.

---
*Phase: 04-public-publishing*
*Completed: 2026-10-05*

## Self-Check: PASSED

- Summary file exists at the expected plan path.
- Task commits `a329142`, `240943a`, and `ebc563b` are present in Git history.
- `git diff --check` passed.
