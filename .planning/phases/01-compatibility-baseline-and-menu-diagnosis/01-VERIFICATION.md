---
phase: 01-compatibility-baseline-and-menu-diagnosis
verified: 2026-10-04T09:02:25Z
status: passed
verdict: pass
score: 4/4 must-haves verified
covered_files:
  - .planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-01-PLAN.md
  - .planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-01-SUMMARY.md
  - .planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-02-PLAN.md
  - .planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-02-SUMMARY.md
  - .planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-03-PLAN.md
  - .planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-03-SUMMARY.md
  - .planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-04-PLAN.md
  - .planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-04-SUMMARY.md
  - .planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-05-PLAN.md
  - .planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-05-SUMMARY.md
  - .planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-COMPATIBILITY-MATRIX.md
  - .planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-DIAGNOSIS.md
  - .planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-MENU-VERIFICATION.md
  - gigpress.php
  - lib/parsecsv.lib.php
  - lib/upgrade.php
  - output/feed.php
  - readme.txt
  - templates/shows-list.php
  - tests/compat/compose.yaml
  - tests/compat/diagnostics/menu-trace.php
  - tests/compat/fixtures/menu-conflict-plugin.php
  - tests/compat/fixtures/php-floor-plugin.php
  - tests/compat/fixtures/shows.csv
  - tests/compat/probe.php
  - tests/compat/run.sh
covered_digest: "v2:sha256:61b9308d009bb636aa2d38f371d03093644fd7f6059f6fc489dfd977ef11ce97"
behavior_unverified: 0
overrides_applied: 0
re_verification:
  previous_status: gaps_found
  previous_score: 3/4
  gaps_closed:
    - "The real-plugin runtime-floor command emits valid lifecycle summaries and exits zero for WordPress 7.0.6 and 7.1.2."
  gaps_remaining: []
  regressions: []
---

# Phase 01: Compatibility Baseline and Menu Diagnosis Verification Report

**Phase Goal:** Establish a trustworthy compatibility baseline for GigPress, reproduce and diagnose the legacy menu warning, and verify the plugin's supported PHP floor and recovery lifecycle.

**Verified:** 2026-10-04T09:02:25Z  
**Status:** passed  
**Re-verification:** Yes — after gap closure

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
| --- | --- | --- | --- |
| 1 | The `separator-gigpress` warning is reproducible under a controlled WordPress core menu fixture, and its source is diagnosed without claiming an unproven live callback. | ✓ VERIFIED | Fresh `diagnose-menu` on WordPress 7.1.2/PHP 8.2 emitted and captured eight exact `Undefined array key "separator-gigpress"` warnings from WordPress core `wp-admin/includes/menu.php:339`. The trace names only fixture callback `gigpress_menu_conflict_late_add` at priority 20; exact key is absent from input and returned maps. The diagnosis therefore does not attribute this to a live GigPress callback. |
| 2 | The supported WordPress 7.0/7.1 × PHP 8.3+ compatibility baseline completes the required workflows without GigPress warnings or fatals. | ✓ VERIFIED | [`01-COMPATIBILITY-MATRIX.md`](01-COMPATIBILITY-MATRIX.md) records six PASS cells at source `259c0ca`: WordPress 7.0.6 and 7.1.2 across PHP 8.3.35, 8.4.26, and 8.5.11. Each records plugin activation, admin edit/create/read, shortcode, RSS/iCal, CSV import, and duplicate workflows with no plugin warnings or fatals. Commit `8ac118d` changes only runtime-floor result serialization. |
| 3 | Public compatibility metadata states WordPress floor 7.0, PHP floor 8.3, and WordPress 7.1 test coverage. | ✓ VERIFIED | `gigpress.php` header and `readme.txt` declare the same floors; the metadata check passed against expected `7.0`, `8.3`, and `7.1`. |
| 4 | An already-active GigPress remains active but inert below PHP 8.3, preserves state and data, scopes a single admin notice, avoids normal runtime loading, and recovers on PHP 8.3. | ✓ VERIFIED | Fresh `runtime-floor --plugin gigpress/gigpress.php --wp-lines 7.0,7.1 --supported-php 8.3 --diagnostic-php 8.2` exited 0. For WordPress 7.0.6 and 7.1.2 it reported `plugin_active_preserved: true`, `data_preserved: true`, no normal surface/hooks/modules under PHP 8.2, exactly one authorized admin notice, no public/unauthorized notice, and recovery under PHP 8.3. |

**Score:** 4/4 truths verified (0 present, behavior-unverified)

### Required Artifacts

| Artifact | Expected | Status | Details |
| --- | --- | --- | --- |
| `tests/compat/run.sh` | Reproducible compatibility, diagnostic, contract, metadata, and lifecycle runner | ✓ VERIFIED | Shell syntax and `self-test` pass; fresh real-plugin lifecycle run returns valid PASS JSON for both WordPress lines. |
| `tests/compat/diagnostics/menu-trace.php` | Captures core warning and menu ownership/order evidence | ✓ VERIFIED | Loaded by `diagnose-menu`; captured the exact WordPress warning and callback trace. |
| `tests/compat/fixtures/menu-conflict-plugin.php` | Deterministic late exact-key conflict fixture | ✓ VERIFIED | The exact-key-late-add mode produces the controlled warning without modifying GigPress. |
| `tests/compat/fixtures/php-floor-plugin.php` and `gigpress.php` | Real-plugin PHP-floor lifecycle coverage | ✓ VERIFIED | The runner booted actual WordPress with GigPress active, then observed low-floor and recovery states. |
| `01-COMPATIBILITY-MATRIX.md` | Supported six-cell compatibility evidence | ✓ VERIFIED | Records exact image versions, commands, workflow results, and source revision. |
| `01-MENU-VERIFICATION.md` | Visual WordPress-admin menu evidence | ✓ VERIFIED | Records disposable WordPress 7.1.2/PHP 8.3 preferred and conflict views with GigPress's menu available and no raw warning output. |

### Key Link Verification

| From | To | Via | Status | Details |
| --- | --- | --- | --- | --- |
| `run.sh diagnose-menu` | `menu-trace.php` | WordPress boot plus fixture activation and warning capture | ✓ WIRED | Fresh exact-key command produced core warning, trace data, and exit 0. |
| `run.sh full-workflows` | `probe.php` / actual GigPress | Disposable WordPress containers and HTTP/admin workflow probes | ✓ WIRED | Six recorded matrix cells complete all required workflows. |
| `gigpress.php` PHP guard | WordPress activation/runtime environment | Low-floor inertness and supported-floor recovery lifecycle | ✓ WIRED | Fresh real-plugin lifecycle result proves the active plugin is inert below PHP 8.3 and recovers on the supported floor in both WordPress versions. |
| Plugin/readme headers | metadata check | Explicit floor and tested-version assertions | ✓ WIRED | Fresh metadata check returned PASS. |

### Data-Flow Trace (Level 4)

| Artifact | Data Variable | Source | Produces Real Data | Status |
| --- | --- | --- | --- | --- |
| `menu-trace.php` | warning/trace/order records | WordPress runtime warning handler and menu globals | Yes | ✓ FLOWING |
| `probe.php` | admin/public workflow responses | Disposable, actual WordPress + active GigPress installation | Yes | ✓ FLOWING |
| `run.sh runtime-floor` | activation/data/notice/recovery fields | Actual WordPress option state, page output, hooks, and loaded modules | Yes | ✓ FLOWING |

### Behavioral Spot-Checks

| Behavior | Command | Result | Status |
| --- | --- | --- | --- |
| Runner self-validation | `rtk bash -n tests/compat/run.sh && rtk bash tests/compat/run.sh self-test` | Exit 0; self-test PASS | ✓ PASS |
| Controlled core warning reproduction | `rtk bash tests/compat/run.sh diagnose-menu --wp 7.1.2 --php 8.2 --diagnostic tests/compat/diagnostics/menu-trace.php --conflict-fixture tests/compat/fixtures/menu-conflict-plugin.php --conflict-mode exact-key-late-add --expect-key separator-gigpress --assert-repository-boundary .planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-DIAGNOSIS.md` | Exit 0; eight captured exact warnings at `menu.php:339` | ✓ PASS |
| Supported conflict menu contract | `rtk bash tests/compat/run.sh menu-contract --wp 7.1.2 --php 8.3 --cases preferred,index-zero,missing,duplicate,empty,single,order-conflict,no-global-mutation` | Exit 0; all cases PASS | ✓ PASS |
| Supported compatibility matrix | Recorded `full-workflows` six-cell run in `01-COMPATIBILITY-MATRIX.md` | All six cells PASS at source `259c0ca` | ✓ PASS |
| Compatibility metadata | `rtk bash tests/compat/run.sh metadata --plugin gigpress.php --readme readme.txt --matrix-evidence .planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-COMPATIBILITY-MATRIX.md --expect-wp-min 7.0 --expect-php-min 8.3 --require-tested-line-pass` | Exit 0; metadata PASS with `Tested up to: 7.1` backed by matrix evidence | ✓ PASS |
| Active-but-inert behavior and recovery | `rtk bash tests/compat/run.sh runtime-floor --plugin gigpress/gigpress.php --wp-lines 7.0,7.1 --supported-php 8.3 --diagnostic-php 8.2` | Exit 0; WordPress 7.0.6 and 7.1.2 lifecycle records PASS while the plugin stays active below PHP 8.3 | ✓ PASS |

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
| --- | --- | --- | --- | --- |
| COMP-01 | 01-03, 01-05 | Declare the WordPress 7.0 and PHP 8.3 floors and back the Tested up to value with passing evidence. | ✓ SATISFIED | Plugin/readme metadata agrees; the evidence-backed metadata check passes for WordPress 7.1. |
| COMP-02 | 01-01, 01-03, 01-05 | Run existing workflows on WordPress 7.0/7.1 and every currently supported PHP branch at or above 8.3. | ✓ SATISFIED | Six full-workflow cells pass under PHP 8.3, 8.4, and 8.5 without GigPress warnings or fatals. |
| COMP-03 | 01-01, 01-02, 01-04, 01-05 | Reproduce and diagnose the controlled menu warning, remove GigPress's equivalent unsafe ordering behavior, and leave the live callback unclaimed. | ✓ SATISFIED | The exact core warning is captured from the controlled fixture; the supported menu contract and conflict matrix pass, and the unavailable live callback remains unproven. |
| COMP-04 | 01-01, 01-03, 01-05 | Keep already-active GigPress installations inert and scoped below PHP 8.3, preserve state, then recover on PHP 8.3+. | ✓ SATISFIED | The real-plugin lifecycle passes on both WordPress lines with active/data preservation, notice scoping, and PHP 8.3 recovery. |

### Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
| --- | --- | --- | --- | --- |
| None | — | No unreferenced `TBD`, `FIXME`, or `XXX` debt markers in phase implementation artifacts | — | No blocker |

### Recorded Visual Verification

`01-MENU-VERIFICATION.md` records the completed manual WordPress-admin checks on disposable WordPress 7.1.2/PHP 8.3 instances: GigPress's **Add a show** screen opened in the preferred and conflict arrangements, and neither showed raw warning output. Its scope correctly does not claim that an unavailable live callback was identified.

### Decision Coverage

All four decisions in the phase decision log are honored: supported floors and workflows have evidence, the warning diagnosis uses the controlled fixture and core trace, metadata matches the support contract, and the PHP-floor lifecycle is exercised against the actual plugin.

### Advisory (New Scope, Unevidenced)

None.

## Gaps Summary

No gaps remain. The previous lifecycle blocker was a runner JSON serialization defect, not a product lifecycle failure; commit `8ac118d` repairs it, and the independent full real-plugin lifecycle command now succeeds on both supported WordPress lines.

---

_Verified: 2026-10-04T09:02:25Z_  
_Verifier: the agent (gsd-verifier)_
