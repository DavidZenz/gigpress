# Phase 2: Data and Upgrade Preservation - Context

**Gathered:** 2026-10-04
**Status:** Ready for planning

<domain>
## Phase Boundary

Site owners can update GigPress and keep their existing show, artist, venue, and tour records, IDs, relationships, saved settings, and show-management behavior usable. This phase delivers DATA-01 and DATA-02 using the existing custom tables and administration workflows.

Carry forward the WordPress 7.0+ and PHP 8.3+ support baseline and the below-minimum runtime behavior from Phase 01. Historical database compatibility does not extend the supported WordPress or PHP minimums. Keep schema changes out of scope unless preservation evidence requires a separately versioned migration. The administration, public publishing, and CSV improvements remain in their roadmap phases.

</domain>

<decisions>
## Implementation Decisions

The user selected all four discussion areas (`1-4`) and then said, "recommendations are fine." The policies below are the agent's recommended decisions under that delegation; the unasked details were not individually selected by the user.

### Upgrade starting points
- **D-01:** Support direct updates from every database version the existing plugin recognizes: 1.0 through the current 1.6. Preserve the existing historical upgrade paths on supported modern runtimes rather than requiring an intermediate plugin installation.
- **D-02:** Inventory historical source schemas and migration branches, then cover each recognized starting version with populated, reproducible fixtures. Include settings, active and trashed records, nontrivial IDs, relationships, and linked WordPress posts where the source schema supports them. Fixtures may share setup for equivalent layouts, but every recognized version must have explicit coverage and expected results. No live-site backup or historical installation has been supplied; label reconstructed fixtures honestly.
- **D-03:** Verify that repeated plugin loads and activation/update transitions do not repeat destructive transformations, duplicate artists or venues, reset options, or change existing record IDs. Retain the active WordPress table prefix. Distinguish a fresh installation from an existing database with missing or invalid version metadata.

### What preserved means
- **D-04:** Preserve existing entity IDs, row contents, show-to-artist/venue/tour relationships, and `show_related` WordPress post references. Existing legacy migrations may transform fields or create newly introduced entities only as needed to preserve their established meaning; define explicit expected before/after results. Do not silently renumber, merge distinct records, discard content, or relink a show to an unrelated entity.
- **D-05:** Preserve saved settings, including intentional empty values, disabled flags, customized labels, and unknown option keys. Supply genuinely missing defaults safely without replacing the complete settings option or treating a deliberately disabled value as absent. Any necessary legacy setting transformation must be documented and verified.
- **D-06:** Retain active and trashed records and their stored dates, times, multi-day/expiration values, notes, ticket links, ordering, and relationships. Preserve existing linked WordPress posts without unexpected creation, deletion, or content changes. Unexpected records or broken references must remain available for diagnosis instead of being silently discarded or replaced with invented data.

### Unexpected or interrupted updates
- **D-07:** When an update cannot safely complete, stop the affected migration and show an actionable notice to an authorized administrator. Preserve stored content and references; do not reset tables/options, delete records, downgrade a newer unknown schema, or guess missing associations as automatic recovery.
- **D-08:** Mark a database upgrade complete only after its required steps are confirmed successful. A failed step must not advance the stored version or produce a false success. Make retry/recovery safe without duplicate records or repeated harmful transformations. Determine the actual mechanism from database and migration evidence; do not assume schema operations can be rolled back atomically.
- **D-09:** Keep WordPress usable while preventing GigPress mutations that depend on an incomplete migration. Research and planning determine which paths can safely continue and the notice wording. This phase does not add a backup/restore product interface or a general-purpose automatic repair tool.

### Trash, restore, and continued show management
- **D-10:** Editing, trashing, and restoring a show retain its ID, content, and artist/venue/tour/post references. Copying creates a distinct show and leaves its source intact, while retaining the existing form and related-post choices. Bulk operations affect only selected shows. Upgrades never empty trash automatically.
- **D-11:** Retain established tour deletion and undo behavior: detach the affected shows when the tour is trashed, then restore eligible associations on undo. Prevent unrelated shows from being attached or deliberate subsequent assignments from being overwritten. Characterize repeated tour deletions and intervening edits before changing the restore path; do not expand this into a new trash-history feature.
- **D-12:** Prevent artist or venue deletion while either active or trashed shows reference the entity, so a later show restore remains usable. Enforce this protection in the mutation handler as well as the visible control. Preserve the existing no-cascade intent and do not silently reassign dependent shows.

### The agent's Discretion

The user delegated recommended choices for these four areas. Researchers and planners may choose fixture organization, migration checks, safe retry mechanics, notice wording, and focused repairs to satisfy these preservation policies. Characterize existing behavior before altering it. Keep the procedural WordPress integration and established data model; a required schema change must carry its own versioned migration and preservation evidence. Authorization to use recommendations completes this discussion; it does not enable automatic planning or execution.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Product scope and carried decisions
- `.planning/PROJECT.md` — project purpose, support minimums, and preservation constraints.
- `.planning/REQUIREMENTS.md` — DATA-01 and DATA-02, existing data model, and scope boundaries.
- `.planning/ROADMAP.md` — Phase 2 goal, success criteria, and historical migration research flags.
- `.planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-CONTEXT.md` — runtime floor, inert behavior below PHP 8.3, and prior decisions.
- `.planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-COMPATIBILITY-MATRIX.md` — completed runtime and lifecycle evidence; historical schema upgrades still require separate proof.

### Migration and architecture guidance
- `.planning/research/ARCHITECTURE.md` — incremental modernization and existing persistence boundaries.
- `.planning/research/PITFALLS.md` — upgrade, settings, and data-preservation risks.
- `.planning/codebase/STACK.md` — existing runtime and database dependencies; mapped before Phase 01, so confirm current declarations in source.
- `.planning/codebase/ARCHITECTURE.md` — four-table model, settings, handlers, and WordPress integration.
- `.planning/codebase/INTEGRATIONS.md` — WordPress database, table prefixes, options, and related content boundaries.

No external specification or site database was provided during this discussion.

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `admin/db.php` defines the four tables, settings defaults, installation, and migrations. It dispatches versions 1.0–1.5 through the legacy upgrade functions before writing the current version.
- `gigpress.php` declares `GIGPRESS_DB_VERSION` as `1.6`, defines prefix-aware table constants, and maintains related-post integration.
- `tests/compat/compose.yaml`, `tests/compat/run.sh`, and `tests/compat/probe.php` provide the existing OrbStack/Compose WordPress/PHP harness and real-plugin workflow probes. Extend the existing harness for historical populated database fixtures instead of requiring host PHP.
- `admin/new.php` loads existing show data for edit and copy; `admin/handlers.php` performs record mutations and undo operations.

### Established Patterns
- Persistence uses WordPress `$wpdb`, four custom tables, and the `gigpress_settings` option. The legacy database migrations are in `admin/db.php`; `lib/upgrade.php` is a PHP compatibility shim, not the database migration module.
- Shows and tours use status-based trashing. Artist and venue deletion removes rows. Current UI counts exclude deleted shows, and deletion handlers lack the corresponding dependency guard; research this against D-12.
- Tour deletion clears earlier restore markers, detaches matching shows, and marks them with the shared `show_tour_restore = 1` flag. Undo reattaches all marked shows to the requested tour. The marker cannot by itself identify which deleted tour owned a show; repeated deletions and intervening reassignment require explicit characterization.
- The current upgrade path runs `dbDelta()` and legacy functions, then stores the current database version without checking aggregate migration success. Older transformations create artists/venues and extract state text from cities; inspect missing fields, no-match values, partial failure, and retry behavior.
- Some settings are rewritten during module load using `empty()` checks. Verify intentional empty/disabled values and missing keys against D-05.

### Integration Points
- Installation, module-load upgrade dispatch, settings reads/writes, and `db_version` in `admin/db.php`.
- Create/edit/delete/undo handlers in `admin/handlers.php`; dependent controls in `admin/artists.php`, `admin/venues.php`, `admin/tours.php`, and `admin/shows.php`.
- Copy/edit form state in `admin/new.php` and WordPress post links in `gigpress.php`.
- Repository-owned compatibility fixtures and probes under `tests/compat/`.

</code_context>

<specifics>
## Specific Ideas

Use recommended preservation policies without further preference interviews. Demonstrate the upgrade of populated historical schemas and continued CRUD behavior; fresh activation or an empty database alone is insufficient evidence. No real-site data was supplied, so fixture results must not be presented as verification of the user's live installation.

</specifics>

<deferred>
## Deferred Ideas

None were added by the user. Administration UX, public publishing, and CSV improvements remain in Phases 3–5 as already scheduled.

</deferred>

---

*Phase: 2-Data and Upgrade Preservation*
*Context gathered: 2026-10-04*
