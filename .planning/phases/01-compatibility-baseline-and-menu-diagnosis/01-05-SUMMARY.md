---
phase: 01-compatibility-baseline-and-menu-diagnosis
plan: "05"
subsystem: compatibility
tags: [wordpress, php, orbstack, docker, metadata, regression]
requires:
  - phase: 01-04
    provides: stable warning-free menu ordering
provides:
  - complete WordPress 7.0.6/7.1.2 × PHP 8.3–8.5 workflow evidence
  - matrix-backed WordPress/PHP compatibility metadata
affects: [plugin-metadata, release-claims]
tech-stack:
  added: []
  patterns: [disposable full-workflow matrix, archive-core fallback]
key-files:
  created: [.planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-COMPATIBILITY-MATRIX.md]
  modified: [tests/compat/run.sh, tests/compat/probe.php, tests/compat/compose.yaml, templates/shows-list.php, output/feed.php]
key-decisions:
  - "Use the official WordPress 7.0.6 archive with an official PHP/Apache image base because Docker Hub has no matching 7.0.6 image tag."
  - "Keep Tested up to at 7.1 because every latest-patch cell in that major.minor line passed the full workflow matrix."
actuals:
  tokens: 7225
  tasks: 2
  commits: 3
commits: 3
plan_head_before: 73440c0079a325eef8d0cd0a9bac8b98302ae5dd
plan_head_after: 4af75bd
status: complete
---

# Phase 01 Plan 05: Full Compatibility Matrix Summary

**GigPress now has audited, full-workflow evidence for WordPress 7.0.6 and 7.1.2 on PHP 8.3 through 8.5, and its published metadata matches that evidence.**

## Accomplishments

- Ran every supported WordPress/PHP cell under `E_ALL`: admin create/edit/read, shortcode rendering, RSS, iCalendar, CSV round trip, duplicate handling, activation, and menu checks all passed without plugin warnings or fatals.
- Ran the PHP 8.3 → diagnostic 8.2 → PHP 8.3 runtime-floor lifecycle on both WordPress lines. The active plugin and data persist below the floor while only the authorized notice is available; normal operation recovers at PHP 8.3.
- Recorded exact WordPress releases, upstream sources, PHP runtime versions, official image IDs, result hashes, and the checked source revision in [01-COMPATIBILITY-MATRIX.md](./01-COMPATIBILITY-MATRIX.md).
- Retained `Requires at least: 7.0`, `Requires PHP: 8.3`, and evidence-backed `Tested up to: 7.1` in the package metadata.

## Task Commits

1. **Task 01-05-01: full-workflow runner and warning fixes** — `4babc16`
2. **Task 01-05-01: WordPress 7.0.6 archive-core validation** — `0fab24d`
3. **Task 01-05-01 and 01-05-02: matrix evidence and metadata gate** — `4af75bd`

## Verification

- `rtk bash tests/compat/run.sh matrix --scenario full-workflows --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL` — PASS, six supported cells.
- `rtk bash tests/compat/run.sh runtime-floor --plugin gigpress/gigpress.php --wp-lines 7.0,7.1 --supported-php 8.3 --diagnostic-php 8.2` — PASS.
- `rtk bash tests/compat/run.sh metadata --plugin gigpress.php --readme readme.txt --matrix-evidence .planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-COMPATIBILITY-MATRIX.md --expect-wp-min 7.0 --expect-php-min 8.3 --require-tested-line-pass` — PASS.
- `rtk bash tests/compat/run.sh self-test` and containerized PHP 8.3–8.5 lint of all tracked PHP files — PASS.

### Runtime-floor summary correction — 2026-10-04

The independent phase re-verification found that the lifecycle transitions themselves passed, but the final `jq` object failed to parse comparison expressions without parentheses, so the command exited non-zero before returning its summary. The runner now parenthesizes those expressions and `self-test` checks the summary serialization contract. Re-runs of `rtk bash tests/compat/run.sh runtime-floor --plugin gigpress/gigpress.php --wp-lines 7.0,7.1 --supported-php 8.3 --diagnostic-php 8.2` exit 0 for both WordPress 7.0.6 and 7.1.2: active plugin state and stored data/options persist over PHP 8.3 → 8.2 → 8.3, the below-floor plugin has no normal surface/modules/hooks, the authorized notice repeats, unauthorized/public requests do not see it, and the recovery request restores normal behavior.

## Deviations from Plan

### Auto-fixed Issues

1. **[Rule 1 - Plugin warning] Guarded optional ticket links in public list and RSS output.**
   - **Found during:** Task 01-05-01 full-workflow matrix.
   - **Issue:** PHP 8 emitted undefined-array-key warnings when a show has no ticket link.
   - **Fix:** Used `!empty()` at the two emitting template/feed checks and covered both in the full-workflow regression cell.
   - **Files modified:** `templates/shows-list.php`, `output/feed.php`.
   - **Commit:** `4babc16`.

2. **[Rule 3 - Verification] Added the documented full-workflow and evidence-metadata harness contracts.**
   - **Issue:** The inherited runner had individual activation/menu and CSV scenarios but no `full-workflows` scenario or matrix-evidence metadata command specified by this plan.
   - **Fix:** Added the single-cell aggregate workflow contract, exact image/source fields, and evidence-backed metadata validation.
   - **Files modified:** `tests/compat/run.sh`, `tests/compat/probe.php`.
   - **Commit:** `4babc16` and `4af75bd`.

3. **[Rule 3 - External image availability] Bootstrapped WordPress 7.0.6 from its official release archive.**
   - **Issue:** Docker Hub has no `wordpress:7.0.6-php8.x-apache` image even though 7.0.6 is the current WordPress 7.0 patch.
   - **Fix:** Kept the official 7.1.2 PHP/Apache image as the runtime base and replaced only core with the official 7.0.6 archive in each disposable cell; result rows record the exact base image ID.
   - **Files modified:** `tests/compat/compose.yaml`, `tests/compat/run.sh`.
   - **Commit:** `0fab24d`.

## Known Stubs

None.

## Self-Check: PASSED

- Confirmed `01-COMPATIBILITY-MATRIX.md` and every plan-modified compatibility file exist.
- Confirmed commits `4babc16`, `0fab24d`, and `4af75bd` exist in Git history.
