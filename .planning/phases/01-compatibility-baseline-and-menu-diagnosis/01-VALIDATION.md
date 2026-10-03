---
phase: "01"
slug: "compatibility-baseline-and-menu-diagnosis"
# status lifecycle: draft (seeded by plan-phase) → validated (set by validate-phase §6)
status: draft
nyquist_compliant: true
wave_0_complete: false
created: "2026-10-03"
---

# Phase 01 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Repository-owned disposable Docker Compose WordPress/PHP integration harness (created by Plan 01-01). |
| **Config file** | `tests/compat/compose.yaml` with orchestration in `tests/compat/run.sh`. |
| **Quick run command** | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario activation-menu` |
| **Full suite command** | `rtk bash tests/compat/run.sh matrix --scenario full-workflows --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL` |
| **Estimated runtime** | Record measured cached and cold-start durations in Task 01-01-02; each task uses the smallest relevant cell before the final full matrix. |

---

## Sampling Rate

- **After every task commit:** Run the task's named `tests/compat/run.sh` command; PHP-changing tasks also lint all tracked PHP on each supported PHP branch.
- **After every plan wave:** Run the supported matrix scenario affected by that wave and retain warning/fatal results in the named evidence artifact.
- **Before `$gsd-verify-work`:** Full supported matrix must be green and evidence recorded for each compatibility requirement.
- **Max feedback latency:** One cached cell; Task 01-01-02 records the measured duration and executor uses that value as the phase sampling ceiling.

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 01-01-01 | 01-01 | 1 | COMP-02, COMP-03 | T-01-01, T-01-02 | A disposable supported cell activates the real plugin and records menu state without warnings/fatals or external DB access. | tracer integration | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario activation-menu` | ❌ W0 | ⬜ pending |
| 01-01-02 | 01-01 | 1 | COMP-02, COMP-03 | T-01-01, T-01-02 | Matrix parsing is fail-closed and core's D-03 compatibility feedback is capability-scoped and persistent after downgrade. | harness contract + lifecycle integration | `rtk bash tests/compat/run.sh self-test` and `rtk bash tests/compat/run.sh runtime-floor --wp-lines 7.0,7.1 --supported-php 8.3 --diagnostic-php 8.2` | ❌ W0 | ⬜ pending |
| 01-02-01 | 01-02 | 2 | COMP-03 | T-01-03, T-01-04 | Diagnostic data is request-local/redacted and the controlled warning names its exact row creator and callback. | diagnostic integration | `rtk bash tests/compat/run.sh diagnose-menu --wp 7.1.2 --php 8.2 --diagnostic tests/compat/diagnostics/menu-trace.php --conflict-fixture tests/compat/fixtures/menu-conflict-plugin.php` | ❌ W0 | ⬜ pending |
| 01-02-02 | 01-02 | 2 | COMP-03 | T-01-03, T-01-04 | Affected-site attribution requires a redacted deployed build/callback trace and cannot be inferred from warning text. | blocking human evidence | Site-owner trace checkpoint; executor verifies exact row/callback attribution | n/a checkpoint | ⬜ pending |
| 01-03-01 | 01-03 | 2 | COMP-02 | T-01-06 | All shipped PHP parses on every supported PHP branch and CSV behavior remains unchanged. | syntax + workflow integration | `rtk bash tests/compat/run.sh lint --php-branches 8.3,8.4,8.5 --all-tracked-php` plus the PHP 8.3 CSV round trip | ❌ W0 | ⬜ pending |
| 01-03-02 | 01-03 | 2 | COMP-01, COMP-02 | T-01-05, T-01-06 | Runtime floors match metadata; below-floor GigPress deactivates without exposing feedback to unauthorized users. | metadata + lifecycle integration | `rtk bash tests/compat/run.sh runtime-floor --plugin gigpress/gigpress.php --wp-lines 7.0,7.1 --supported-php 8.3 --diagnostic-php 8.2` | ❌ W0 | ⬜ pending |
| 01-04-01 | 01-04 | 3 | COMP-03 | T-01-07, T-01-08 | Menu ordering is a pure stable transform and all missing/duplicate/empty/conflict cases return incoming core order. | contract integration | `rtk bash tests/compat/run.sh menu-contract --wp 7.1.2 --php 8.3 --cases preferred,index-zero,missing,duplicate,empty,single,order-conflict,no-global-mutation` | ❌ W0 | ⬜ pending |
| 01-04-02 | 01-04 | 3 | COMP-03 | T-01-07, T-01-08 | Preferred placement and standard-order fallback are warning-free across supported cells and competing order callbacks. | matrix integration + human check | `rtk bash tests/compat/run.sh matrix --scenario admin-menu --wp-lines 7.0,7.1 --php-supported upstream --conflict-fixture tests/compat/fixtures/menu-conflict-plugin.php --conflict-mode order-only` | ❌ W0 | ⬜ pending |
| 01-05-01 | 01-05 | 4 | COMP-02, COMP-03 | T-01-09, T-01-10 | Every exact supported pair completes all existing workflows under E_ALL using disposable data. | full matrix integration | `rtk bash tests/compat/run.sh matrix --scenario full-workflows --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL` | ❌ W0 | ⬜ pending |
| 01-05-02 | 01-05 | 4 | COMP-01 | T-01-09 | Header/readme floors match and `Tested up to` is backed by a fully passing exact WordPress patch line. | metadata evidence gate | `rtk bash tests/compat/run.sh metadata --plugin gigpress.php --readme readme.txt --matrix-evidence .planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-COMPATIBILITY-MATRIX.md --expect-wp-min 7.0 --expect-php-min 8.3 --require-tested-line-pass` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] Task 01-01-01 creates the disposable WordPress/PHP runner and real plugin mount/install tracer.
- [ ] Task 01-01-02 creates reusable admin/public/feed/CSV fixtures, stable result schema, matrix edges, and the D-03 core-feedback feasibility check.
- [ ] Task 01-02-01 creates the transient menu trace and proves exact row/callback attribution on a controlled conflict.
- [ ] Task 01-02-02 resolves the external deployed-build attribution or records the blocking dependency conflict.
- [ ] Task 01-05-01 refreshes upstream WordPress/PHP releases immediately before the final full matrix.

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|-------------|-------------------|
| Attribute `separator-gigpress` to the deployed plugin build or another active menu-order callback. | COMP-03 | The original production environment is unavailable to this repository run; capture a one-time diagnostic trace on the reported installation if the owner can reproduce it. | Record the deployed GigPress build, active plugins/MU-plugins/theme, registered `menu_order` callbacks, input order, returned order, and final menu slugs. Do not persist raw diagnostics in the admin notice. |
| Confirm the persistent below-minimum notice after self-deactivation on both WordPress target lines. | COMP-02 | This is an admin lifecycle interaction whose persistence depends on WordPress core behavior and must be observed after the plugin is no longer loaded. | On a disposable site with GigPress active, use PHP 8.2; observe deactivation and then revisit the Plugins screen as a user with plugin-management capability. Confirm the notice remains until PHP 8.3+ is detected. |

---

## Validation Sign-Off

- [x] All plan tasks have automated verification or an explicit blocking-human evidence checkpoint.
- [x] Sampling continuity: no 3 consecutive tasks without automated verify.
- [x] Wave 0 tasks cover all missing validation references.
- [ ] No watch-mode flags.
- [ ] Feedback latency target recorded after runner selection.
- [x] `nyquist_compliant: true` set in frontmatter after validation strategy and plan mapping are complete.

**Approval:** pending
