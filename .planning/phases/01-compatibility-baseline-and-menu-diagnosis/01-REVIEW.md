---
phase: 01-compatibility-baseline-and-menu-diagnosis
reviewed: 2026-10-04T08:06:20Z
depth: standard
files_reviewed: 2
files_reviewed_list:
  - tests/compat/probe.php
  - tests/compat/run.sh
findings:
  critical: 0
  warning: 0
  info: 0
  total: 0
status: clean
---

# Phase 01: Code Review Report

**Reviewed:** 2026-10-04T08:06:20Z
**Depth:** standard
**Files Reviewed:** 2
**Status:** clean

## Summary

This post-fix review covered the real-plugin PHP 8.3 → 8.2 → 8.3 recovery path and the disposable Compose cleanup paths in the compatibility runner. The recovered runtime now executes `real-recover` after the supported-PHP container is recreated, instead of reusing the pre-downgrade result. That probe omits `WP_INSTALLING`, so WordPress loads the already-active GigPress plugin through its normal bootstrap. The first disposable install persists deterministic `home` and `siteurl` values before the recovery request. The runner verifies restored PHP, active state, GigPress option/table snapshots, normal functions and hooks, and absence of the PHP-floor notice. Every one of the three cleanup targets now uses its locally configured `compose_env`, which supplies the required WordPress image version.

No new bug, security vulnerability, or reliability defect was confirmed in the two reviewed files. Shell syntax and the patch whitespace check passed. The full lifecycle and workflow-matrix runtime evidence cited for these fixes was not rerun during this read-only review.

## Narrative Findings (AI reviewer)

No unresolved findings.

## Resolved Prior Findings

### WR-01: Real-plugin recovery is now probed — resolved

`tests/compat/run.sh:505-507` runs `run_real_plugin_phase real-recover` after recreating the supported-PHP container and compares its active state and GigPress data snapshot with the activation and diagnostic states. `tests/compat/probe.php:189-194` skips `WP_INSTALLING` only for that purpose, allowing the already-active plugin to load during WordPress bootstrap. WordPress 7.1's `activate_plugin()` leaves an already-active plugin unchanged, so the common activation branch after bootstrap does not reactivate or mutate it.

### WR-02: Empty compatibility matrices are rejected — resolved

`tests/compat/run.sh:386-410` requires both dimensions, materializes the normalized matrix, and rejects an empty result before dispatching cells.

### WR-03: Empty menu-contract case lists are rejected — resolved

`tests/compat/run.sh:202-218` rejects blank comma-separated case lists before the container starts, and the embedded PHP contract independently rejects an empty or malformed normalized list at lines 268-271.

### WR-04: Disposable Compose projects were not reliably removed — resolved

The previous teardown commands omitted `WORDPRESS_IMAGE_VERSION`; Compose therefore rejected the incomplete interpolation while the cleanup functions suppressed the error, leaving `gigpress_compat_*` and `gigpress_floor_*` projects behind. `tests/compat/run.sh:476-480`, `553-562`, and `654-665` now invoke `compose_env down --volumes --remove-orphans`, preserving every environment value used to create the target project. `run_self_test` at lines 199-200 counts all three teardown calls and fails if a cleanup target stops using this wrapper. The supplied post-commit evidence also confirmed that the complete matrix and both runtime-floor lifecycles left no matching containers or networks.

---

_Reviewed: 2026-10-04T08:06:20Z_
_Reviewer: the agent (gsd-code-reviewer)_
_Depth: standard_
