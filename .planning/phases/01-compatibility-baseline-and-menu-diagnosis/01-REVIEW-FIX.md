---
phase: 01
fixed_at: 2026-10-13T08:32:36Z
review_path: /Users/davidzenz/gigpress/.planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-REVIEW.md
iteration: 2
findings_in_scope: 5
fixed: 5
skipped: 0
status: all_fixed
---

# Phase 01: Code Review Fix Report

**Fixed at:** 2026-10-13T08:32:36Z
**Source review:** `/Users/davidzenz/gigpress/.planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-REVIEW.md`
**Iteration:** 1

**Summary:**

- Findings in scope across both review-fix iterations: 5
- Fixed: 5
- Skipped: 0

## Fixed Issues

### Iteration 1 — 2026-10-04

### WR-01: Real-plugin recovery is never probed

**Files modified:** `tests/compat/run.sh`
**Commit:** 22a20dd
**Applied fix:** The supported-PHP recovery now runs the real GigPress recovery probe, which verifies active state, normal hooks and functions, and the data snapshot before its state is compared with activation and diagnostic results.

### WR-02: An empty compatibility matrix exits successfully

**Files modified:** `tests/compat/run.sh`
**Commit:** 13350c2
**Applied fix:** The runner requires effective WordPress and PHP inputs, materializes and validates normalized matrix pairs before looping, and self-tests missing and malformed matrix inputs.

### WR-03: Menu-contract accepts a case list that runs no cases

**Files modified:** `tests/compat/run.sh`
**Commit:** 7d86783
**Applied fix:** The menu contract rejects empty or blank comma-separated case entries before starting the container, and its PHP harness rejects any normalized empty or malformed case list.

### Iteration 2 — 2026-10-13

#### CR-01: CSV-imported artist names execute in the Artists administration page

**Files modified:** `admin/artists.php`, `tests/compat/csv-import.php`
**Commit:** `47285be`
**Applied fix:** Escape the texturized artist name at the Artists table output sink while retaining its raw stored value. The regression imports an image/event-handler payload, renders the real admin table, and verifies the escaped label appears without an active image element.

#### WR-01: Import accepts values that are not URLs

**Files modified:** `admin/csv.php`, `tests/compat/csv-import.php`
**Commit:** `47285be`
**Applied fix:** Reject nonempty Artist URL, Venue URL, Ticket URL, and External URL values unless they are absolute HTTP or HTTPS URLs accepted unchanged by `esc_url_raw()`. Rejections identify the specific column before persistence. Valid URL strings remain byte-for-byte unchanged in storage.

## Verification

Verification ran in the isolated worktree `/Users/davidzenz/gigpress/.claude/worktrees/rf-01-22547-1791099108`.

- Re-read each modified runner section after its change.
- `rtk bash -n tests/compat/run.sh` passed after every finding.
- `rtk bash tests/compat/run.sh self-test` passed after WR-02 and WR-03; it includes the new matrix and menu-case rejection assertions.
- `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario csv-workflows --case import-rows` passed with 16 assertions, including both findings' regressions.
- `rtk proxy bash tests/compat/run.sh csv-evidence --action build --report .planning/phases/05-csv-import-export/05-CSV-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3` passed with 54 rows across six runtime cells and nine cases per cell.
- The CSV evidence validator and self-test passed; all 12 corruption classes were rejected.

---

_Fixed: 2026-10-13T08:32:36Z_
_Fixer: Codex_
_Iteration: 2_
