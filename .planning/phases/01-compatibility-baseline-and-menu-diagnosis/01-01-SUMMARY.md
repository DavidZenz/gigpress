---
phase: 01-compatibility-baseline-and-menu-diagnosis
plan: "01"
subsystem: testing
tags: [wordpress, php, docker-compose, compatibility]
requires: []
provides:
  - Parser-safe GigPress PHP sources verified in official WordPress PHP 8.3, 8.4, and 8.5 images
  - Disposable WordPress and MariaDB Compose harness with activation, workflow, matrix, and runtime-floor commands
  - Controlled active-but-inert fixture lifecycle and a gated real-GigPress assertion target
affects: [01-02, 01-03, 01-04, 01-05]
actuals:
  tokens: 8583
  tasks: 3
  commits: 4
tech-stack:
  added: [Docker Compose, official WordPress images, official MariaDB image]
  patterns: [isolated Compose project per cell, PHP execution inside containers only, diagnostic PHP floor kept outside supported matrix]
key-files:
  created: [.gitignore, tests/compat/fixtures/php-floor-plugin.php, tests/compat/fixtures/shows.csv]
  modified: [gigpress.php, lib/parsecsv.lib.php, lib/upgrade.php, tests/compat/compose.yaml, tests/compat/run.sh, tests/compat/probe.php]
key-decisions:
  - "PHP 8.2 remains a diagnostic-only runtime and cannot enter lint or supported matrix results."
  - "The controlled fixture's original diagnostic path used a narrow plugin API facade; a later Phase 01 recheck established that the target WordPress releases can boot on diagnostic PHP 8.2."
  - "The real GigPress runtime-floor target fails closed until Plan 01-03 supplies the production guard."
patterns-established:
  - "Compatibility commands create a unique Compose project and remove its volumes through cleanup traps."
  - "Matrix pairs are normalized, exact-deduplicated, and sorted before execution."
requirements-completed: [COMP-02, COMP-03, COMP-04]
coverage:
  - id: D1
    description: "Parser-safe shipped PHP source across PHP 8.3, 8.4, and 8.5"
    requirement: COMP-02
    verification:
      - kind: integration
        ref: "rtk bash tests/compat/run.sh lint --php-branches 8.3,8.4,8.5 --all-tracked-php"
        status: pass
    human_judgment: false
  - id: D2
    description: "Supported WordPress activation and GigPress admin-menu tracer"
    requirement: COMP-03
    verification:
      - kind: integration
        ref: "rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario activation-menu"
        status: pass
    human_judgment: false
  - id: D3
    description: "Deterministic matrix and controlled active-but-inert runtime-floor fixture"
    requirement: COMP-04
    verification:
      - kind: integration
        ref: "rtk bash tests/compat/run.sh self-test"
        status: pass
      - kind: integration
        ref: "rtk bash tests/compat/run.sh runtime-floor --fixture tests/compat/fixtures/php-floor-plugin.php --wp-lines 7.0,7.1 --supported-php 8.3 --diagnostic-php 8.2"
        status: pass
    human_judgment: false
duration: 36min
completed: 2026-10-03
commits: 4
plan_head_before: dd471ea375bcfc67b54e6780629b2a1e891b7aaf
plan_head_after: 506162f1d184daa2527fc256545eb0db6e866966
status: complete
---

# Phase 01 Plan 01: Compatibility Baseline and Menu Diagnosis Summary

**A container-only WordPress compatibility harness with PHP 8 parser fixes, an activation-to-menu tracer, deterministic matrix execution, and a controlled active-but-inert fixture lifecycle.**

## Performance

- **Duration:** 36 min
- **Started:** 2026-10-03T20:11:30Z
- **Completed:** 2026-10-03T20:47:31Z
- **Tasks:** 3
- **Files modified:** 9

## Accomplishments

- Converted every workflow-reachable removed curly-brace offset to parser-safe bracket syntax while preserving CSV export and legacy helper behavior.
- Added a disposable Compose harness that lints in official WordPress containers, activates GigPress, constructs its admin menu, and exercises CSV round trips without host PHP.
- Added a sorted, duplicate-free matrix command and a controlled PHP-floor fixture that retains activation, restricts its low-runtime surface, scopes recurring notices, and recovers on PHP 8.3.

## Task Commits

1. **Task 01-01-01: Make the composition root parse-safe and prove one supported activation-to-menu path** - `159fd5e` (feat)
2. **Task 01-01-02: Complete shipped parser compatibility and expand workflow probes** - `a5f31ab` (feat)
3. **Task 01-01-03: Add deterministic matrix and runtime-floor harness contracts** - `6a1b6f9` (feat)
4. **Task 01-01-03 correction: Preserve runtime-floor volumes** - `506162f` (fix)

## Files Created/Modified

- `gigpress.php`, `lib/parsecsv.lib.php`, and `lib/upgrade.php` - PHP 8 parser-safe offset accesses.
- `tests/compat/compose.yaml` and `tests/compat/run.sh` - isolated Compose lifecycle, linting, cells, matrix, and runtime-floor commands.
- `tests/compat/probe.php` - WordPress probes, CSV smoke coverage, fixture lifecycle checks, and explicit real-plugin inventory.
- `tests/compat/fixtures/shows.csv` and `tests/compat/fixtures/php-floor-plugin.php` - representative CSV and PHP-floor fixtures.
- `.gitignore` - excludes generated compatibility evidence from commits.

## Decisions Made

- PHP 8.2 is diagnostic-only and is rejected from supported lint and matrix paths.
- The real GigPress runtime-floor assertion remains intentionally gated until Plan 01-03 adds the production guard.
- The controlled fixture uses the same disposable database across transitions. Its original diagnostic PHP 8.2 step read the active option and evaluated only fixture APIs through a narrow facade after the initial runner path failed to bootstrap.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Stabilized the fixture lifecycle across the diagnostic PHP transition**
- **Found during:** Task 01-01-03
- **Issue:** The initial harness's PHP 8.2 fixture request did not complete its expected WordPress bootstrap after activation, despite the same isolated MariaDB tables remaining available. A later direct recheck showed this was a limitation of that harness path, not of the target WordPress releases.
- **Fix:** Kept the activation in a supported WordPress request, read the same `active_plugins` option through the container's mysqli driver for diagnostic and recovery requests, and supplied only the fixture's `add_action()` and capability APIs.
- **Files modified:** `tests/compat/probe.php`, `tests/compat/run.sh`
- **Verification:** The complete fixture flow passed on WordPress 7.0 and 7.1 with PHP 8.3 → 8.2 → 8.3 transitions.
- **Committed in:** `6a1b6f9`, `506162f`

**2. [Rule 1 - Bug] Prevented the fixture's normal surface from being declared below the PHP floor**
- **Found during:** Task 01-01-03
- **Issue:** PHP registered the fixture's unconditional function before its early return, so the below-floor normal surface still existed.
- **Fix:** Declared the normal function and `init` hook inside the PHP 8.3+ conditional.
- **Files modified:** `tests/compat/fixtures/php-floor-plugin.php`
- **Verification:** The runtime-floor flow observed no normal function below the floor and restored it on PHP 8.3.
- **Committed in:** `6a1b6f9`

**3. [Rule 3 - Blocking] Waited for the disposable application database account before WordPress startup**
- **Found during:** Task 01-01-03
- **Issue:** MariaDB could report healthy before its disposable `wordpress` account accepted queries, causing an intermittent activation connection failure.
- **Fix:** Started MariaDB first and waited for an authenticated `SELECT 1` before starting WordPress.
- **Files modified:** `tests/compat/run.sh`
- **Verification:** The full two-line fixture command completed with exit status 0.
- **Committed in:** `6a1b6f9`

**Total deviations:** 3 auto-fixed (1 Rule 1, 2 Rule 3).

## Issues Encountered

The diagnostic PHP 8.2 image can connect to the disposable MariaDB database directly, but its target WordPress core bootstrap reports unavailable core tables. The fixture adapter is intentionally limited to the active-option and plugin API behavior needed for the controlled contract. Plan 01-03 retains the separate real-GigPress assertion mode and must run it after installing the production guard.

## User Setup Required

None.

## Later Phase 01 Correction

The initial investigation incorrectly generalized a runner/bootstrap failure into a claim that WordPress 7.0 and 7.1 could not boot on PHP 8.2. A later direct recheck in Plan 01-04 booted WordPress 7.0.6 and 7.1.2 under PHP 8.2.34. The controlled fixture still uses its narrow facade for its own fixture contract, but the real GigPress below-floor lifecycle now uses the actual WordPress plugin-loading path and does not use that facade.

## Next Phase Readiness

Plans 01-02 through 01-05 can reuse the disposable cell, matrix, lint, and diagnostic runtime-floor commands. The real-plugin target is available but correctly fails closed until Plan 01-03 adds its runtime guard.

## Self-Check: PASSED

- Confirmed every created or modified compatibility artifact exists.
- Confirmed commits `159fd5e`, `a5f31ab`, `6a1b6f9`, and `506162f` are present in Git history.

---
*Phase: 01-compatibility-baseline-and-menu-diagnosis*
*Completed: 2026-10-03*
