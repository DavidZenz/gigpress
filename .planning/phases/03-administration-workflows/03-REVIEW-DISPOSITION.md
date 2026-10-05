---
phase: 03
review: 03-REVIEW.md
titles: json
findings:
  - id: CR-01
    severity: critical
    disposition: fixed
    title: "Native settings controls lose or block unchanged legacy values"
  - id: CR-02
    severity: critical
    disposition: fixed
    title: "Artist ordering AJAX bypasses the configured administration capability"
  - id: CR-03
    severity: critical
    disposition: fixed
    title: "Feed discovery title permits stored script injection"
  - id: CR-04
    severity: critical
    disposition: deferred
    title: "CSV import notices execute raw imported markup"
  - id: CR-05
    severity: critical
    disposition: deferred
    title: "Tour conversion deletes source data after failed writes"
  - id: CR-06
    severity: critical
    disposition: fixed
    title: "Required new-entry fields can become empty after validation"
  - id: CR-07
    severity: critical
    disposition: deferred
    title: "CSV import converts every show status into zero"
  - id: WR-01
    severity: warning
    disposition: fixed
    title: "Recovery escaping checks do not bind values to their fields"
  - id: WR-02
    severity: warning
    disposition: fixed
    title: "Failed browser cleanup removes its own recovery metadata"
open: 0
total: 9
recorded: 2026-10-05T06:55:19.778Z
---

# Phase 03: Code Review Disposition

| Finding | Severity | Disposition | Source |
|---------|----------|-------------|--------|
| CR-01 | critical | fixed | Fixed dce65a5 after RED 2bd3377; exact legacy text fallback; settings-sections 175 PASS. Actual browser unchanged-legacy save remains a human check. |
| CR-02 | critical | fixed | Fixed dce65a5 after RED 2bd3377; POST/capability/action nonce/readiness, exact existing deduplicated IDs, constrained update and JSON. Settings-save 64 PASS; actual AJAX HTTP snapshots PASS. |
| CR-03 | critical | fixed | Fixed dce65a5 after RED 2bd3377; esc_attr title and esc_url feed discovery URL; hostile title markup check PASS. |
| CR-04 | critical | deferred | Deferred to Phase 05 CSV import notices; inherited workflow outside this administration slice. Required follow-up in .planning/CSV-REVIEW-FOLLOWUPS.md; remains unresolved. |
| CR-05 | critical | deferred | Deferred to Phase 05 conversion/import safety; inherited tour conversion outside the approved entry/list/settings rewrite. Required verified writes, retained relationships and truthful partial outcomes in .planning/CSV-REVIEW-FOLLOWUPS.md; remains unresolved. |
| CR-06 | critical | fixed | Fixed 346d3d0 after RED 44e612c; normalize required fields before side effects and retain raw recovery; 283 entry-recovery checks PASS. See 03-ENTRY-REVIEW-FIX.md. |
| CR-07 | critical | deferred | Deferred to Phase 05 CSV status contract; inherited format mismatch. Required %s/domain validation and explicit legacy zero-status repair decision in .planning/CSV-REVIEW-FOLLOWUPS.md; remains unresolved. |
| WR-01 | warning | fixed | Fixed 71d3578 after RED 3f4db2f; distinct hostile values and named-control escaping/read-back; corrupt-control negative checks PASS. |
| WR-02 | warning | fixed | Fixed 9b8e6eb after RED ec2f9e9; retain private session/logs when cleanup unconfirmed; inventory failure cannot pass; owned partial stop supported; 5 cleanup contract checks PASS. |

Dispositions: `open` (recorded, not yet triaged), `fixed`, `skipped`, `deferred`.
Set `deferred` by hand and put the reason in the Source cell; both are preserved. A `|` in the reason is kept as prose and escaped on the next run.
Re-running the gate keeps every row it can. A row the current review no longer reports is kept and its Source cell flagged, so a finding does not leave this record silently. ONE exception: when a finding id is REUSED by a different finding, the earlier decision cannot keep a row — the id is taken — and it is dropped. A RECORDED decision (anything but `open`) is named on the console when that happens; a row still at `open` is replaced silently, because `open` records no decision to lose.
