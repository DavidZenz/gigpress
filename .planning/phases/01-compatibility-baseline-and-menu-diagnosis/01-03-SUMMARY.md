---
phase: 01-compatibility-baseline-and-menu-diagnosis
plan: "03"
subsystem: compatibility
tags: [wordpress, php, plugin-metadata, docker-compose]
requires:
  - phase: 01-01
    provides: parser-safe PHP 8.3 activation tracer and compatibility harness
provides:
  - WordPress 7.0 and PHP 8.3 compatibility declarations
  - Active-but-inert runtime guard for PHP below 8.3
  - Metadata and real-plugin runtime-floor assertions
affects: [01-04, 01-05, compatibility]
actuals:
  tokens: 4692
  tasks: 2
  commits: 2
commits: 2
plan_head_before: abe592afb199173f4db5b1f840035d26c5107788
plan_head_after: 448860009a80db3053a6d7025a5e9071087cab8f
tech-stack:
  added: []
  patterns: [early PHP runtime branch, capability-scoped escaped compatibility notice, Compose metadata contract]
key-files:
  created: []
  modified: [gigpress.php, readme.txt, tests/compat/run.sh, tests/compat/probe.php]
key-decisions:
  - "Declare the selected WordPress 7.0 and PHP 8.3 floors in both distribution metadata sources."
  - "Leave already-active below-floor installations active while exposing only an escaped activate_plugins-scoped notice."
requirements-completed: [COMP-01, COMP-02, COMP-04]
coverage:
  - id: D1
    description: WordPress 7.0 and PHP 8.3 support metadata remains aligned between the plugin header and readme.
    requirement: COMP-01
    verification:
      - kind: integration
        ref: rtk bash tests/compat/run.sh metadata --expect-wp-min 7.0 --expect-php-min 8.3 --allow-tested-up-to-from readme.txt
        status: pass
    human_judgment: false
  - id: D2
    description: GigPress becomes inert below PHP 8.3 and resumes its supported bootstrap when PHP 8.3 returns.
    requirement: COMP-04
    verification:
      - kind: integration
        ref: rtk bash tests/compat/run.sh runtime-floor --plugin gigpress/gigpress.php --wp-lines 7.0,7.1 --supported-php 8.3 --diagnostic-php 8.2
        status: pass
    human_judgment: false
duration: 14min
completed: 2026-10-04
status: complete
---

# Phase 01 Plan 03: Runtime Floor Summary

**GigPress now declares WordPress 7.0/PHP 8.3 support and stays active but inert with an administrator-only compatibility notice below the PHP floor.**

## Performance

- **Duration:** 14min
- **Started:** 2026-10-04T06:19:21Z
- **Completed:** 2026-10-04T06:33:10Z
- **Tasks:** 2/2
- **Files modified:** 4

## Accomplishments

- Added exact WordPress 7.0 and PHP 8.3 requirements to the plugin header and readme, with a metadata contract that rejects PHP 8.2 as a supported floor.
- Added an early PHP-version boundary that registers only an escaped `admin_notices` callback for users who can activate plugins below PHP 8.3.
- Extended the compatibility harness to exercise the approved real-plugin target through supported activation, diagnostic low-runtime state, and PHP 8.3 recovery checks on WordPress 7.0 and 7.1.

## Task Commits

1. **Task 1: Declare the WordPress and PHP support floor** - `ce5f28b` (feat)
2. **Task 2: Enforce the declared runtime floor and already-active transition** - `4488600` (feat)

## Files Created/Modified

- `gigpress.php` - Declares the floors and keeps the normal bootstrap behind the PHP 8.3 boundary.
- `readme.txt` - Publishes aligned WordPress, PHP, and tested-up-to metadata.
- `tests/compat/run.sh` - Adds metadata validation and enables the real GigPress runtime-floor lifecycle.
- `tests/compat/probe.php` - Adds narrow real-plugin state, notice, module, and data assertions.

## Decisions Made

- The plugin header remains the activation authority; `readme.txt` mirrors the floors and supplies the tested WordPress line.
- The below-floor notice is capability-scoped with `activate_plugins`, rendered only through `admin_notices`, and escapes its static compatibility message.

## Deviations from Plan

### User-authorized Scoped Harness Deviation

- **Found during:** Task 1 and Task 2
- **Issue:** The plan’s required `metadata` command was unsupported, and its real-plugin runtime-floor mode deliberately failed closed until this plan installed the production guard.
- **Fix:** Updated only `tests/compat/run.sh` and `tests/compat/probe.php` to implement metadata validation and the existing real-plugin lifecycle contract while preserving the separate controlled fixture path.
- **Verification:** Metadata, activation/menu, self-test, and real-plugin runtime-floor commands pass.
- **Committed in:** `ce5f28b`, `4488600`

**Total deviations:** 1 user-authorized scoped harness deviation.

## Issues Encountered

- During initial implementation, the runner's PHP 8.2 transition path did not complete its expected bootstrap, so it reused supported-runtime state for assertions. A later direct recheck in Plan 01-04 showed that WordPress 7.0.6 and 7.1.2 can fully boot on PHP 8.2.34. The real-plugin low-floor scenario now boots WordPress directly; the narrow facade remains confined to the separate controlled fixture path.

## User Setup Required

None.

## Next Phase Readiness

- Plan 01-04 can correct the remaining menu-order mutation on the declared PHP 8.3 baseline.

## Self-Check: PASSED

---
*Phase: 01-compatibility-baseline-and-menu-diagnosis*
*Completed: 2026-10-04*
