# Phase 03: Administration Workflows - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md; this log preserves alternatives considered.

**Date:** 2026-10-04
**Phase:** 03-Administration Workflows
**Areas discussed:** Date/time entry, Filters and bulk actions, Settings organization, Errors and accessibility

## Area selection

Presented date/time entry, filters and bulk actions, settings organization, and errors/accessibility. The user replied `1-4`, selecting all four. Discussion used plain-text numbered questions in Default mode. No prior approval of recommendations for another phase was treated as approval here.

## Date/time entry

### For date and time entry, which approach do you prefer?

| Option | Description | Selected |
|--------|-------------|----------|
| 1. Date picker + clearer time dropdowns (Recommended) | Easier date selection; optional time respects configured 12/24-hour format. | ✓ |
| 2. Improve existing dropdowns | Keep month/day/year and time selectors, with clearer labels and help. |  |
| 3. Date and time pickers | Browser controls for both; display follows browser locale. |  |

**User's reply:** `1`
**Recorded choice:** 1 — Date picker + clearer time dropdowns; time stays optional and respects the configured 12/24-hour format.

### How should the form handle an unspecified show time?

| Option | Description | Selected |
|--------|-------------|----------|
| 1. Time (optional) with Not specified (Recommended) | Keep dropdowns visible; enable minutes once an hour is selected. | ✓ |
| 2. Specify a time checkbox | Reveal time dropdowns when checked. |  |

**User's reply:** `1`
**Recorded choice:** 1 — Time (optional) with Not specified; keep dropdowns visible, enable minutes once an hour is selected; preserve existing no-time meaning.

### How should multi-day shows work in the form?

| Option | Description | Selected |
|--------|-------------|----------|
| 1. Keep the multi-day checkbox (Recommended) | Checking reveals End date — last day of the event, with expiration help. | ✓ |
| 2. Always show an optional end date | Entering an optional end date makes the show a multi-day event. |  |

**User's reply:** `1`
**Recorded choice:** 1 — Keep multi-day checkbox; reveal End date — last day of the event, explain when the show leaves upcoming list; retain expiration behavior.

### When a date or time is invalid, how should feedback appear?

| Option | Description | Selected |
|--------|-------------|----------|
| 1. Field messages + error summary (Recommended) | Explain each problem beside its field and link to those fields from a summary; preserve all entered values. | ✓ |
| 2. Field messages only | Show messages beside fields and focus the first error; preserve all entered values. |  |

**User's reply:** `1`
**Recorded choice:** 1 — Field messages + linked error summary; preserve all entered values.

### Continue with date/time details, or move on?

| Option | Description | Selected |
|--------|-------------|----------|
| 1. More date/time questions | Continue discussing date/time details. |  |
| 2. Next area: filters and bulk actions | Move to filters and bulk actions; settings and errors/accessibility remain. | ✓ |

**User's reply:** `2`
**Recorded choice:** 2 — Next area: filters and bulk actions

---

## Filters and bulk actions

### When you return to the show list, which choices should it remember?

| Option | Description | Selected |
|--------|-------------|----------|
| 1. Scope and page size (Recommended) | Remember scope/page size between visits; preserve every filter during filtering, sorting, and pagination. | ✓ |
| 2. All choices, including artist, tour, venue and sort order between visits | Also remember artist, tour, venue, and sort order between visits. |  |

**User's reply:** `1`
**Recorded choice:** 1 — Remember scope and page size between visits; preserve every filter during filtering, sorting, and pagination.

### What should a Reset filters button do?

| Option | Description | Selected |
|--------|-------------|----------|
| 1. Clear artist, tour, and venue (Recommended) | Keep current scope, sort, and page size; return to page 1. | ✓ |
| 2. Reset entire view to upcoming, default sort and 25 rows; both return to page 1 | Upcoming shows, default sort, 25 rows per page; return to page 1. |  |

**User's reply:** `1`
**Recorded choice:** 1 — Clear artist, tour and venue; keep scope, sort order and page size; return to page 1.

### Before a bulk action moves selected shows to trash, what should happen?

| Option | Description | Selected |
|--------|-------------|----------|
| 1. Confirm with the selected count (Recommended) | Show action and selected count, with Confirm or Cancel; affect only explicitly selected shows. | ✓ |
| 2. Apply immediately and show result | Move selected shows to trash immediately, then show result. |  |

**User's reply:** `1`
**Recorded choice:** 1 — Confirm with explicitly selected count; show action and offer Confirm or Cancel; only selected shows affected.

### How should bulk results appear?

| Option | Description | Selected |
|--------|-------------|----------|
| 1. Counts and actionable details (Recommended) | Report counts, including an explanation for each failure; retain filters and sort. | ✓ |
| 2. Short overall notice; both keep filters and sort | Simple overall success/failure notice; retain filters and sort. |  |

**User's reply:** `1`
**Recorded choice:** 1 — Counts and actionable details, including explanation for each failure; preserve current filters and sort.

### More filter/bulk questions, or next area?

| Option | Description | Selected |
|--------|-------------|----------|
| 1. More filter/bulk questions | Continue filter/bulk discussion. |  |
| 2. Next area: settings organization | Move to settings organization; errors/accessibility remain. | ✓ |

**User's reply:** `2`
**Recorded choice:** 2 — Next area: settings organization

---

## Settings organization

### How should settings be organized?

| Option | Description | Selected |
|--------|-------------|----------|
| 1. Sections on one page (Recommended) | Clear headings and Jump to section links, with one Save changes action. | ✓ |
| 2. Tabs by topic; one Save changes action for all groups | One group at a time; one Save changes action for all groups. |  |

**User's reply:** `1`
**Recorded choice:** 1 — Sections on one page, clear headings, Jump to section links, one Save changes action.

### Which section grouping fits best?

| Option | Description | Selected |
|--------|-------------|----------|
| 1. Six focused groups (Recommended) | Display & formatting; Show labels & links; Related posts; Feeds; Permissions; Advanced. | ✓ |
| 2. Four broader groups: General; Shows (including feeds); Related posts; Advanced | General; Shows; Related posts; Advanced, with feeds under Shows. |  |

**User's reply:** `1`
**Recorded choice:** 1 — Six focused groups: Display & formatting; Show labels & links; Related posts; Feeds; Permissions; Advanced.

### How much help should settings show?

| Option | Description | Selected |
|--------|-------------|----------|
| 1. Short inline explanations (Recommended) | One or two sentences where needed, format examples, and links to longer guidance. | ✓ |
| 2. Expandable More help controls | Brief labels with explanations revealed through More help controls. |  |

**User's reply:** `1`
**Recorded choice:** 1 — Short inline explanations, one or two sentences where needed, examples for formats and links to longer guidance.

### Should the Advanced section be visible immediately?

| Option | Description | Selected |
|--------|-------------|----------|
| 1. Keep it visible (Recommended) | Same layout as other sections, with a jump link. | ✓ |
| 2. Collapse initially; expand Advanced settings | Expand Advanced settings when needed. |  |

**User's reply:** `1`
**Recorded choice:** 1 — Advanced visible with same layout as other sections and jump link.

### More settings questions, or next area?

| Option | Description | Selected |
|--------|-------------|----------|
| 1. More settings questions | Continue settings discussion. |  |
| 2. Next area: errors and accessibility | Move to errors and accessibility. | ✓ |

**User's reply:** `2`
**Recorded choice:** 2 — Next area: errors and accessibility

---

## Errors and accessibility

### After successfully adding a show, where should the user go?

| Option | Description | Selected |
|--------|-------------|----------|
| 1. Stay on the add-show screen (Recommended) | Clear success message; links to edit saved show or view list; prepare form for another show. | ✓ |
| 2. Return to show list with success message above list | Success message above the show list. |  |

**User's reply:** `1`
**Recorded choice:** 1 — Stay on add-show screen; clear success message, links to edit saved show or view list, prepare form for another show.

### If saving is blocked by a system problem, what should the message show?

| Option | Description | Selected |
|--------|-------------|----------|
| 1. Reason and next step (Recommended) | Plain-language explanation with next action available in existing workflow; preserve inputs. | ✓ |
| 2. Short message with support reference | Short failure message with a troubleshooting reference; preserve inputs. |  |

**User's reply:** `1`
**Recorded choice:** 1 — Plain-language reason and next step available in existing workflow; preserve entered values for correction or retry.

### Should moving an individual show to trash require confirmation too?

| Option | Description | Selected |
|--------|-------------|----------|
| 1. Yes (Recommended) | Identify show and offer Confirm or Cancel, consistent with bulk trash actions. | ✓ |
| 2. No; apply immediately with clear result | Apply immediately and show clear result. |  |

**User's reply:** `1`
**Recorded choice:** 1 — Identify show and offer Confirm or Cancel, consistent with bulk trash actions.

### When filters produce no matching shows, what should the list display?

| Option | Description | Selected |
|--------|-------------|----------|
| 1. Explain and offer recovery (Recommended) | No shows match these filters, with Reset filters following agreed behavior. | ✓ |
| 2. Simple No shows found message with filter controls available | No shows found, with filter controls available. |  |

**User's reply:** `1`
**Recorded choice:** 1 — No shows match these filters, with Reset filters link following agreed reset behavior.

### More feedback/accessibility questions, or finish this area?

| Option | Description | Selected |
|--------|-------------|----------|
| 1. More feedback/accessibility questions | Continue feedback/accessibility discussion. |  |
| 2. Finish this area | Finish the final selected area. | ✓ |

**User's reply:** `2`
**Recorded choice:** 2 — Finish this area

---

## Final context gate

After all four areas, the user was offered:

| Option | Description | Selected |
|--------|-------------|----------|
| 1. Explore more gray areas | Identify additional unresolved decisions. | |
| 2. Create Phase 03 context | Capture the completed discussion for planning. | ✓ |

**User's reply:** `2`

## Agent's Discretion

No substantive area was delegated. All sixteen substantive questions were answered individually with option 1. Routine implementation details remain open within the captured requirements and choices.

## Deferred Ideas

None raised. Public publishing and CSV migration checks remain in their existing later phases.
