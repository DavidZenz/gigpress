---
phase: 05
review: 05-REVIEW.md
titles: json
findings:
  - id: CR-01
    severity: critical
    disposition: fixed
    title: "CSV validation permits values the schema cannot preserve"
  - id: CR-02
    severity: critical
    disposition: fixed
    title: "Configured GigPress managers cannot export"
  - id: CR-03
    severity: critical
    disposition: fixed
    title: "Conversion deletes sources after failed verification queries"
  - id: CR-04
    severity: critical
    disposition: fixed
    title: "Concurrent confirmations can create duplicate conversion targets"
  - id: WR-01
    severity: warning
    disposition: fixed
    title: "Full-workflows probe simulates an upload that the importer must reject"
  - id: R2-CR-01
    severity: critical
    disposition: fixed
    title: "Failed exact-name artist lookup can create a duplicate conversion target"
  - id: R2-CR-02
    severity: critical
    disposition: fixed
    title: "Source-tour verification failure needs transactional rollback"
  - id: R2-WR-01
    severity: warning
    disposition: fixed
    title: "Missing capability setting must retain the edit_posts default"
  - id: R3-CR-01
    severity: critical
    disposition: fixed
    title: "Nontransactional tours storage cannot guarantee source deletion recovery"
open: 0
total: 9
recorded: "2026-10-12T21:30:00Z"
---

# Phase 05: Code Review Disposition

| Finding | Severity | Disposition | Source |
|---------|----------|-------------|--------|
| CR-01 | Critical | fixed | `admin/csv.php`; `csv-workflows/import-rows` across six runtimes |
| CR-02 | Critical | fixed | `admin/csv-export.php`; `csv-workflows/export-filters` across six runtimes |
| CR-03 | Critical | fixed | `admin/tour-conversion.php`; failed verification-read retention assertions across six runtimes |
| CR-04 | Critical | fixed | `admin/tour-conversion.php`; serialized reentrant-confirmation assertion across six runtimes |
| WR-01 | Warning | fixed | `tests/compat/browser-bootstrap.php`, `tests/compat/probe.php`, and `tests/compat/run.sh`; authenticated multipart full-workflow probe passes |
| R2-CR-01 | Critical | fixed | `admin/tour-conversion.php`; injected target-lookup error leaves tables unchanged and retry creates one target |
| R2-CR-02 | Critical | fixed | `admin/tour-conversion.php`; post-delete read failure and source-ID collision trigger transaction rollback |
| R2-WR-01 | Warning | fixed | `admin/csv-export.php`, `admin/csv.php`, and `admin/tour-conversion.php`; editor without saved capability can import/export/preview |
| R3-CR-01 | Critical | fixed | `admin/tour-conversion.php`; MyISAM tours-table conversion is rejected before show/artist/tour changes |

The latest standard code review at `05-REVIEW.md` is clean with zero remaining findings. Follow-up IDs preserve the earlier review rounds whose finding numbers were reused by each fresh report.

Dispositions: `open` (recorded, not yet triaged), `fixed`, `skipped`, `deferred`.
