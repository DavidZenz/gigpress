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

Exact plan/task map. Every NEW command is created by the same task or an earlier dependency before it is used.

| Plan / task | Requirement | Threat refs | Automated evidence | Human/browser evidence | Command dependency | Status |
|---|---|---|---|---|---|---|
| 04-01-01 migrated 1.4 tracer | PUB-01, PUB-02 | T-04-01, T-04-02, T-04-03 | NEW `cell --scenario public-publishing --case tracer-1.4`; inherited `upgrade-preservation --case all` | None | Creates public scenario/registry and tracer before invocation | Pending — W0 |
| 04-01-02 recognized versions/current state | PUB-01 | T-04-02, T-04-03 | `cell --scenario public-publishing --case migrated-contracts` | None | Uses 04-01-01 registry/dispatch | Pending — W0 |
| 04-02-01 bundled main/compact layout | PUB-01, PUB-02 | T-04-04, T-04-05 | `layout-main`, `layout-compact` public cells | Deferred to final-source 04-05-02 | Uses 04-01 public scenario; creates layout module/cases | Pending — W0 |
| 04-02-02 override isolation/adoption | PUB-02 | T-04-04 | `override-priority` public cell | Deferred to final-source 04-05-02 | Uses 04-01 public scenario and 04-02-01 ownership marker | Pending — W0 |
| 04-03-01 main HTML/JSON-LD | PUB-01 | T-04-07, T-04-08, T-04-09 | `html-json` public cell with DOM/JSON parsing | None | Uses 04-01 scenario; creates html module/case | Pending — W0 |
| 04-03-02 related/widget destinations | PUB-01 | T-04-07, T-04-08, T-04-09 | Expanded `html-json` public cell | None | Uses 04-03-01 module/case | Pending — W0 |
| 04-03-03 bundled partial destinations | PUB-01, PUB-02 | T-04-07, T-04-09 | Expanded `html-json` public cell | None | Uses 04-03-01 module/case and 04-02 layout contract | Pending — W0 |
| 04-04-01 RSS | PUB-01 | T-04-10, T-04-11 | `rss-contract` public cell with independent XML parser | None | Uses 04-01 registry; creates feed module/RSS case | Pending — W0 |
| 04-04-02 iCalendar and empty feeds | PUB-01 | T-04-10, T-04-11 | `ical-contract`, `empty-contracts` public cells with independent property/XML parsers | Final calendar-client item is required by blocking Task 04-05-02 | Uses 04-04-01 feed module | Pending — W0 |
| 04-04-03 supported matrix/evidence seal | PUB-01, PUB-02 | T-04-01, T-04-02, T-04-12 | NEW public matrix plus `public-evidence` build/validate/self-test and inherited preservation aggregate | Human evidence remains explicitly incomplete until blocking Task 04-05-02; no 04-BROWSER.md input is required at this stage | Creates `public-evidence` actions before invocation; all nine cases created by prior tasks; Plan 04-05 must refresh this report after its fingerprinted edits | Pending — W0 |
| 04-05-01 final-source public fixture | PUB-01, PUB-02 | T-04-13, T-04-14 | After all fixture-source edits: exact supported `matrix --case all`, `public-evidence build`, `validate`, `self-test`, then NEW `public-fixture --action start --case all` against the rebuilt fingerprint | Prepares retained fixture only; no acceptance inferred | Runs after 04-04 and all nine modules; refreshes 04-PUBLIC-MATRIX.md after modifying fingerprinted runner/bootstrap/Compose files, then starts fixture only on matching identity | Pending — W0 |
| 04-05-02 blocking final human acceptance | PUB-01, PUB-02 | T-04-14 | None; automated source/HTTP evidence is supporting context only | Required exact 320px/wide/readability/bounds, computed inheritance, complete/mixed overrides, keyboard, actual disabled-browser-JS and calendar-client approval | `checkpoint:human-verify gate=blocking-human`; unavailable evidence blocks phase acceptance | Pending — blocking human |
| 04-05-03 final recheck/cleanup/validation | PUB-01, PUB-02 | T-04-13, T-04-14 | `public-fixture check --case all` then owned `stop` | Consumes explicit 04-05-02 approval only | Runs after human approval against unchanged final source | Pending — W0 |

## Wave 0 Requirements

- [ ] 04-01-01 creates the exact nine-case `public-publishing` registry, lazy selected-case dispatch and `tracer-1.4`; missing selected modules and empty/unknown selected cases fail without requiring later modules.
- [ ] 04-01-02 creates `migrated-contracts` for every recognized Phase 02 source/current state, same-runtime public reads and independent unchanged snapshots; supplemental data remains separate.
- [ ] 04-02-01/02 create `layout-main`, `layout-compact`, `override-priority`, bundled-only marker isolation and complete/mixed child/parent/wp-content fixtures.
- [ ] 04-03-01 creates `html-json`; 04-03-02/03 expand it across main/widget/related/partials with DOM/JSON exact-value checks.
- [ ] 04-04-01/02 create independent RSS/XML and iCalendar property parsers for `rss-contract`, `ical-contract`, `empty-contracts`.
- [ ] 04-04-03 creates the exact pinned matrix and `public-evidence` build/validate/self-test, including named missing/duplicate/unknown/empty/failed/stale/source/runtime corruptions.
- [ ] 04-05-01 creates the owned loopback `public-fixture` lifecycle, then after every fingerprinted fixture-source edit reruns the exact supported matrix plus `public-evidence` build/validate/self-test and starts the retained fixture only when its identity matches the refreshed 04-PUBLIC-MATRIX.md.
- [ ] 04-05-02 blocks phase acceptance until every final-source browser/calendar-client observation is explicitly human-approved; unavailable evidence is a blocker.
- [ ] 04-05-03 rechecks the unchanged approved source, verifies cleanup and updates compliance status from actual evidence only.
- [ ] No new test framework/package is required. Docker engine access remains an execution precondition; a denied research socket read is not evidence that OrbStack is unavailable.

## Manual-Only Verifications

All rows below are owned by blocking-human Task 04-05-02 after every fingerprinted production or fixture-source change and after Task 04-05-01 refreshes automated evidence. A missing observer or calendar client blocks phase acceptance; it is not an accepted pending state. Any later fingerprinted fix invalidates both matrix and browser evidence and requires Tasks 04-05-01 and 04-05-02 again.

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
