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
| **Framework** | Repository-owned disposable WordPress/PHP integration harness using OrbStack's installed Docker-compatible engine and Compose interface (created by Plan 01-01); no host PHP. |
| **Config file** | `tests/compat/compose.yaml` with orchestration in `tests/compat/run.sh`. |
| **Quick run command** | `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario activation-menu` |
| **Full suite command** | `rtk bash tests/compat/run.sh matrix --scenario full-workflows --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL` |
| **Estimated runtime** | Record measured cached and cold-start durations in Task 01-01-02; each task uses the smallest relevant cell before the final full matrix. |

---

## Sampling Rate

- **After every task commit:** Run the task's named `tests/compat/run.sh` command. Plan 01-01's parser-compatibility tasks lint the targeted composition root first and then all tracked PHP on every supported branch; later scoped PHP changes use their runtime/menu scenario, with all-file lint repeated by the final matrix gate.
- **After every plan wave:** Run the supported matrix scenario affected by that wave and retain warning/fatal results in the named evidence artifact.
- **Before `$gsd-verify-work`:** Full supported matrix must be green and evidence recorded for each compatibility requirement.
- **Max feedback latency:** One cached cell; Task 01-01-02 records the measured duration and executor uses that value as the phase sampling ceiling.

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 01-01-01 | 01-01 | 1 | COMP-02, COMP-03 | T-01-01, T-01-02, T-01-11 | The composition-root parser blocker is converted before a disposable supported cell activates the real plugin and records menu state without warnings/fatals or external DB access. | parser preflight + tracer integration | `rtk bash tests/compat/run.sh lint --php-branches 8.3 --files gigpress.php` and `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario activation-menu` | ❌ W0 | ⬜ pending |
| 01-01-02 | 01-01 | 1 | COMP-02, COMP-03 | T-01-11 | All shipped PHP parses on every supported branch after narrow offset conversions, and the representative CSV round trip preserves existing behavior. | syntax + workflow integration | `rtk bash tests/compat/run.sh lint --php-branches 8.3,8.4,8.5 --all-tracked-php` and `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario csv-roundtrip` | ❌ W0 | ⬜ pending |
| 01-01-03 | 01-01 | 1 | COMP-02, COMP-03, COMP-04 | T-01-01, T-01-02 | Matrix parsing is fail-closed; the below-floor fixture retains active state, exposes only the repeated authorized notice, and restores its normal surface on PHP 8.3+. | harness contract + lifecycle integration | `rtk bash tests/compat/run.sh self-test` and `rtk bash tests/compat/run.sh runtime-floor --wp-lines 7.0,7.1 --supported-php 8.3 --diagnostic-php 8.2` | ❌ W0 | ⬜ pending |
| 01-02-01 | 01-02 | 2 | COMP-03 | T-01-03, T-01-04 | Diagnostic data is request-local/redacted and the controlled warning names its exact row creator and callback. | diagnostic integration | `rtk bash tests/compat/run.sh diagnose-menu --wp 7.1.2 --php 8.2 --diagnostic tests/compat/diagnostics/menu-trace.php --conflict-fixture tests/compat/fixtures/menu-conflict-plugin.php --conflict-mode exact-key-late-add --expect-key separator-gigpress` | ❌ W0 | ⬜ pending |
| 01-02-02 | 01-02 | 2 | COMP-03 | T-01-03, T-01-04 | Fixture, checkout, historical, and unavailable live-site evidence remain distinct; the repository boundary does not claim a production actor. | diagnostic evidence contract | `rtk bash tests/compat/run.sh diagnose-menu --wp 7.1.2 --php 8.2 --diagnostic tests/compat/diagnostics/menu-trace.php --conflict-fixture tests/compat/fixtures/menu-conflict-plugin.php --conflict-mode exact-key-late-add --expect-key separator-gigpress --assert-repository-boundary .planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-DIAGNOSIS.md` | ❌ W0 | ⬜ pending |
| 01-03-01 | 01-03 | 2 | COMP-01, COMP-02 | T-01-06 | The canonical header/readme floors match D-01, PHP 8.2 remains diagnostic-only per D-02, and the Plan 01-01 supported activation tracer remains green. | metadata + supported tracer regression | `rtk bash tests/compat/run.sh metadata --expect-wp-min 7.0 --expect-php-min 8.3 --allow-tested-up-to-from readme.txt` and `rtk bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario activation-menu` | ❌ W0 | ⬜ pending |
| 01-03-02 | 01-03 | 2 | COMP-02, COMP-04 | T-01-05, T-01-06 | Already-active below-floor GigPress stays active but registers only an authorized repeated notice, with normal modules/hooks/functions absent until PHP 8.3+ resumes them. | lifecycle integration | `rtk bash tests/compat/run.sh runtime-floor --plugin gigpress/gigpress.php --wp-lines 7.0,7.1 --supported-php 8.3 --diagnostic-php 8.2` | ❌ W0 | ⬜ pending |
| 01-04-01 | 01-04 | 3 | COMP-03 | T-01-07, T-01-08 | Menu ordering is a pure stable transform and all missing/duplicate/empty/conflict cases return incoming core order. | contract integration | `rtk bash tests/compat/run.sh menu-contract --wp 7.1.2 --php 8.3 --cases preferred,index-zero,missing,duplicate,empty,single,order-conflict,no-global-mutation` | ❌ W0 | ⬜ pending |
| 01-04-02 | 01-04 | 3 | COMP-03 | T-01-07, T-01-08 | The exact-key fixture remains fixture-attributed; the checkout is warning-free across supported cells; order conflicts use the standard-order fallback. | diagnostic + matrix integration + human check | `rtk bash tests/compat/run.sh diagnose-menu --wp 7.1.2 --php 8.2 --diagnostic tests/compat/diagnostics/menu-trace.php --conflict-fixture tests/compat/fixtures/menu-conflict-plugin.php --conflict-mode exact-key-late-add --expect-key separator-gigpress`<br>`rtk bash tests/compat/run.sh matrix --scenario admin-menu --wp-lines 7.0,7.1 --php-supported upstream`<br>`rtk bash tests/compat/run.sh matrix --scenario admin-menu --wp-lines 7.0,7.1 --php-supported upstream --conflict-fixture tests/compat/fixtures/menu-conflict-plugin.php --conflict-mode order-only` | ❌ W0 | ⬜ pending |
| 01-05-01 | 01-05 | 4 | COMP-02, COMP-03, COMP-04 | T-01-09, T-01-10 | Every exact supported pair completes all workflows under E_ALL, and the separate diagnostic lifecycle proves retained active state, inertness, notice scoping, and automatic recovery. | full matrix + lifecycle integration | `rtk bash tests/compat/run.sh matrix --scenario full-workflows --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL` and `rtk bash tests/compat/run.sh runtime-floor --plugin gigpress/gigpress.php --wp-lines 7.0,7.1 --supported-php 8.3 --diagnostic-php 8.2` | ❌ W0 | ⬜ pending |
| 01-05-02 | 01-05 | 4 | COMP-01 | T-01-09 | Header/readme floors match and `Tested up to` is backed by a fully passing exact WordPress patch line. | metadata evidence gate | `rtk bash tests/compat/run.sh metadata --plugin gigpress.php --readme readme.txt --matrix-evidence .planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-COMPATIBILITY-MATRIX.md --expect-wp-min 7.0 --expect-php-min 8.3 --require-tested-line-pass` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] Task 01-01-01 removes the composition-root PHP 8 parser blocker, creates the disposable WordPress/PHP runner, and proves the real plugin mount/install tracer.
- [ ] Task 01-01-02 completes shipped parser compatibility and creates reusable admin/public/feed/CSV probes plus the representative CSV fixture.
- [ ] Task 01-01-03 creates the stable matrix result contract, edge cases, and the resolved D-03 active-but-inert lifecycle fixture.
- [ ] Task 01-02-01 creates the transient menu trace and proves exact row/callback attribution on a controlled conflict.
- [ ] Task 01-02-02 records the repository-history mismatch, related historical evidence, fixture-only attribution, and the unavailable live-site identity boundary without requiring site access.
- [ ] Task 01-05-01 refreshes upstream WordPress/PHP releases immediately before the final full matrix.

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|-------------|-------------------|
| None. | — | The active-but-inert lifecycle, separate-request notice persistence, capability visibility, public inertness, active-state retention, and PHP 8.3+ recovery are asserted by the automated OrbStack-backed runtime-floor scenario. | — |

---

## Validation Sign-Off

- [x] All plan tasks have automated verification.
- [x] Sampling continuity: no 3 consecutive tasks without automated verify.
- [x] Wave 0 tasks cover all missing validation references.
- [ ] No watch-mode flags.
- [ ] Feedback latency target recorded after runner selection.
- [x] `nyquist_compliant: true` set in frontmatter after validation strategy and plan mapping are complete.

**Approval:** pending
