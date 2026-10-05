---
status: testing
phase: 03-administration-workflows
source: [03-VERIFICATION.md]
started: 2026-10-05T07:13:27.246474+00:00
updated: 2026-10-05T07:13:27.246474+00:00
---

## Current Test

number: 1
name: Genuinely disabled-JavaScript entry and list
expected: |
  Date correction/multi-day and single/bulk Confirm/Cancel work, preserve actual received state and change only confirmed selected shows.
awaiting: user response

## Tests

### 1. Genuinely disabled-JavaScript entry and list

expected: Date correction/multi-day and single/bulk Confirm/Cancel work, preserve actual received state and change only confirmed selected shows.

Why human: Available browser automation has no script-disable capability; PHP/HTTP requests are not a disabled-script browser.

result: [pending]

### 2. Complete keyboard traversal, focus and readable feedback

expected: Tab/Space/Enter reach and operate all changed controls, including filter/pagination/Reset; automatic notices and summary links focus usable targets with visible focus.

Why human: Only the documented subset of actual keyboard paths passed; full navigation, automatic notice focus and visual feedback quality remain unobserved.

result: [pending]

### 3. Complete recovery and mixed-outcome browser sequences

expected: New artist/venue/tour/post choices, related-date radio and notes survive rejection; no-time differs from midnight; intervening selected-row changes yield truthful mixed results.

Why human: Real WordPress tests prove data transitions but cannot establish these complete browser interactions.

result: [pending]

### 4. Authorized artist sorting feedback and repeated unchanged order

expected: Sorting shows truthful text success/error feedback; repeated unchanged order remains successful and omitted artists retain their order.

Why human: Actual AJAX authority, JSON and exact subset read-back pass; interactive sortable feedback remains unobserved.

result: [pending]

### 5. Resolve the date/expiration product prohibition

expected: A human explicitly accepts that existing upcoming cutoff semantics and optional event time are preserved.

Why human: The PLAN projection contains an unresolved statement without a verification descriptor; related tests do not resolve product intent.

result: [pending]

### 6. Resolve the selected-set/Cancel product prohibition

expected: A human explicitly accepts that confirmation represents the selected IDs and Cancel communicates no continuation of trash.

Why human: The PLAN projection is descriptor-less and unresolved; data assertions do not settle interpretation of the product wording.

result: [pending]

### 7. Resolve the Advanced/unchanged-save product prohibition

expected: A human explicitly accepts immediate Advanced visibility and preservation of site configuration during unchanged saves.

Why human: Concrete saves and visibility pass, including the legacy-control browser regression; the unresolved descriptor-less product-intent item still needs explicit resolution.

result: [pending]

### 8. Clarify ADMIN-03/unclassified acceptance intent

expected: Identify the missing settings edge and its acceptance check, or explicitly resolve its scope; then record the relevant result.

Why human: The supplied edge is unidentified; no truth, test descriptor or acceptance decision can be safely inferred from the preservation tests.

result: [pending]

## Summary

total: 8
passed: 0
issues: 0
pending: 8
skipped: 0
blocked: 0

## Gaps
