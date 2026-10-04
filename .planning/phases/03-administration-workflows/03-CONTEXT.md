# Phase 03: Administration Workflows - Context

**Gathered:** 2026-10-04
**Status:** Ready for planning

<domain>
## Phase Boundary

Improve the existing add/edit show form, show list, and settings screen so site owners can enter dates and times, manage selected shows, and find settings with clearer guidance. Deliver ADMIN-01, ADMIN-02, ADMIN-03, and UX-01 from the roadmap.

Carry forward the WordPress 7.0+/PHP 8.3+ support floor and Phase 02 preservation guarantees. Existing records, IDs, relationships, settings, expiration semantics, and mutation protections remain compatibility boundaries. Public publishing and CSV improvements belong to Phases 04 and 05. A wholesale admin/framework rewrite, a replacement data model, and new repair or event-management capabilities are outside this phase.

</domain>

<decisions>
## Implementation Decisions

### Date/time entry

- **D-01:** Use a date picker for show dates and multi-day end dates, with clearer time dropdowns. Keep time optional and respect the existing configured 12/24-hour format.
- **D-02:** Label the time controls “Time (optional)” and offer “Not specified.” Keep the dropdowns visible; enable minutes once an hour is selected. Preserve the existing meaning and storage of a show without a time.
- **D-03:** Keep the multi-day checkbox. Checking it reveals “End date — last day of the event,” with short help explaining when the show leaves the upcoming list. Preserve the existing expiration behavior.
- **D-04:** Show field-specific error text and a summary that links to the affected fields. Preserve all entered values after invalid submissions so users can correct them.

### Filters and bulk actions

- **D-05:** Remember scope and page size between visits, retaining the existing per-user behavior. Preserve all active choices—scope, artist, tour, venue, sort, and page size—through filtering, sorting, and pagination, and keep their selected values visible. Do not add persistent between-visit preferences for the other filters.
- **D-06:** “Reset filters” clears artist, tour, and venue only. Keep the current scope, sort order, and page size, and return to page 1.
- **D-07:** Before moving selected shows to trash, confirm the action with the explicitly selected count and offer Confirm or Cancel. Only explicitly selected shows may be affected.
- **D-08:** Report bulk outcomes with counts and actionable details, including an explanation for each failure. Keep the current filters and sort order after the action.

### Settings organization

- **D-09:** Keep settings on one page with clear section headings, “Jump to section” links, and one Save changes action.
- **D-10:** Use six focused groups: Display & formatting; Show labels & links; Related posts; Feeds; Permissions; Advanced.
- **D-11:** Provide short inline explanations, normally one or two sentences where a setting needs explanation. Include examples for formats and links to longer guidance where useful.
- **D-12:** Keep Advanced visible immediately, using the same layout as other sections and a jump link. Do not collapse it by default.
- **D-13:** Reorganization must retain existing option keys, saved values, and setting meanings, including hidden, unknown, and falsey settings preserved by Phase 02.

### Errors and accessibility

- **D-14:** After successfully adding a show, stay on the add-show screen. Show a clear success message, offer links to edit the saved show or view the list, and prepare the form for another show. Preserve existing create/edit/copy identity behavior.
- **D-15:** If a system problem blocks saving, explain the reason in plain language and provide a next step available in the existing workflow. Preserve entered values for correction or retry. Do not invent a new repair workflow.
- **D-16:** Require confirmation for moving an individual show to trash too. Identify the show and offer Confirm or Cancel, consistent with bulk trash actions.
- **D-17:** When filters produce no matching shows, explain “No shows match these filters” and offer Reset filters using D-06's behavior.
- **D-18:** Changed controls must have associated labels, semantic table headings where applicable, keyboard operation, and text-based success/error feedback. Preserve existing capability checks, nonce checks, and readiness guards while changing forms and confirmation flows.

### Agent's Discretion

The user explicitly selected each decision above; no area was delegated wholesale. Exact copy beyond the agreed labels, spacing, mapping existing settings into the six groups, focus/announcement mechanics, and the implementation of accessible confirmations are normal implementation details. Follow the existing WordPress administration patterns and the locked accessibility/preservation requirements.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Scope and requirements

- `.planning/ROADMAP.md` — Phase 03 goal, dependency, success criteria, and the boundaries of Phases 04/05.
- `.planning/REQUIREMENTS.md` — ADMIN-01, ADMIN-02, ADMIN-03, UX-01, and the retained data/security contracts.
- `.planning/PROJECT.md` — Compatibility-first project scope and preservation constraints.

### Prior decisions and evidence

- `.planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-CONTEXT.md` — WordPress/PHP support floor and existing admin integration decisions.
- `.planning/phases/02-data-and-upgrade-preservation/02-CONTEXT.md` — Upgrade, settings, identity, relationship, and mutation-readiness guarantees.
- `.planning/phases/02-data-and-upgrade-preservation/02-VERIFICATION.md` — Completed preservation evidence and deferred public/CSV migration integration checks.
- `.planning/phases/02-data-and-upgrade-preservation/02-UI-REVIEW.md` — Existing advisory findings about feedback and confirmations; findings do not expand Phase 03 scope.

No external specs or user-supplied design references were introduced in this discussion. Prior context descriptions may predate Phase 02 implementation; use current source and verification evidence for current behavior.

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets

- `admin/new.php` — Existing add/edit/copy form, populated values, date/time controls, and multi-day end-date row.
- `admin/shows.php` — Existing server-rendered show table, filter controls, per-user scope/page-size preferences, pagination, and bulk form.
- `admin/settings.php` — Existing settings form, inline help, format examples, and hidden preservation fields.
- `admin/handlers.php` — Existing field error collection, CRUD behavior, nonce checks, and readiness guards.
- `scripts/gigpress-admin.js` — Existing jQuery show/hide behavior for multi-day and optional controls.
- `css/gigpress-admin.css` — Existing admin styles to adapt alongside standard WordPress classes.
- `tests/compat/` — Existing OrbStack-backed real-WordPress fixture and workflow harness for subsequent planning/execution validation.

### Established Patterns

- Administration uses procedural PHP and server-rendered WordPress forms/tables. Reuse translation functions, standard admin styling, and existing settings registration.
- Scope and page size currently use `gigpress_scope` and `gigpress_limit` user metadata. Artist/tour/venue filters are request values; preserve the agreed navigation state without creating additional saved-preference semantics.
- Time uses existing optional sentinels and honors `alternate_clock`. Change presentation without redefining stored time values or clock behavior.
- Settings save through the existing WordPress options flow. Moving fields must preserve submitted values, hidden settings, and unknown options.
- Phase 02 protects record identity, relationships, and unsafe upgrade states. UI changes and confirmation requests must continue through those protections.

### Integration Points

- Coordinate date/end-date controls and submitted-value recovery between `admin/new.php`, `admin/handlers.php`, and the admin script.
- Coordinate filter/pagination URLs, selection, confirmations, and bulk outcome messages between `admin/shows.php` and existing handlers.
- Group settings within `admin/settings.php`; preserve the existing option registration/sanitization behavior in `gigpress.php` and handlers.
- Update labels, table headings, help associations, and feedback consistently across the changed form/list/settings surfaces.

</code_context>

<specifics>
## Specific Ideas

- Agreed labels: “Time (optional),” “Not specified,” “End date — last day of the event,” “Reset filters,” and “Jump to section.”
- Example bulk feedback: “3 shows moved to trash; 1 could not be changed,” followed by applicable failure explanations.
- Empty filtered list: “No shows match these filters,” with the agreed Reset filters action.
- All four presented areas were selected (`1-4`); the user chose option 1 individually for every substantive decision and explicitly approved creating the context.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope. Existing public-publishing and CSV migration integration checks remain assigned to Phases 04/05 in the roadmap.

</deferred>

---

*Phase: 03-administration-workflows*
*Context gathered: 2026-10-04*
