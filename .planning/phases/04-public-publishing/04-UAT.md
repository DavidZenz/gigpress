---
status: complete
phase: 04-public-publishing
source: [04-01-SUMMARY.md, 04-02-SUMMARY.md, 04-03-SUMMARY.md, 04-04-SUMMARY.md, 04-05-SUMMARY.md]
started: 2026-10-06T12:03:57+02:00
updated: 2026-10-06T12:24:29+02:00
---

## Current Test
<!-- OVERWRITE each test - shows where we are -->

[testing complete]

## Tests

### 1. Publish a migrated show through established public destinations
expected: The reconstructed migrated show appears through the shortcode, legacy wrapper, widget, related-post, RSS, iCalendar, and child-theme override while repeated reads preserve its stored snapshot.
result: pass
source: automated
coverage_id: D1

### 2. Preserve public records across recognized legacy and current states
expected: Each recognized migration source and populated current state preserves its independent manifest and publishes the expected records and feeds.
result: pass
source: automated
coverage_id: D2

### 3. Exercise Unicode, hostile values, and public boundary cases
expected: The supplemental fixture includes Unicode, hostile text and URLs, status, grouping, country, and date/time boundaries that later public cases exercise.
result: pass
source: user_reported
evidence: .planning/phases/04-public-publishing/04-BROWSER.md#listing-at-320-css-pixels
coverage_id: D3

### 4. Use visible calendar links without JavaScript
expected: The bundled first-show calendar actions are visible beside date/time and remain usable without JavaScript.
result: pass
source: user_reported
evidence: .planning/phases/04-public-publishing/04-BROWSER.md#keyboard-with-javascript-blocked
coverage_id: D4

### 5. Read the final bundled listing at narrow and wide widths
expected: The wide table remains available, and the narrow listing shows date-first labelled rows with show details and actions visible.
result: pass
source: user_reported
evidence: .planning/phases/04-public-publishing/04-BROWSER.md#listing-at-320-css-pixels
coverage_id: D1

### 6. Preserve compact public surfaces and their theme inheritance
expected: Compact subscriptions, widgets, and related-show output preserve existing filters, endpoints, and theme inheritance.
result: pass
source: automated
coverage_id: D2

### 7. Preserve complete and mixed template override ownership
expected: Child, parent, wp-content, and bundled overrides retain their established priority and owner-controlled responsive styling.
result: pass
source: automated
coverage_id: D3

### 8. Review the theme-owner adoption guidance
expected: Theme owners can find the exact filenames, variables, hooks, responsive marker, and mixed-override guidance needed to opt in.
result: pass
source: user_reported
evidence: .planning/phases/04-public-publishing/04-BROWSER.md#template-override-ownership
coverage_id: D4

### 9. Preserve compatible listing fragments and safe JSON-LD
expected: Main and grouped listings keep compatible fragments, and plain JSON-LD values parse correctly without allowing hostile input to close the script element.
result: pass
source: automated
coverage_id: D1

### 10. Keep intended links and inert hostile values across related surfaces
expected: Related, widget, subscription, and bundled outputs retain intended links, classes, rich notes, and inert hostile values without changing stored snapshots.
result: pass
source: automated
coverage_id: D2

### 11. Confirm encoded public output and link behavior
expected: Main and grouped listings retain their fragments and emit safe, parseable JSON-LD. Related-show, widget, subscription, and bundled output preserve their intended links, classes, and rich notes; hostile values remain inert and reads leave stored snapshots unchanged.
result: pass
source: user_reported
coverage_ids: [D1, D2]

### 12. Serialize RSS values and empty results correctly
expected: Anonymous RSS responses parse to exact channel and event values for hostile text, rich notes, filters, limits, repeated reads, and empty results without changing migrated storage.
result: pass
source: automated
coverage_id: D1

### 13. Preserve iCalendar event identity and date/time meanings
expected: Anonymous iCalendar responses preserve event identity and filters, with valid CRLF framing, property types, UTF-8 folds, hostile text, optional time, midnight, multi-day ranges, repeated reads, and empty calendars.
result: pass
source: automated
coverage_id: D2

### 14. Pass the supported public compatibility matrix
expected: All nine public cases pass on WordPress 7.0.6 and 7.1.2 with PHP 8.3, 8.4, and 8.5, and the source-bound evidence validates its named corruption checks.
result: pass
source: automated
coverage_id: D3

### 15. Accept final-source browser, keyboard, and calendar behavior
expected: The final-source responsive listing, theme inheritance, keyboard navigation with JavaScript disabled, and calendar-client behavior match the recorded acceptance criteria.
result: pass
source: user_reported
evidence: .planning/phases/04-public-publishing/04-BROWSER.md
coverage_id: D4

### 16. Pass the final-source matrix and evidence validation
expected: All nine public contracts pass on the six supported WordPress/PHP cells, and the source-bound evidence report validates and rejects its 22 named corruptions.
result: pass
source: automated
coverage_id: D1

### 17. Read final listings, compact surfaces, and template overrides
expected: The final-source listing, compact surfaces, theme inheritance, and complete/mixed overrides remain readable and owner-controlled.
result: pass
source: user_reported
evidence: .planning/phases/04-public-publishing/04-BROWSER.md#compact-surfaces-and-theme-styling
coverage_id: D2

### 18. Use public links with JavaScript blocked
expected: Ticket, Google Calendar, iCalendar, RSS, and webcal links remain keyboard-usable when browser JavaScript is disabled.
result: pass
source: user_reported
evidence: .planning/phases/04-public-publishing/04-BROWSER.md#keyboard-with-javascript-blocked
coverage_id: D3

### 19. Import calendar events with their intended date meanings
expected: Apple Calendar preserves no-time, actual-midnight, and all-day multi-day event meanings after import.
result: pass
source: user_reported
evidence: .planning/phases/04-public-publishing/04-BROWSER.md#calendar-client-import
coverage_id: D4

### 20. Recheck and clean up the approved public fixture
expected: The final-source public fixture passes all public checks with unchanged snapshots and leaves no owned containers or volumes.
result: pass
source: automated
coverage_id: D5

## Summary

total: 20
passed: 20
issues: 0
pending: 0
skipped: 0
blocked: 0

## Gaps

<!-- none yet -->
