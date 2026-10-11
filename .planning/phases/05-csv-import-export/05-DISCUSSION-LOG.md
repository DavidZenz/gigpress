# Phase 05: CSV Import/Export - Discussion Log

> **Audit trail only.** Do not use as input to planning, research or execution agents.
> Decisions are captured in `05-CONTEXT.md`; this log preserves the alternatives considered.

**Date:** 2026-10-11
**Phase:** 05-CSV Import/Export
**Areas discussed:** Import/export screen, Import results and retry, Export behavior, Legacy records and conversion

**Area selection:** User replied `1-4` to select all four areas.

---

## Import/export screen

### 1. How should the two tasks be arranged?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | One page, stacked sections (Recommended) | ✓ |
| 2 | Separate Import and Export tabs |  |
| 3 | Side by side on wide screens |  |

**User’s choice:** `1` — One page, stacked sections; Import first, Export below, both immediately visible.

**Notes:** Explicit individual selection; no wholesale delegation.

### 2. How should CSV formatting guidance appear?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Short instructions plus expandable column reference (Recommended) | ✓ |
| 2 | Full reference always visible |  |
| 3 | Short instructions with a documentation link |  |

**User’s choice:** `1` — Essential instructions stay visible beside the upload control, with an expandable column reference covering names, formats and examples.

**Notes:** Explicit individual selection; no wholesale delegation.

### 3. How should Include related-post associations be presented?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Visible checkbox, unchecked by default (Recommended) | ✓ |
| 2 | Checkbox inside an expandable Advanced section |  |
| 3 | Explicit Yes/No choice |  |

**User’s choice:** `1` — Visible checkbox, unchecked by default, explaining that post IDs must match posts on the destination site.

**Notes:** Explicit individual selection; no wholesale delegation.

### 4. What should the action buttons say?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Import shows and Download CSV (Recommended) | ✓ |
| 2 | Upload CSV and Download CSV |  |
| 3 | Import and Export |  |

**User’s choice:** `1` — Import shows and Download CSV.

**Notes:** Explicit individual selection; no wholesale delegation.

**Area completion:** User chose `2` to advance after the four questions.

---

## Import results and retry

### 1. How should the import outcome be displayed?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Summary counts with expandable row details (Recommended) | ✓ |
| 2 | Summary plus every row immediately visible |  |
| 3 | Summary with problem rows visible |  |

**User’s choice:** `1` — Summary counts for imported, duplicate, rejected and failed rows, with expandable groups showing row numbers and reasons.

**Notes:** Explicit individual selection; no wholesale delegation.

### 2. What should rejected or failed row details contain?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Row number, show identity, affected field, and correction guidance (Recommended) | ✓ |
| 2 | Row number, show identity, and a short reason |  |
| 3 | All submitted fields plus the reason |  |

**User’s choice:** `1` — Row number, show identity, affected field and correction guidance, such as Row 8: invalid Date; use YYYY-MM-DD.

**Notes:** Explicit individual selection; no wholesale delegation.

### 3. After an import, where should the owner land?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Stay on Import/Export (Recommended) | ✓ |
| 2 | Open the show list after complete success |  |
| 3 | Open the show list after any successful rows |  |

**User’s choice:** `1` — Stay on Import/Export with the results visible, a show-list link and another upload available.

**Notes:** Explicit individual selection; no wholesale delegation.

### 4. What retry guidance should appear when some rows fail?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Correct the original file and upload it again (Recommended) | ✓ |
| 2 | Upload only the corrected problem rows |  |
| 3 | Describe both approaches equally |  |

**User’s choice:** `1` — Correct the original file and upload it again; explain that existing matching shows are skipped as duplicates and are not overwritten.

**Notes:** Explicit individual selection; no wholesale delegation.

**Area completion:** User chose `2` to advance after the four questions.

---

## Export behavior

### 1. What should appear beside Download CSV before downloading?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Selected filters and matching show count (Recommended) | ✓ |
| 2 | Selected filters only |  |
| 3 | Labelled filter controls only |  |

**User’s choice:** `1` — Selected artist, tour and date-scope filters plus matching show count.

**Notes:** Explicit individual selection; no wholesale delegation.

### 2. When no shows match, what should happen?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Show the empty-result explanation with Reset filters, no download (Recommended) | ✓ |
| 2 | Download a CSV containing only the column headers |  |
| 3 | Disable Download CSV and show the empty-result explanation |  |

**User’s choice:** `1` — Stay on the page, show No shows match these export filters with a Reset filters action, and produce no download.

**Notes:** Explicit individual selection; no wholesale delegation.

### 3. How long should export filter choices be retained?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | For the current visit only (Recommended) | ✓ |
| 2 | Remember them between visits for each user |  |
| 3 | Reset after each download |  |

**User’s choice:** `1` — Current visit only; keep choices after downloads or errors and use existing defaults on later visits.

**Notes:** Explicit individual selection; no wholesale delegation.

### 4. How should spreadsheet guidance appear?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Short visible note plus expandable steps (Recommended) | ✓ |
| 2 | Full instructions always visible |  |
| 3 | Documentation link only |  |

**User’s choice:** `1` — Short visible note plus expandable steps explaining how to import values as text and preserve them when editing for re-import.

**Notes:** Explicit individual selection; no wholesale delegation.

**Area completion:** User chose `2` to advance after the four questions.

---

## Legacy records and conversion

### 1. How should existing records with status 0 be handled?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Preserve them and flag them for manual correction (Recommended) | ✓ |
| 2 | Convert every status 0 to Active |  |
| 3 | Block CSV export when affected records are present |  |

**User’s choice:** `1` — Preserve them and flag affected shows for manual correction; do not guess Active, Cancelled or Sold out.

**Notes:** Explicit individual selection; no wholesale delegation.

### 2. Before converting tours into artists, what should the confirmation show?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Affected tour names and show counts (Recommended) | ✓ |
| 2 | A concise total count |  |

**User’s choice:** `1` — Affected tour names and show counts, explaining reassignment and removal of converted tours, with Confirm and Cancel.

**Notes:** Explicit individual selection; no wholesale delegation.

### 3. If a tour name already matches an existing artist, what should conversion do?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Reuse a single exact name match (Recommended) | ✓ |
| 2 | Create a separate artist |  |
| 3 | Leave matching tours unchanged |  |

**User’s choice:** `1` — Reuse a single exact name match and preserve that artist details; leave the tour unchanged and report ambiguity when several artists match.

**Notes:** Explicit individual selection; no wholesale delegation.

### 4. If one tour fails to convert, how should the batch proceed?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Continue with the other tours (Recommended) | ✓ |
| 2 | Stop at the first failure |  |

**User’s choice:** `1` — Continue other independent tours, report each outcome, preserve failed relationships and support safe retries without duplicating completed work. Remove a source tour only after all its show reassignments are verified.

**Notes:** Explicit individual selection; no wholesale delegation.

**Area completion:** User chose `2` to advance after the four questions.

---

## Context approval

The final summary offered:

1. Explore more gray areas
2. Create Phase 05 context with these decisions

**User’s choice:** `2` — create context.

## Agent’s Discretion

No discussion area was delegated wholesale. Exact copy, spacing, accessible disclosures, request-state mechanics, fixture organization, safe encoding and recovery bookkeeping remain implementation details, constrained by `05-CONTEXT.md`.

## Research shown and consulted

- https://support.microsoft.com/en-us/excel/get-started/import-or-export-text-txt-or-csv-files — Shown during export discussion: direct CSV opening can reinterpret dates and numbers; import controls allow column formats.
- https://community.owasp.org/attacks/CSV_Injection — Research background for spreadsheet formula handling; no export transformation policy selected in this discussion.

No new export format or value-transformation policy was selected.

## Deferred Ideas

None introduced during this discussion.
