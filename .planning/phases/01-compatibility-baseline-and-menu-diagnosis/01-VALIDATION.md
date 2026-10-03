---
phase: "01"
slug: "compatibility-baseline-and-menu-diagnosis"
# status lifecycle: draft (seeded by plan-phase) → validated (set by validate-phase §6)
status: draft
nyquist_compliant: false
wave_0_complete: false
created: "2026-10-03"
---

# Phase 01 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | None detected — select and configure a disposable WordPress integration runner in Wave 0. |
| **Config file** | None — Wave 0 defines the runner configuration. |
| **Quick run command** | TBD in Wave 0: lint changed PHP in the selected PHP container and run the smallest affected smoke cell. |
| **Full suite command** | TBD in Wave 0: run the pinned WordPress 7.0.6/7.1.2 × PHP 8.3/8.4/8.5 matrix plus the PHP 8.2 diagnostic cell. |
| **Estimated runtime** | TBD after the disposable runner is selected and measured. |

---

## Sampling Rate

- **After every task commit:** Lint changed PHP files in the selected PHP container and run the smallest relevant disposable-site smoke cell.
- **After every plan wave:** Run all supported WordPress/PHP cells and archive warning/fatal logs.
- **Before `$gsd-verify-work`:** Full supported matrix must be green and evidence recorded for each compatibility requirement.
- **Max feedback latency:** TBD in Wave 0 after measuring the selected runner.

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| TBD after plan task IDs are assigned | TBD | 0 | COMP-01 | — | Version declarations match the verified matrix; no unvalidated `Tested up to` claim. | metadata + integration | TBD in Wave 0 | ❌ W0 | ⬜ pending |
| TBD after plan task IDs are assigned | TBD | 0 | COMP-02 | — | Existing admin, public display, feed, and CSV workflows complete without PHP warnings or fatals. | integration smoke | TBD in Wave 0 | ❌ W0 | ⬜ pending |
| TBD after plan task IDs are assigned | TBD | 0 | COMP-03 | — | Menu diagnostics are temporary and restricted to the disposable environment; normal menu ordering emits no warning. | diagnostic + integration | TBD in Wave 0 | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] Define a reproducible disposable WordPress/PHP matrix runner and plugin mount/install procedure.
- [ ] Add a temporary controlled trace for menu-order input, returned order, final slugs, and participating callbacks; ensure normal requests do not retain diagnostic output.
- [ ] Define reusable fixtures/checklist for an admin action, public shortcode/list, RSS, iCalendar, CSV import, and CSV export.
- [ ] Record each matrix cell's WordPress/PHP versions and warning/fatal output in a consistent results format.
- [ ] Verify the reported PHP 8.2 diagnostic case and whether WordPress keeps an actionable compatibility notice visible after an already-active plugin is deactivated; pause for a product decision if core behavior cannot satisfy D-03.
- [ ] Recheck upstream-supported WordPress and PHP releases immediately before fixing the final matrix.

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|-------------|-------------------|
| Attribute `separator-gigpress` to the deployed plugin build or another active menu-order callback. | COMP-03 | The original production environment is unavailable to this repository run; capture a one-time diagnostic trace on the reported installation if the owner can reproduce it. | Record the deployed GigPress build, active plugins/MU-plugins/theme, registered `menu_order` callbacks, input order, returned order, and final menu slugs. Do not persist raw diagnostics in the admin notice. |
| Confirm the persistent below-minimum notice after self-deactivation on both WordPress target lines. | COMP-02 | This is an admin lifecycle interaction whose persistence depends on WordPress core behavior and must be observed after the plugin is no longer loaded. | On a disposable site with GigPress active, use PHP 8.2; observe deactivation and then revisit the Plugins screen as a user with plugin-management capability. Confirm the notice remains until PHP 8.3+ is detected. |

---

## Validation Sign-Off

- [ ] All plan tasks have automated verification or explicit Wave 0 dependencies.
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify.
- [ ] Wave 0 covers all missing validation references.
- [ ] No watch-mode flags.
- [ ] Feedback latency target recorded after runner selection.
- [ ] `nyquist_compliant: true` set in frontmatter after validation strategy and plan mapping are complete.

**Approval:** pending
