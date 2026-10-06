---
phase: "04"
slug: "public-publishing"
status: validated
nyquist_compliant: true
wave_0_complete: true
created: "2026-10-05"
---

# Phase 04 — Validation Strategy

## Test Infrastructure

| Property | Value |
|---|---|
| Framework | Existing shell runner and PHP probes in owned WordPress/OrbStack containers |
| Configuration | `tests/compat/compose.yaml`, `tests/compat/compose.browser.yaml` |
| Quick command | `rtk proxy bash tests/compat/run.sh public-fixture --action check --session "$PUBLIC_SESSION" --case all` — measured 7.7 seconds on the retained fixture |
| Full command | NEW: `rtk proxy bash tests/compat/run.sh matrix --scenario public-publishing --case all --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL` |
| Runtime | Retained-fixture check target <30 seconds; final check completed in 7.7 seconds. Matrix build took 119 seconds across six cells; no cold-start 30-second claim. |

Both public commands require implementation before use. `PUBLIC_SESSION` is private executor-issued fixture metadata, never committed. Existing fresh full-workflows assertions supplement migrated-data evidence. No test was run during planning.

## Sampling Rate

- After each implementation task, use its focused automated case and require nonempty named true assertions and zero plugin errors.
- After each wave, run the relevant migrated public cases and preservation regression for shared code changes.
- Before verify-work, require the pinned public matrix and separate actual browser/manual evidence.
- Target fast feedback <30 seconds on an already retained fixture; record actual timing during execution.

## Per-Task Verification Map

Exact plan/task map. Every NEW command is created by the same task or an earlier dependency before it is used.

| Plan / task | Requirement | Threat refs | Automated evidence | Human/browser evidence | Command dependency | Status |
|---|---|---|---|---|---|---|
| 04-01-01 migrated 1.4 tracer | PUB-01, PUB-02 | T-04-01, T-04-02, T-04-03 | `cell --scenario public-publishing --case tracer-1.4`; inherited `upgrade-preservation --case all` | None | Creates public scenario/registry and tracer before invocation | PASS — 19 named assertions; 11 inherited preservation cases pass |
| 04-01-02 recognized versions/current state | PUB-01 | T-04-02, T-04-03 | `cell --scenario public-publishing --case migrated-contracts` | None | Uses 04-01-01 registry/dispatch | PASS — seven source states, 11 aggregate checks, unchanged snapshots |
| 04-02-01 bundled main/compact layout | PUB-01, PUB-02 | T-04-04, T-04-05 | `layout-main`, `layout-compact` public cells | Deferred to final-source 04-05-02 | Uses 04-01 public scenario; creates layout module/cases | PASS — 11 + 7 named checks; final-source visual review passed |
| 04-02-02 override isolation/adoption | PUB-02 | T-04-04 | `override-priority` public cell | Deferred to final-source 04-05-02 | Uses 04-01 public scenario and 04-02-01 ownership marker | PASS — 11 named checks; final-source override review passed |
| 04-03-01 main HTML/JSON-LD | PUB-01 | T-04-07, T-04-08, T-04-09 | `html-json` public cell with DOM/JSON parsing | None | Uses 04-01 scenario; creates html module/case | PASS — focused 27-assertion check and final six-cell matrix |
| 04-03-02 related/widget destinations | PUB-01 | T-04-07, T-04-08, T-04-09 | Expanded `html-json` public cell | None | Uses 04-03-01 module/case | PASS — expanded output contract in all six final matrix cells |
| 04-03-03 bundled partial destinations | PUB-01, PUB-02 | T-04-07, T-04-09 | Expanded `html-json` public cell | None | Uses 04-03-01 module/case and 04-02 layout contract | PASS — bundled destinations in all six final matrix cells |
| 04-04-01 RSS | PUB-01 | T-04-10, T-04-11 | `rss-contract` public cell with independent XML parser | None | Uses 04-01 registry; creates feed module/RSS case | PASS — RSS contract in all six final matrix cells |
| 04-04-02 iCalendar and empty feeds | PUB-01 | T-04-10, T-04-11 | `ical-contract`, `empty-contracts` public cells with independent property/XML parsers | Final calendar-client item is satisfied in 04-05-02 | Uses 04-04-01 feed module | PASS — iCalendar and empty contracts in all six final matrix cells; client import accepted |
| 04-04-03 supported matrix/evidence seal | PUB-01, PUB-02 | T-04-01, T-04-02, T-04-12 | Public matrix plus `public-evidence` build/validate/self-test and inherited preservation aggregate | Final-source evidence recorded in 04-BROWSER.md | Creates `public-evidence` actions before invocation; all nine cases created by prior tasks; Plan 04-05 refreshes report after its fingerprinted edits | PASS — six cells, nine cases per cell, 798 assertions, 22 corruption checks, 11 preservation cases |
| 04-05-01 final-source public fixture | PUB-01, PUB-02 | T-04-13, T-04-14 | Exact supported `matrix --case all`, `public-evidence build`, `validate`, `self-test`, then `public-fixture --action start --case all` | Prepares retained fixture only; no acceptance inferred | Runs after 04-04 and all nine modules; refreshes 04-PUBLIC-MATRIX.md after fingerprinted runner/bootstrap/Compose edits | PASS — retained fixture source revision and fingerprint matched the rebuilt report |
| 04-05-02 blocking final human acceptance | PUB-01, PUB-02 | T-04-14 | None; automated source/HTTP evidence is supporting context only | 320px/wide bounds, computed inheritance, complete/mixed overrides, keyboard with browser JS disabled, and calendar-client import | `checkpoint:human-verify gate=blocking-human`; unavailable evidence blocks phase acceptance | PASS — user approved final-source browser checks and reported importing/checking all three calendar date cases on 2026-10-06 |
| 04-05-03 final recheck/cleanup/validation | PUB-01, PUB-02 | T-04-13, T-04-14 | `public-fixture check --case all` then owned `stop` | Consumes explicit 04-05-02 approval only | Runs after human approval against unchanged final source | PASS — 13 final assertions, unchanged snapshots, matching fingerprint; services and volumes removed |

## Wave 0 Requirements

- [x] 04-01-01 creates the exact nine-case `public-publishing` registry, lazy selected-case dispatch and `tracer-1.4`; missing selected modules and empty/unknown selected cases fail without requiring later modules.
- [x] 04-01-02 creates `migrated-contracts` for every recognized Phase 02 source/current state, same-runtime public reads and independent unchanged snapshots; supplemental data remains separate.
- [x] 04-02-01/02 create `layout-main`, `layout-compact`, `override-priority`, bundled-only marker isolation and complete/mixed child/parent/wp-content fixtures.
- [x] 04-03-01 creates `html-json`; 04-03-02/03 expand it across main/widget/related/partials with DOM/JSON exact-value checks.
- [x] 04-04-01/02 create independent RSS/XML and iCalendar property parsers for `rss-contract`, `ical-contract`, `empty-contracts`.
- [x] 04-04-03 creates the exact pinned matrix and `public-evidence` build/validate/self-test, including named missing/duplicate/unknown/empty/failed/stale/source/runtime corruptions.
- [x] 04-05-01 creates the owned loopback `public-fixture` lifecycle, then after every fingerprinted fixture-source edit reruns the exact supported matrix plus `public-evidence` build/validate/self-test and starts the retained fixture only when its identity matches the refreshed 04-PUBLIC-MATRIX.md.
- [x] 04-05-02 blocks phase acceptance until every final-source browser/calendar-client observation is explicitly human-approved; all required observations passed.
- [x] 04-05-03 rechecks the unchanged approved source, verifies cleanup and updates compliance status from actual evidence only.
- [x] No new test framework/package is required. Docker engine access remains an execution precondition; a denied research socket read is not evidence that OrbStack is unavailable.

## Manual-Only Verifications

All rows below are owned by blocking-human Task 04-05-02 after every fingerprinted production or fixture-source change and after Task 04-05-01 refreshes automated evidence. A missing observer or calendar client blocks phase acceptance; it is not an accepted pending state. Any later fingerprinted fix invalidates both matrix and browser evidence and requires Tasks 04-05-01 and 04-05-02 again.

| Behavior | Requirement | Why manual/browser evidence | Instructions | Result |
|---|---|---|---|---|
| Main listing at exactly 320 CSS pixels and wide layout | PUB-02 | HTTP/source cannot establish readability or scroll bounds | Measure actual viewport and document/body scroll widths; inspect long labels, grouped shows, details, statuses, wide table and narrow theme containers | PASS — user confirmed final-source 320px and desktop views; see 04-BROWSER.md |
| Keyboard with browser JavaScript disabled | PUB-02 | Plugin disable_js and HTTP checks do not disable browser scripts | Disable scripts in the browser, reload, Tab/Enter through date-area calendar links, ticket and subscriptions | PASS — ticket, Google Calendar, iCalendar download, RSS, and webcal keyboard activation observed; see 04-BROWSER.md |
| Theme inheritance, compact widgets/related and custom/mixed overrides | PUB-02 | Source does not prove computed styles/layout | Observe computed fonts/link colors and complete/mixed override layouts at narrow/wide widths | PASS — user confirmed final-source review; see 04-BROWSER.md |
| Downloaded iCalendar interoperability | PUB-01 | Parser success alone does not show client import | Open generated file in an available calendar client and record preserved date/time/no-time behavior | PASS — user reports importing in Apple Calendar and checking no-time, actual midnight, and all-day multi-day cases; see 04-BROWSER.md |

## Validation Sign-Off

- [x] All tasks have automated verification or explicit W0 dependencies; manual observations remain separate.
- [x] No three consecutive implementation tasks lack automated verification.
- [x] Every NEW command is created before use and fails on empty evidence.
- [x] No watch flags; measured feedback latency recorded (final retained-fixture check: 7.7 seconds; matrix: 119 seconds across six cells).
- [x] Actual browser evidence and populated migrated matrix completed.
- [x] `nyquist_compliant: true` only after validation.

**Approval:** validated on 2026-10-06 after final-source user acceptance, unchanged-source fixture recheck, and owned-resource cleanup.

## Validation Audit 2026-10-06

| Metric | Count |
|--------|-------|
| Gaps found | 0 |
| Resolved | 0 |
| Escalated | 0 |
