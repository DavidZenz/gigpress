---
phase: "03"
slug: administration-workflows
status: verified
threats_open: 0
asvs_level: 1
created: 2026-10-05
---

# Phase 03 — Security

## Trust Boundaries

WordPress browser/forms to guarded handlers and Settings API; received values to SQL/HTML; local runner to private owned loopback Compose fixtures; runtime results to source-bound evidence verdicts.

## Threat Register

| Threat ID | Category | Component | Severity | Disposition | Mitigation evidence | Status |
|-----------|----------|-----------|----------|-------------|---------------------|--------|
| T-03-01 | Elevation | add/update show handlers | high | mitigate | admin/handlers.php:175 shared capability, nonce and readiness before preparation/writes; administration-entry denied snapshots | closed |
| T-03-02 | Tampering | calendar/time/mode/association input | high | mitigate | admin/handlers.php:37/42/65/116 exact checkdate, precedence and scalar/domain validation before side effects | closed |
| T-03-03 | Tampering | recovered form values and notices | high | mitigate | admin/new.php:12/26/168/195 and handlers.php:246 destination encoding; hostile recovery/label checks | closed |
| T-03-04 | Repudiation | related creation and final show outcome | medium | mitigate | admin/handlers.php:114/160/167/215-242 structured completed IDs and strict write/read-back; retry tests | closed |
| T-03-05 | Repudiation | administration registry/result contract | medium | mitigate | probe.php:39/49/64 exact registry and positive checks; run.sh:1231 rejects empty/incomplete/error records | closed |
| T-03-SC | Tampering | package-manager installation | high | mitigate | gigpress.php:123 core jQuery/sortable; scoped diff has no dependencies/manifests/install commands | closed |
| T-03-06 | Elevation | options.php settings save | high | mitigate | gigpress.php:101/462 manage_options/registered Settings API; settings.php:67 real options.php fields; HTTP negative snapshots | closed |
| T-03-07 | Tampering | settings sanitizer/hidden metadata | high | mitigate | gigpress.php:468 stored baseline/context-only editable overlay; administration-settings.php:139 forged/malformed metadata tests | closed |
| T-03-08 | Tampering | settings markup and help/examples | high | mitigate | settings.php:85/102/109/114/116/120 destination encoding/associations; hostile fixtures and help examples | closed |
| T-03-09 | Repudiation | unchecked/unknown and programmatic updates | medium | mitigate | settings.php:90/97 explicit zero/current choices; gigpress.php:477/492 programmatic preservation; repeated/sticky saves | closed |
| T-03-10 | Elevation | confirmed trash handler | high | mitigate | handlers.php:428/457-465 POST/stage/capability/nonce/readiness, owner/expiry/exact IDs, intent consume before writes | closed |
| T-03-11 | Tampering | selected-ID confirmation | high | mitigate | handlers.php:320/383/458 bounded positive scalar IDs/deduplication/exact equality; shows.php explicit row IDs | closed |
| T-03-12 | Tampering | list domains/query/navigation | high | mitigate | handlers.php:325/367 domain allowlist/prepared relationships/integer LIMIT; shows.php:50/72/98 encoded links | closed |
| T-03-13 | Repudiation | bulk outcomes/undo | high | mitigate | handlers.php:473/496-499 previous-row predicates, strict write/read-back and changed-only Undo; mixed/stale tests | closed |
| T-03-14 | Tampering | confirmation/result HTML | high | mitigate | handlers.php:417/499 encoded preview/results; shows.php:82/92/99 semantic headings/labels; hostile identities | closed |
| T-03-15 | Denial | empty/stale/out-of-range list requests | medium | mitigate | handlers.php:374/412/443 safe clamping/reselect; shows.php:37/111 narrow reset/no-results; empty selection tests | closed |
| T-03-16 | Information | browser fixture/session file | high | mitigate | browser-bootstrap.php:222 synthetic users; compose.browser.yaml:9 loopback; run.sh:1018 private random credentials | closed |
| T-03-17 | Tampering | fixture cleanup/project metadata | high | mitigate | run.sh:1000/1048 private same-user repository/project/marker metadata and container labels; 1028 environment scrub; read-only mount | closed |
| T-03-18 | Elevation | actual HTTP show/settings/trash requests | high | mitigate | browser-bootstrap.php:34/82 real login/cookies; 121-145 actual options.php; 157-218 issued nonce/intent negatives | closed |
| T-03-19 | Repudiation | aggregate/evidence record | high | mitigate | run.sh:608/624/663 exact case/check/runtime/image/source validation; 704-735 twenty real corruption copies plus clean control | closed |
| T-03-20 | Denial | fixture interruption/leaked services | medium | mitigate | run.sh:1032/1052/1056 owned EXIT/INT/TERM teardown; ADMIN-BROWSER.md start/status/stop and final cleanup | closed |

## Accepted Risks Log

No accepted risks. All 21 authored mitigation dispositions are closed from implementation evidence (16 high, five medium). Blocking threshold: high; blocking and nonblocking open counts are both zero.

## Security Audit Trail

| Audit Date | Threats Total | Closed | Open | Run By |
|------------|---------------|--------|------|--------|
| 2026-10-05 | 21 | 21 | 0 | gsd-security-auditor; parent report writer |

The independent auditor read the authored register, all plans/summaries, changed production/harness source and reported evidence. It verified the exact planned threats at ASVS L1, without inventing a new register or accepting risks. All summary threat flags map to authored IDs. Production/harness source matches edf959e; browser procedure documentation changed only.

This source-level security verdict does not certify pending genuine no-JS/full keyboard browser acceptance or a final unobserved live Docker inventory. The measured matrix, parent HTTP smoke and browser session recorded owned cleanup. A redundant pull/diagnostic was terminated with exit137 and is not counted as a passed run.

## Sign-Off

- [x] All authored threats have verified mitigations.
- [x] No accepted risks or unregistered threat flags.
- [x] threats_open: 0 at high threshold.
- [x] status: verified at ASVS level 1.

Approval: verified 2026-10-05. Phase acceptance remains governed by goal verification and pending browser checks.

## Post-review source supplement

Parent verification at source f3f1781: the original independent 21-row audit above is preserved as historical authored-threat evidence. Subsequent fixes strengthen T-03-02/03/08/17/18/20: normalize required fields before writes with exact raw recovery; escape feed discovery title/URL; preserve legacy control values; POST/configured-capability/action-nonce/readiness protect artist reorder, with bounded positive existing IDs and subset SQL; retain private cleanup metadata/logs on unconfirmed teardown and permit verified partial-resource retry. Actual focused WordPress checks and 54 HTTP checks passed, including subscriber/invalid-nonce/GET zero-write snapshots and exact subset artist read-back. Five controlled cleanup contract checks passed.

Both Artists and Venues now load their dependency guard for normal page rendering, verified by actual HTTP; this does not alter dependency deletion policy. Parent checked the changed sinks/guards against the existing authored controls. This supplement is an inline source/test recheck, not a second independent audit. The current matrix and goal verification govern final source acceptance.

The source review discovered inherited CSV notice injection, tour conversion write-loss and CSV status formatting defects outside the authored administration threat register. They are unresolved required Phase 05 work in CSV-REVIEW-FOLLOWUPS.md. The scoped SECURED 21/21 verdict does not certify those unregistered workflows or imply a clean plugin-wide security review. No accepted risk is fabricated.
