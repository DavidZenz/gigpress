# Phase 04: Public Publishing - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents. Decisions are captured in 04-CONTEXT.md; this log records the alternatives considered.

**Date:** 2026-10-05
**Phase:** 04-public-publishing
**Areas discussed:** Phone layout, Details and status, Calendar links, Theme styling

The user selected all four areas with `1-4`. Discussion used numbered plain-text questions in Default mode. After each area the user selected `2` to move on; after the final summary they selected `2` to create context.

## Phone layout

### 1. How should the main show listing appear on narrow screens?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Stack each show into a labelled block (recommended) | ✓ |
| 2 | Keep a compact table with wrapped text |  |
| 3 | Scroll the table within its container |  |

**User’s choice:** Stack each show into a labelled block: date first, then artist, location and details. Keep the table on wider screens.

### 2. Within each stacked show, how should additional information appear?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Show all available details immediately (recommended) | ✓ |
| 2 | Use Show details to expand them |  |
| 3 | Keep time/tickets visible and expand remaining details |  |

**User’s choice:** Show all available details immediately: time, admission, address, notes and links wrap below the main information.

### 3. For listings grouped by artist or tour, how should the phone layout show context?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Keep group heading above shows (recommended) | ✓ |
| 2 | Keep heading and repeat artist/tour in each block |  |

**User’s choice:** Keep the group heading above its shows, preserving current grouping and order.

### 4. How should stacked shows be visually separated?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Subtle dividers and comfortable spacing (recommended) | ✓ |
| 2 | Individual bordered cards |  |
| 3 | Dense list with minimal spacing |  |

**User’s choice:** Subtle dividers and comfortable spacing, fitting the existing listing.

## Details and status

### 1. How should cancelled and sold-out statuses appear?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Clear text with subtle emphasis (recommended) | ✓ |
| 2 | Prominent status badges | ✓ |

**User’s choice:** Combine clear Cancelled/Sold out text with prominent badges; retain fully readable show details and existing visibility/ticket rules.

**Clarification:** User answered 1-2, then explicitly selected 1 to confirm the combined interpretation. The clarification offered 1 = combine and 2 = subtle option; the user selected 1.

### 2. When a show has no start time, what should visitors see?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Omit time line (recommended) | ✓ |
| 2 | Show Time not specified |  |

**User’s choice:** Omit the time line, preserving current public display.

### 3. How should a multi-day event display its dates?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | One date range (recommended) | ✓ |
| 2 | Separate Starts and Ends labels |  |

**User’s choice:** One start – end date range using the saved date format.

### 4. How prominent should an available ticket link be?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Emphasized link (recommended) | ✓ |
| 2 | Button below details |  |
| 3 | Match other links |  |

**User’s choice:** A clearly labelled emphasized link within the show details; retain the saved ticket label and destination.

## Calendar links

### 1. How should visitors access existing Google Calendar and iCalendar links?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Show both directly (recommended) | ✓ |
| 2 | Add to calendar control with no-JS fallback |  |

**User’s choice:** Show both links directly, usable without JavaScript.

### 2. Which labels should the bundled listing use?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Descriptive action labels (recommended) | ✓ |
| 2 | Google Calendar and iCal |  |

**User’s choice:** Add to Google Calendar and Download iCalendar.

### 3. Where should each show calendar links appear?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | After show details (recommended) |  |
| 2 | Beside date and time | ✓ |

**User’s choice:** Beside the date and time, higher in the listing.

### 4. How should existing RSS and iCalendar subscription links below the listing appear?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Compact wrapping Subscribe group (recommended) | ✓ |
| 2 | Separate labelled lines |  |

**User’s choice:** Keep a compact Subscribe group that wraps; preserve settings and artist/tour/venue filtering.

## Theme styling

### 1. How should the bundled listing fit the site theme?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Inherit theme fonts/link colors (recommended) | ✓ |
| 2 | Neutral GigPress appearance |  |

**User’s choice:** Inherit theme fonts and link colors; add spacing, dividers and status treatment needed for clarity.

### 2. How should the new phone layout affect custom GigPress templates?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Bundled templates only (recommended) | ✓ |
| 2 | Adapt compatible custom templates using standard classes |  |

**User’s choice:** Apply new phone layout to bundled templates; custom layouts stay owner-controlled, with adoption guidance. Preserve template filenames, variables and override priority.

### 3. How wide should the bundled listing be?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Theme content width (recommended) | ✓ |
| 2 | GigPress maximum width |  |

**User’s choice:** Use the width provided by the theme content area, including narrower widget areas.

### 4. How much should widgets and related-show appearance change?

| Option | Description | Selected |
|--------|-------------|----------|
| 1 | Keep compact appearance (recommended) | ✓ |
| 2 | Resemble main stacked listing |  |

**User’s choice:** Keep compact appearance, improving wrapping and status readability where needed.

## Agent’s Discretion

No area was delegated wholesale. Normal implementation details are identified in 04-CONTEXT.md.

## Deferred Ideas

None introduced. Prior Phase 05 assignments remain on the roadmap.
