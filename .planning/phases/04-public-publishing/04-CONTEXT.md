# Phase 04: Public Publishing - Context

**Gathered:** 2026-10-05
**Status:** Ready for planning

<domain>
## Phase Boundary

Deliver PUB-01 and PUB-02: site owners can publish readable show listings while existing shortcodes, widgets, related-post displays, RSS and iCalendar feeds, and theme customizations continue to work. The bundled main listing must remain readable at a 320 CSS-pixel viewport without page-level horizontal scrolling, with existing show details and links available.

Carry forward WordPress 7.0+/PHP 8.3+ support and Phase 02 preservation of records, relationships, settings, dates, optional time and expiration semantics. Preserve public filtering/grouping/order, shortcode and widget interfaces, feed contracts and settings, and theme override filenames, variables and CSS hooks. Child-theme, parent-theme and wp-content/gigpress-templates resolution retain their current priority. Exercise publishing and feeds on populated migrated Phase 02 fixtures; prior fresh-database workflow results do not establish this deferred acceptance.

CSV/import-export improvements and required inherited CR-04/05/07 review fixes belong to Phase 05. No new calendar provider, event-management capability, public filtering product, data-model replacement or wholesale template rewrite is introduced.

</domain>

<decisions>
## Implementation Decisions

### Phone layout

- **D-01:** On narrow screens, stack each show into a labelled block: date first, then artist, location and details. Retain the main table on wider screens. Meet the roadmap's 320 CSS-pixel readability requirement without page-level horizontal scrolling.
- **D-02:** Show all available additional information immediately: time, admission, address, notes and links wrap beneath the main information. Do not introduce a Show details disclosure or hide secondary information in the bundled phone layout.
- **D-03:** Keep artist/tour group headings above their shows and preserve existing grouping and order. Do not repeat group context in every show block solely for the phone layout.
- **D-04:** Separate stacked shows with subtle dividers and comfortable spacing, fitting the existing listing rather than introducing bordered cards or a dense presentation.

### Details and status

- **D-05:** Combine clear Cancelled/Sold out text with prominent badges and fully readable show details. The user explicitly confirmed the combined choice after initially replying 1-2. Preserve established status visibility and ticket-link rules; badges are presentation, not a new filtering policy.
- **D-06:** When start time is unspecified, omit the public time line. Preserve the existing distinction between unspecified time and actual midnight.
- **D-07:** Display multi-day dates as one start – end range using the site's saved date format. Preserve stored dates, formatting settings and expiration behavior.
- **D-08:** Present available ticket actions as clearly labelled, emphasized links within the show details. Keep the saved ticket label and destination; do not switch to button-style actions.

### Calendar links

- **D-09:** Show both existing Google Calendar and per-show iCalendar links directly where existing display rules provide them. These actions must be usable by keyboard and with JavaScript disabled; the bundled listing must not require the former Add toggle to reveal them.
- **D-10:** Use the bundled action labels Add to Google Calendar and Download iCalendar.
- **D-11:** Place per-show calendar actions beside the date and time, higher in the listing. At narrow widths the actions may wrap within that date/time area while remaining visible and readable.
- **D-12:** Keep the existing RSS/iCalendar subscriptions below the listing as a compact Subscribe group that wraps when needed. Preserve current enable/disable settings, endpoints and artist/tour/venue filtering; this does not add new subscription behavior.

### Theme styling and customization

- **D-13:** Inherit the theme's fonts and link colors. Add only the spacing, dividers and status treatment needed for clarity; do not introduce a self-contained GigPress typography/color system.
- **D-14:** Apply the new phone layout to bundled templates. Existing custom templates remain under the site owner's control; provide guidance for adopting the new styling. Preserve filenames, variables, CSS hooks and override priority. Retaining a class hook must not automatically force the new stacked layout onto a custom template.
- **D-15:** Use the width supplied by the theme's content area, including narrower widget areas. Do not add a plugin-defined maximum width.
- **D-16:** Keep widgets and related-show displays compact, improving wrapping and status readability where needed. Do not restyle them as replicas of the main stacked listing.

### Agent's Discretion

The user selected all four areas and explicitly chose every decision above. No entire area was delegated. Responsive thresholds, precise spacing/badge treatment, markup and semantics, isolation of bundled responsive styles while retaining existing hooks, safe destination encoding, feed serialization, fixture organization and adoption-documentation placement are implementation details for research/planning. Preserve the locked output, data and customization boundaries. Creating this context does not authorize automatic planning or execution.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Scope and contracts

- `.planning/ROADMAP.md` — Phase 04 goal, PUB-01/PUB-02 success criteria, migrated-fixture publishing obligation and Phase 05 boundary.
- `.planning/REQUIREMENTS.md` — Public display/feed/override contracts, 320 CSS-pixel requirement and existing preservation/safety requirements.
- `.planning/PROJECT.md` — Compatibility-first scope, support minimums and theme/data preservation constraints.

### Prior decisions and acceptance

- `.planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-CONTEXT.md` — Support floor and diagnostic-only PHP 8.2.
- `.planning/phases/02-data-and-upgrade-preservation/02-CONTEXT.md` — Stored fields, relationships, settings, linked posts and recognized legacy upgrade preservation.
- `.planning/phases/02-data-and-upgrade-preservation/02-VERIFICATION.md` — Completed reconstructed-fixture preservation evidence and deferred migrated public/feed/template acceptance.
- `.planning/phases/03-administration-workflows/03-CONTEXT.md` — Optional time, date/expiration and saved-settings meanings retained by administration work.
- `.planning/phases/03-administration-workflows/03-VERIFICATION.md` — Completed administration verification and evidence boundaries; Phase 05 CSV/conversion defects remain deferred.
- `.planning/CSV-REVIEW-FOLLOWUPS.md` — Required inherited CSV/conversion fixes assigned to Phase 05, not silently accepted or reassigned here.

No external specs, reference designs or user-supplied documents were introduced in this discussion. The codebase maps predate modernization; current source and verification records govern current behavior.

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets

- `output/gigpress_shows.php` — Main listing/shortcode queries, grouping, monthly/yearly menu and JSON-LD emission through existing prepared show data and partials.
- `templates/shows-list-start.php`, `templates/shows-list.php`, `templates/shows-list-end.php` — Main table, dynamic columns, group/show rows and secondary detail row. Preserve exposed variables and existing class hooks while adapting bundled presentation.
- `templates/shows-list-footer.php`, `templates/shows-artist-heading.php`, `templates/shows-tour-heading.php`, `templates/shows-list-empty.php` — Group headings, existing subscription/filter URLs and configured empty output.
- `css/gigpress.css` — Existing public table/list/calendar/status styles. The current main table has no responsive media rules; shared class selectors and inherited cancelled-text styling need careful compatibility treatment.
- `scripts/gigpress.js` — Existing calendar-links toggle and month/year navigation. Direct calendar visibility must remain usable without this enhancement.
- `output/gigpress_sidebar.php`, `templates/sidebar-list.php`, `output/gigpress_related.php`, `templates/related.php` — Compact widget/sidebar and related-post surfaces with established markup/variables.
- `output/feed.php`, `output/ical.php` — Existing RSS and iCalendar output boundaries; preserve formats, endpoints and filter semantics while verifying punctuation, newlines and hostile values.
- `gigpress.php` — Shared gigpress_prepare, calendar/ticket links, template resolver, custom stylesheet loading and feed hooks. Changes here can affect custom templates, feeds and administration; characterize each affected contract.
- `tests/compat/run.sh`, `tests/compat/probe.php`, `tests/compat/compose.yaml`, `tests/compat/fixtures/upgrade-preservation/` — Existing OrbStack real-WordPress runtime matrix and populated recognized-version fixtures; reuse them for migrated publishing acceptance.
- `tests/compat/ADMIN-BROWSER.md`, `tests/compat/compose.browser.yaml` — Existing owned disposable HTTP/browser fixture pattern; actual interaction observations remain distinct from HTTP/PHP assertions.

### Established Patterns

- Procedural WordPress callbacks return buffered HTML and include overridable PHP partials with prepared show arrays and caller variables.
- Template resolution checks child theme, parent theme, wp-content/gigpress-templates, then bundled files. Theme gigpress.css and saved disable-css/disable-js choices are established customization paths.
- Optional artist/country columns, grouping, status classes, linked notes/details and configured labels are existing public contracts. Stacking must retain their meaning and available content.
- Calendar links are currently concealed by CSS until a jQuery toggle opens them. Direct visibility is the chosen bundled presentation; do not accidentally force custom override markup to change.
- RSS/XML, iCalendar, HTML and JSON-LD have different encoding boundaries. Feed correctness and safe output are technical research obligations, not opportunities to redefine saved content or date/time meaning.
- Runtime evidence uses positive named assertions, populated expected fixtures, pinned versions/images and source fingerprints. Fresh fixture results and markup presence do not substitute for migrated integration or actual 320px/browser observation.

### Integration Points

- Bundled show partials and scoped public styles provide the new wide/narrow presentation; shared preparation and override lookup connect it to all current output surfaces.
- Existing widget/related partials receive limited readability adjustments within D-16.
- Existing public/feed handlers and migrated fixtures provide preservation checks for shortcodes, widgets, related posts, RSS/iCalendar and each override location/priority.

</code_context>

<specifics>
## Specific Ideas

- Keep desktop tables; use stacked, labelled shows on phones with every available detail visible.
- Status badges say Cancelled or Sold out; readable details remain present.
- Calendar actions say Add to Google Calendar and Download iCalendar and sit near the date/time.
- Preserve saved ticket labels/destinations and date formats; unspecified time remains omitted.
- The user selected 1-4, completed all four discussion areas, and explicitly chose to create this context. The calendar placement and combined status badge choices differ from the initial recommendations and must be carried into planning.

</specifics>

<deferred>
## Deferred Ideas

None introduced — the discussion stayed within Phase 04 scope. Existing CSV/conversion fixes and migrated CSV acceptance remain required Phase 05 work; they are prior roadmap assignments rather than new discussion requests.

</deferred>

---

*Phase: 04-public-publishing*
*Context gathered: 2026-10-05*
