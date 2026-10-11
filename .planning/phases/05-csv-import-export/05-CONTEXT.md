# Phase 05: CSV Import/Export - Context

**Gathered:** 2026-10-11
**Status:** Ready for planning

<domain>
## Phase Boundary

Deliver CSV-01, CSV-02 and SEC-01: site owners can understand import/export tasks and outcomes while exchanges retain the existing GigPress column format, supported artist/tour/date-scope filters, related-post option and row-handling behavior. Protect changed input, upload, mutation and output paths with the existing WordPress capability, nonce and database-readiness boundaries.

Carry forward WordPress 7.0+/PHP 8.3+ support and preservation of records, identities, relationships, settings, dates, optional time and expiration meanings. Verify CSV integration and round trips on populated migrated Phase 02 fixtures; fresh workflow checks alone do not close that obligation.

The inherited CR-04, CR-05 and CR-07 fixes in `.planning/CSV-REVIEW-FOLLOWUPS.md` are required work: destination-safe import notices; verified, recoverable tour-to-artist conversion; textual show-status writes without positional format drift. The user decision for preexisting status `0` is D-13 below.

Keep the procedural WordPress administration integration and established CSV schema. Arbitrary third-party column mapping, an import-preview wizard, new export formats, external synchronization, a replacement data model and a general repair product remain outside this phase.

</domain>

<decisions>
## Implementation Decisions

### Import/export screen

- **D-01:** Keep both tasks on one page in stacked sections, Import first and Export below, both immediately visible. This follows the existing settings-page organization rather than adding tabs.
- **D-02:** Keep essential CSV requirements visible beside the upload control. Provide an expandable reference containing column names, formats and examples.
- **D-03:** Keep “Include related-post associations” as a visible checkbox, unchecked by default. Explain that related post IDs must match posts on the destination site. Preserve the existing option's intent rather than silently enabling associations.
- **D-04:** Use the action labels “Import shows” and “Download CSV,” making clear that importing saves records and exporting downloads a file.

### Import results and retry

- **D-05:** Show a summary with imported, duplicate, rejected and failed counts, plus expandable row details for each outcome group. Preserve the inherited skipped-row behavior while making its reasons clear; an existing skipped outcome must be counted truthfully in the appropriate group, not lost or counted twice.
- **D-06:** Rejected and failed details identify the CSV row number, show identity, affected field where applicable, and correction guidance. Example: “Row 8: invalid Date; use YYYY-MM-DD.” System failures need a truthful reason and an available next step rather than an invented field error.
- **D-07:** Stay on Import/Export after importing. Keep the result visible, offer a link to the show list and make another upload available.
- **D-08:** Recommend correcting the original CSV and uploading it again. Explain that existing matching shows are skipped as duplicates and are not overwritten. Safe retry must avoid duplicating completed shows or related records; characterize the established matching behavior before changing the importer.

### Export behavior

- **D-09:** Show the selected artist, tour and date-scope filters and matching show count beside “Download CSV.” Preserve all existing filters and their meanings. Example: “Upcoming shows · All artists · Summer Tour · 24 shows.” The summary/count must describe the actual export query.
- **D-10:** When no shows match, stay on the page, display “No shows match these export filters” and offer Reset filters. Produce no download. Reset returns to the existing all-artists/all-tours/all-dates defaults.
- **D-11:** Retain export choices for the current visit, including after downloads or errors. Later visits use the existing defaults. Do not add persistent per-user export preferences.
- **D-12:** Provide a short visible spreadsheet note plus expandable steps explaining how to import values as text and preserve them when editing for re-import. Retain the CSV transfer contract. The user chose guidance placement; they did not select a new spreadsheet-specific output mode or a transformation of exported values.
- **D-17:** Planning clarification, 2026-10-11: preserve exact exported values, including formula-like strings and legitimate leading-plus phone numbers, with explicit spreadsheet text-import guidance. The user accepted that direct opening can execute formulas. Do not silently prefix, strip, rewrite or block those values. Verify CSV structure and literal round trips; any spreadsheet safety claim must be limited to the specifically observed text-import workflow, not universal direct-open or save/reopen safety.

### Legacy records and conversion

- **D-13:** Preserve preexisting show records with status `0` and identify them for manual correction. Do not infer Active, Cancelled or Sold out, normalize all such records to Active, or impose a blanket CSV-export block. This policy concerns existing corrupted records; required validation and textual handling of supported statuses in new imports still apply.
- **D-14:** Before tour-to-artist conversion, show the affected tour names and show counts. Explain reassignment and removal of successfully converted source tours, with Confirm and Cancel. Carry forward the prior phase's confirmed-action and mutation-protection patterns.
- **D-15:** Reuse an artist when there is exactly one exact name match, preserving that artist's existing details. When multiple artists match, leave that tour unchanged and report the ambiguity. For a tour without a match, preserve the existing ability to create its artist; retries must not duplicate a previously created target.
- **D-16:** Continue independent tours when one conversion fails, and accumulate every outcome. Preserve failed relationships for safe retry without duplicating completed work. Remove a source tour only after its artist identity and all required show reassignments have been verified. Test early and late INSERT/UPDATE/DELETE failures as required by CR-05; partial results must never be reported as complete success.

### Agent's Discretion

The user selected all four areas and explicitly chose each substantive decision above; no area was delegated wholesale. Exact supporting copy, spacing, accessible disclosure/focus mechanics, request-state handling, row-number conventions, fixture organization, recovery bookkeeping and safe output encoding are implementation details for research and planning.

Characterize existing CSV parsing, matching, related-post and export semantics before altering them. D-17 resolves the researched spreadsheet formula/literal-transfer tradeoff; it does not waive SEC-01 upload, mutation, HTML-output or CSV-structure controls. No arbitrary mapping wizard or new export mode was selected.

Creating this context authorizes the discussion artifacts and commits; it does not start planning or execution automatically.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Scope and required fixes

- `.planning/PROJECT.md` — compatibility-first purpose, support floor and preservation boundaries.
- `.planning/REQUIREMENTS.md` — CSV-01, CSV-02, SEC-01, existing CSV schema and excluded preview/mapping features.
- `.planning/ROADMAP.md` — Phase 05 goal, success criteria, migrated CSV obligation and dependency on Phases 02/03.
- `.planning/CSV-REVIEW-FOLLOWUPS.md` — mandatory CR-04/CR-05/CR-07 fixes and required failure, hostile-output and status evidence.

### Carried decisions and evidence

- `.planning/phases/02-data-and-upgrade-preservation/02-CONTEXT.md` — identity, relationships, linked posts, settings and failure/retry preservation.
- `.planning/phases/02-data-and-upgrade-preservation/02-VERIFICATION.md` — recognized reconstructed migration evidence and deferred migrated CSV check.
- `.planning/phases/03-administration-workflows/03-CONTEXT.md` — confirmed actions, accessible controls and truthful recoverable feedback.
- `.planning/phases/03-administration-workflows/03-REVIEW.md` — origin of the inherited CSV/conversion findings.
- `.planning/phases/03-administration-workflows/03-REVIEW-DISPOSITION.md` — required disposition of those findings into Phase 05.
- `.planning/phases/04-public-publishing/04-CONTEXT.md` — retained public contracts and separation of public and CSV verification.
- `.planning/phases/04-public-publishing/04-VERIFICATION.md` — completed public verification; shared-helper changes need to preserve its contracts.

### Existing architecture

- `.planning/codebase/STACK.md` — bundled CSV parser and WordPress dependencies; mapping predates modernization, so current source governs.
- `.planning/codebase/ARCHITECTURE.md` — procedural handlers, custom tables, WordPress routing and persistence.
- `.planning/codebase/CONVENTIONS.md` — naming, translations and destination encoding patterns.

No external specification or live-site dataset was supplied. The agent consulted [Microsoft's CSV import guidance](https://support.microsoft.com/en-us/excel/get-started/import-or-export-text-txt-or-csv-files) while discussing D-12, and [OWASP's CSV injection guidance](https://community.owasp.org/attacks/CSV_Injection) as research background. These sources inform research; they are not additional user-selected output policies.

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets

- `admin/import-export.php` — existing upload form, related-post checkbox and artist/tour/scope export controls, currently in separate side panels.
- `admin/handlers.php` — `gigpress_import()` implements CSV parsing, name-based entity lookup/creation, show duplicate matching, inserts and outcome notices; `gigpress_map_tours_to_artists()` performs the required inherited conversion.
- `gigpress.php` — `gigpress_export()` uses the existing CSV columns and query, converts unspecified time/end dates to empty fields and adds related-post URLs.
- `lib/parsecsv.lib.php` — bundled parser/serializer; no new package was selected.
- `admin/artists.php` — existing conversion route and artist-management integration.
- `admin/new.php`, `admin/shows.php`, `admin/settings.php`, `css/gigpress-admin.css` and `scripts/gigpress-admin.js` — established labelled controls, confirmed actions, feedback and grouped-screen patterns.
- `tests/compat/run.sh`, `tests/compat/probe.php`, `tests/compat/fixtures/shows.csv` and `tests/compat/fixtures/upgrade-preservation/` — OrbStack-backed real-WordPress checks and populated reconstructed migration inputs.

### Established Patterns

- Forms and results are server-rendered and translated through WordPress. Preserve keyboard operation, associated labels and text-based feedback.
- Current duplicate matching uses artist, date, time and venue, excluding trashed shows. Current entity matching uses artist name, active tour name, and venue name/city/country. These are compatibility boundaries to characterize, not permission to overwrite existing records.
- Export uses 23 established columns, comma-separated output, descending date/time order and excludes deleted shows. Preserve date-only, unspecified-time, status and related-post meanings.
- The importer currently has the reviewed positional `%d` status defect and unescaped outcome text; current upload/related-post handling also requires boundary validation under SEC-01.
- Conversion currently creates an artist, reassigns shows and deletes each source tour without verifying all operations, and reports only the last tour's variables. Required repairs must establish truth across the full batch and preserve retry state.
- Reuse capability/nonce/readiness checks and owned fixture lifecycle patterns. Source-bound automated evidence, migrated-data integration and actual human/browser acceptance are separate claims.

### Integration Points

- Coordinate screen controls, count/filter state and empty-result recovery between `admin/import-export.php` and `gigpress_export()`.
- Coordinate upload/row validation, entity identity, related-post associations, writes and safe notices in `gigpress_import()`.
- Coordinate conversion confirmation in the existing administration route with verified per-tour outcomes and safe retry in its handler.
- Extend the existing compatibility harness for CSV round trips, hostile values, supported statuses, invalid legacy records and injected database failures on populated migrated fixtures.

</code_context>

<specifics>
## Specific Ideas

- Selected labels: “Import shows,” “Download CSV,” and “Include related-post associations.”
- Empty export: “No shows match these export filters,” followed by Reset filters.
- Rejected-row example: “Row 8: invalid Date; use YYYY-MM-DD.”
- The user selected `1-4`, chose option 1 for all 16 substantive questions, advanced after each area with option 2, and explicitly approved context creation with option 2.

</specifics>

<deferred>
## Deferred Ideas

None introduced — discussion stayed within Phase 05 scope. Existing future arbitrary CSV mapping/preview features remain deferred in `.planning/REQUIREMENTS.md`. Phase 04 UI advisory findings remain documented in its own review and do not expand this CSV phase.

</deferred>

---

*Phase: 05-CSV Import/Export*
*Context gathered: 2026-10-11*
