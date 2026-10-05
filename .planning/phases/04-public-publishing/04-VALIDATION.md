---
phase: "04"
slug: "public-publishing"
status: draft
nyquist_compliant: false
wave_0_complete: false
created: "2026-10-05"
---

# Phase 04 — Validation Strategy

## Test Infrastructure

| Property | Value |
|---|---|
| Framework | Existing shell runner and PHP probes in owned WordPress/OrbStack containers |
| Configuration | `tests/compat/compose.yaml`, `tests/compat/compose.browser.yaml` |
| Quick command | NEW: `rtk proxy bash tests/compat/run.sh public-fixture --action check --session "$PUBLIC_SESSION" --case tracer-1.4` |
| Full command | NEW: `rtk proxy bash tests/compat/run.sh matrix --scenario public-publishing --case all --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL` |
| Runtime | Retained-fixture check target <30 seconds, unmeasured; cold starts and matrix runs have no 30-second claim |

Both public commands require implementation before use. `PUBLIC_SESSION` is private executor-issued fixture metadata, never committed. Existing fresh full-workflows assertions supplement migrated-data evidence. No test was run during planning.

## Sampling Rate

- After each implementation task, use its focused automated case and require nonempty named true assertions and zero plugin errors.
- After each wave, run the relevant migrated public cases and preservation regression for shared code changes.
- Before verify-work, require the pinned public matrix and separate actual browser/manual evidence.
- Target fast feedback <30 seconds on an already retained fixture; record actual timing during execution.

## Per-Task Verification Map

Provisional slice map; the planner must replace slice labels with exact plan/task IDs, dependencies and threat references.

| Slice | Requirement | Secure behavior | Test type | Planned evidence | Exists | Status |
|---|---|---|---|---|---|---|
| Migrated publishing tracer | PUB-01, PUB-02 | Owned isolated fixture; exact expected IDs, unchanged snapshots | Integration | NEW tracer-1.4 and override marker cases | No — W0 | Pending |
| Public destination encoding | PUB-01 | Inert hostile text/URLs; intact allowed rich notes | Integration/parser | NEW HTML/RSS/ICS/JSON-LD cases | No — W0 | Pending |
| Bundled layout and links | PUB-02 | All details/actions available with readable status | Integration + browser | NEW layout cases plus measured 320px, keyboard/no-JS observation | No — W0 | Pending |
| Contracts and full matrix | PUB-01, PUB-02 | Nonempty evidence, exact case/runtime/source identity, preserved data | Matrix + validator | NEW pinned public matrix and fail-closed evidence validation | No — W0 | Pending |

## Wave 0 Requirements

- [ ] NEW public-publishing scenario/case modules and runner/probe allow-list registration.
- [ ] Reuse recognized Phase02 legacy fixtures, migrate real populated data, and compare independent preservation snapshots; supplemental adversarial fixtures remain separate.
- [ ] NEW retained public fixture operations and source/runtime ownership checks; preserve safe cleanup.
- [ ] NEW complete and mixed child/parent/content overrides with variable/hook assertions.
- [ ] NEW independent format parsers/assertions and malformed request/value cases.
- [ ] NEW public browser pages/procedure and evidence validator, including missing/duplicate/failing-result rejection.
- [ ] No new test framework is required. Docker engine access remains an execution prerequisite; research's sandbox-denied read is not evidence that OrbStack is unavailable.

## Manual-Only Verifications

| Behavior | Requirement | Why manual/browser evidence | Instructions |
|---|---|---|---|
| Main listing at exactly 320 CSS pixels | PUB-02 | HTTP/source cannot establish readability or scroll bounds | Measure actual viewport and document/body scroll widths; inspect long labels, grouped shows, details, statuses, wide table and narrow theme containers; retain screenshots |
| Keyboard and actual disabled-JavaScript calendar use | PUB-02 | Plugin disable_js and HTTP checks do not disable browser scripts | Disable scripts in the browser, reload, Tab/Enter through date-area calendar links, ticket and subscriptions; record setting and outcomes |
| Theme inheritance, compact widgets/related and custom/mixed overrides | PUB-02 | Source does not prove computed styles/layout | Observe computed fonts/link colors and complete/mixed override layouts at narrow/wide widths |
| Downloaded iCalendar interoperability | PUB-01 | Parser success alone does not show client import | Open generated file in an available calendar client and record preserved date/time/no-time behavior |

## Validation Sign-Off

- [ ] All tasks have automated verification or explicit W0 dependencies; manual observations remain separate.
- [ ] No three consecutive implementation tasks lack automated verification.
- [ ] Every NEW command is created before use and fails on empty evidence.
- [ ] No watch flags; measured feedback latency recorded.
- [ ] Actual browser evidence and populated migrated matrix completed.
- [ ] `nyquist_compliant: true` only after validation.

**Approval:** pending execution; draft planning contract.
