---
phase: 04-public-publishing
verified: 2026-10-07T08:51:58Z
status: passed
score: 20/20 must-haves verified
covered_files:
  - .planning/phases/04-public-publishing/04-01-PLAN.md
  - .planning/phases/04-public-publishing/04-01-SUMMARY.md
  - .planning/phases/04-public-publishing/04-02-PLAN.md
  - .planning/phases/04-public-publishing/04-02-SUMMARY.md
  - .planning/phases/04-public-publishing/04-03-PLAN.md
  - .planning/phases/04-public-publishing/04-03-SUMMARY.md
  - .planning/phases/04-public-publishing/04-04-PLAN.md
  - .planning/phases/04-public-publishing/04-04-SUMMARY.md
  - .planning/phases/04-public-publishing/04-05-PLAN.md
  - .planning/phases/04-public-publishing/04-05-SUMMARY.md
  - css/gigpress.css
  - docs/public-template-adoption.md
  - gigpress.php
  - output/feed.php
  - output/gigpress_related.php
  - output/gigpress_shows.php
  - output/ical.php
  - templates/related.php
  - templates/shows-artist-heading.php
  - templates/shows-list-footer.php
  - templates/shows-list-start.php
  - templates/shows-list.php
  - templates/sidebar-artist-heading.php
  - templates/sidebar-list-footer.php
  - templates/sidebar-list.php
  - tests/compat/PUBLIC-BROWSER.md
  - tests/compat/browser-bootstrap.php
  - tests/compat/fixtures/public-publishing/child/gigpress-templates/shows-list.php
  - tests/compat/fixtures/public-publishing/overrides.php
  - tests/compat/fixtures/public-publishing/supplemental.php
  - tests/compat/probe.php
  - tests/compat/public-feeds.php
  - tests/compat/public-html.php
  - tests/compat/public-layout.php
  - tests/compat/public-migrated.php
  - tests/compat/public-publishing.php
  - tests/compat/public-tracer.php
  - tests/compat/run.sh

covered_digest: "v2:sha256:f02a309da550428a5789eb2451e2889e5becc8266788b07de3641eb6884ff603"
behavior_unverified: 0
overrides_applied: 0
human_verification:
  - test: "Choose one: explicitly accept the static assessment that the changed public production paths add no server-side outbound HTTP request or new external provider for feed/calendar output, with Google Calendar and Maps remaining visitor-followed links; or define and run a network-level policy check."
    expected: "A developer-owned record either accepts that boundary or records a concrete check showing the intended privacy boundary."
    why_human: "The PUB-01 prohibition in 04-01-PLAN.md remains unresolved with verification: null. Static inspection is strong evidence but cannot authoritatively interpret or accept the site's privacy-policy boundary."
---

# Phase 04: Public Publishing Verification Report

**Phase Goal:** As a site owner, I want to publish readable show listings while existing public displays, feeds, and theme customizations continue to work, so that visitors can find upcoming shows without breaking the site's existing integrations.

**Verified:** 2026-10-07T08:51:58Z

**Status:** passed — human UAT completed 2026-10-11

**Re-verification:** No — regenerated against the current reports and source; the previous report had no `gaps:` contract to close.

## User Flow Coverage

| User-story step | Expected result | Evidence in current codebase | Status |
| --- | --- | --- | --- |
| Owner publishes migrated shows | The real shortcode, legacy wrapper, widget, related post, RSS, and iCalendar surfaces expose the same persisted records without writes. | `public-publishing.php` dispatches all nine cases; migrated traces assert independent fixture IDs and snapshots. The source-bound six-cell matrix validates against `af7feea`. | ✓ VERIFIED |
| Visitor reads the bundled listing on a phone | At 320 CSS pixels, all show details and links remain available with no page-level horizontal scrolling. | Bundled templates supply labelled rows and scoped responsive CSS; refreshed final-source UAT records 320/320 client/scroll widths and visible details. | ✓ VERIFIED |
| Visitor follows public actions without JavaScript | Ticket, Google Calendar, iCalendar, RSS, and webcal links are ordinary keyboard-operable anchors. | `templates/shows-list.php` emits labelled anchors; the final-source Chrome UAT records Tab/Enter activation with scripts disabled. | ✓ VERIFIED |
| Existing integrations remain compatible | RSS and iCalendar retain their endpoint, membership, filtering, date semantics, and valid serialization. | `output/feed.php` and `output/ical.php` query real rows and serialize prepared values; parsed matrix checks and Apple Calendar UAT cover the required cases. | ✓ VERIFIED |
| A theme owner keeps control of overrides | Child, parent, and `wp-content` templates resolve in established priority; responsive bundled styling is opt-in for owner layouts. | `output/gigpress_shows.php` resolves all three structural templates before setting the bundled marker; override tests and refreshed UAT cover complete and mixed sets. | ✓ VERIFIED |

The user-story format is valid. The observable user outcome is supported by the five verified flow steps. The user explicitly accepted the static privacy assessment in UAT test 22 on 2026-10-11; all 22 UAT rows pass. The report was canonicalized from `human_needed` to `passed` after the UAT-only completion predicate passed.

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
| --- | --- | --- | --- |
| 1 | A migrated 1.4 database publishes the same IDs, relationships, settings, linked post, optional-time/midnight distinction, and expiration meaning through every established public surface. | ✓ VERIFIED | `public-tracer.php` and `public-migrated.php` use real migration fixtures; the current matrix validator reports six cells, nine cases each, 852 assertions, and zero warnings/fatals/plugin errors. |
| 2 | Bundled shows retain saved ticket, Google Calendar, and per-show iCalendar actions as labelled links beside date/time. | ✓ VERIFIED | `templates/shows-list.php` renders the three actions; refreshed no-JavaScript UAT exercised the ticket, Google Calendar, and iCalendar links. |
| 3 | Recognized legacy/current states publish independently of supplemental hostile and layout fixtures. | ✓ VERIFIED | The dispatcher keeps `migrated-contracts` and supplemental fixtures distinct; each matrix cell contains the exact required case set. |
| 4 | Repeated and concurrent public reads preserve tables, options, and linked posts while retaining membership and order. | ✓ VERIFIED | Matrix validation requires unchanged migrated/RSS/iCalendar snapshots; refreshed final fixture recheck recorded unchanged snapshots. |
| 5 | The bundled listing is a labelled date-first block at 320px while retaining its wide table. | ✓ VERIFIED | Bundled-marker CSS and labels are present; final-source UAT measured 320px document/body widths with no overflow, and a 645px wide-table layout at desktop. |
| 6 | Group headings/order, stacked spacing, and status badges retain readable details. | ✓ VERIFIED | `output/gigpress_shows.php` renders headings outside rows and status classes; layout contracts plus final-source UAT cover grouped, sold-out, and cancelled data. |
| 7 | No-time, midnight, multi-day ranges, tickets, and calendar actions retain their established meanings without JavaScript. | ✓ VERIFIED | Real serializer code distinguishes `00:00:01` no-time from midnight; Chrome and Apple Calendar UAT covers no-time, midnight, multi-day, and direct links. |
| 8 | Compact subscriptions, widgets, and related output retain filters/endpoints and theme inheritance. | ✓ VERIFIED | `public-layout.php` checks compact contracts; final-source UAT records wrapped compact rows, Manrope inheritance, and inherited link color. |
| 9 | Bundled responsive CSS does not take over complete or mixed owner templates without explicit adoption. | ✓ VERIFIED | Resolver checks all structural paths before the marker is emitted; complete/mixed child, parent, and `wp-content` cases are tested and observed. |
| 10 | HTML-bearing prepared values remain owner-compatible while plain values are available without rewriting stored data. | ✓ VERIFIED | `gigpress_prepare_plain_values()` adds `plain`; hostile-value/snapshot checks in `public-html.php` cover rendered output and unchanged storage. |
| 11 | Main and related JSON-LD is exact, settings-aware, parseable, and script-safe. | ✓ VERIFIED | `gigpress_json_ld_script()` uses `wp_json_encode` with all HTML-sensitive hex flags; `public-html.php` parses the actual JSON-LD. |
| 12 | Main, widget, related, subscription, and owner-compatible partials encode output for each destination while retaining permitted rich notes. | ✓ VERIFIED | Public HTML contracts parse rendered DOM, URLs, rich notes, and hostile values across those destinations. |
| 13 | Query membership/order, public destinations, hooks, status/ticket behavior, and theme inheritance remain established. | ✓ VERIFIED | Independent fixture expectations are exercised by the migrated, layout, HTML, and feed cases; source fingerprint validation prevents stale evidence. |
| 14 | RSS preserves endpoint/filter/limit/order and valid populated and empty XML. | ✓ VERIFIED | `output/feed.php` performs the public DB query and emits a channel for empty results; `public-feeds.php` independently parses the responses. |
| 15 | iCalendar preserves endpoint/filter/UIDs and valid CRLF, folds, TEXT, date values, midnight, and empty calendars. | ✓ VERIFIED | `output/ical.php` uses typed property helpers; parser checks plus Apple Calendar UAT cover all required time meanings. |
| 16 | RSS and iCalendar use plain prepared values without HTML escaping or public-read writes. | ✓ VERIFIED | Both serializers call `gigpress_prepare()` and read `plain`; migrated snapshot assertions pass in the current source-bound matrix. |
| 17 | The supported WordPress/PHP matrix runs every exact public case without runtime errors. | ✓ VERIFIED | Independent run: `public-evidence validate` passed with WP 7.0.7/7.1.3, PHP 8.3.35/8.4.26/8.5.11, 852 assertions, and zero warnings/fatals/plugin errors. |
| 18 | Matrix evidence fails closed for malformed, stale, incomplete, or overclaimed records. | ✓ VERIFIED | Independent `public-evidence self-test` passed its clean control and 22 named corruptions. |
| 19 | Automated and human evidence bind the same final source, and explicit human approval is required. | ✓ VERIFIED | The matrix validates source `af7feea`; `git diff af7feea..HEAD` contains planning/audit records only, while `04-BROWSER.md` records final-source approval and recheck. |
| 20 | Existing displays, feeds, and theme customizations continue to work as the roadmap requires. | ✓ VERIFIED | This is the merged roadmap contract: all public surfaces, 320px readability, and override resolution have direct matrix/UAT evidence above. |

**Score:** 20/20 truths verified (0 present, behavior-unverified)

### Required Artifacts

| Artifact | Expected | Status | Details |
| --- | --- | --- | --- |
| `tests/compat/public-publishing.php` | Exact fail-closed nine-case dispatcher | ✓ VERIFIED | Substantive registry validates exact case names, readable modules, callbacks, nonempty boolean checks, and aggregate results. Wired by `probe.php`. |
| `public-tracer.php`, `public-migrated.php`, and supplemental fixture | Migrated public-path and independent edge coverage | ✓ VERIFIED | Routed by the dispatcher and exercised by current matrix evidence. |
| `tests/compat/public-layout.php` and override fixture | Layout, compact surface, and resolver-priority contracts | ✓ VERIFIED | It creates isolated complete/mixed overrides, renders the shortcode, checks marker ownership, and cleans up. |
| `gigpress.php`, `output/gigpress_shows.php`, templates, and CSS | Real listing data plus readable bundled layout | ✓ VERIFIED | DB rows flow through `gigpress_prepare()` into resolved templates; bundled-only selectors use the ownership marker. |
| `tests/compat/public-html.php` | Parsed hostile HTML and JSON-LD contract | ✓ VERIFIED | Uses DOM/JSON parsing and independent expected values, then runs through the public dispatcher. |
| `output/feed.php`, `output/ical.php`, and `public-feeds.php` | RSS/iCalendar serialization and parsed contracts | ✓ VERIFIED | Both real endpoints query database rows and produce parsed output; no static-empty fallback is used. |
| `04-PUBLIC-MATRIX.md` and `run.sh` validator | Source/runtime-bound matrix evidence | ✓ VERIFIED | This verification independently ran validator and corruption self-test successfully. |
| `PUBLIC-BROWSER.md`, `04-BROWSER.md`, `04-VALIDATION.md` | Final-source browser acceptance record | ✓ VERIFIED | They describe the procedure, source identity, explicit approved observations, and post-approval recheck. |

### Key Link Verification

| From | To | Via | Status | Details |
| --- | --- | --- | --- | --- |
| `run.sh` | `probe.php` | `public-publishing` scenario/case forwarding | WIRED | Artifact query verified the pattern; probe loads the registry module. |
| `probe.php` | public case registry | Registered module and nonempty named checks | WIRED | `public-publishing.php` returns PASS only for the exact registry and true boolean checks. |
| public tracer | reconstructed 1.4 fixture | Real migration before public callbacks | WIRED | Artifact query verified the fixture link; matrix current-source evidence covers the tracer. |
| show template | `gigpress_prepare()` output | Compatible data keys | WIRED | Template consumes date/time/ticket/calendar `showdata` keys prepared from the queried row. |
| `gigpress_shows.php` | resolved templates | Complete-set ownership decision | WIRED | It resolves start/body/end before adding `gigpress-layout-bundled`. |
| stylesheet | bundled listing marker | Scoped responsive selectors | WIRED | Narrow rules require `.gigpress-layout-bundled`; owner layouts stay outside unless explicitly marked. |
| plain preparation | JSON-LD and feeds | Machine values at destination boundary | WIRED | `plain` is supplied to JSON-LD, RSS, and iCalendar serializers. |
| feeds | independent parsers | XML/ICS contract checks | WIRED | `public-feeds.php` parses actual anonymous output rather than static fixtures. |
| public evidence / fixture lifecycle | matrix report | validate/self-test before retained fixture | WIRED | Direct inspection confirms `run.sh` validates the report and source/runtime identity before start/check. The Plan 05 metadata query was inconclusive only because its `from` field names three files; the implementation link itself is present. |
| browser record | validation record | explicit approval | WIRED | `04-VALIDATION.md` maps the approval and recheck to the matching source record. |

### Data-Flow Trace

| Artifact | Data variable | Source | Produces real data | Status |
| --- | --- | --- | --- | --- |
| Main listing | `$shows` → `$showdata` | WordPress `$wpdb` public show query | Resolved templates render real IDs, dates, links, details, and statuses | ✓ FLOWING |
| JSON-LD | `$showdata['plain']` | Same migrated show row through `gigpress_prepare()` | `wp_json_encode` serializes real Event objects | ✓ FLOWING |
| RSS/iCalendar | `$shows` → `$showdata['plain']` | Endpoint queries with existing filters/limits | XML items and VEVENTs derive from queried rows | ✓ FLOWING |
| Override layout | resolved start/body/end filenames | Child → parent → `wp-content` → bundled resolver | Owner or bundled templates render according to real resolver output | ✓ FLOWING |

### Behavioral Spot-Checks

| Behavior | Command | Result | Status |
| --- | --- | --- | --- |
| Current code matches the six-cell public evidence | `rtk bash tests/compat/run.sh public-evidence --action validate --report .planning/phases/04-public-publishing/04-PUBLIC-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3` | 6 cells, 9 cases/cell, 852 assertions; zero warnings/fatals/plugin errors; source `af7feea` | ✓ PASS |
| Evidence validation fails closed | `rtk bash tests/compat/run.sh public-evidence --action self-test --report .planning/phases/04-public-publishing/04-PUBLIC-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3` | Clean control and all 22 named corruptions rejected | ✓ PASS |
| Final browser, disabled-JS keyboard, and calendar-client behavior | Current source has no implementation change since the recorded `af7feea` UAT | 21 earlier user/UAT passes include 320px/desktop, no-JS keyboard navigation, and Apple Calendar imports for no-time, midnight, and multi-day events | ✓ PASS (recorded human evidence) |

### Probe Execution

No declared `scripts/*/tests/probe-*.sh` probes exist. The phase uses `tests/compat/run.sh`; the non-mutating evidence validator and self-test above provide fresh execution evidence.

### Requirements Coverage

| Requirement | Source Plans | Description | Status | Evidence |
| --- | --- | --- | --- | --- |
| PUB-01 | 04-01 through 04-05 | Existing public displays, feeds, and output contracts continue to work. | ✓ SATISFIED | Migrated dispatcher, HTML/feed parsers, source-bound six-cell matrix, final recheck, and calendar UAT. |
| PUB-02 | 04-01 through 04-05 | 320px readable listing plus preserved theme override contracts. | ✓ SATISFIED | Bundled-only responsive CSS, complete/mixed override contract, measured final-source browser UAT, and no-JS keyboard UAT. |

No Phase 04 requirement is orphaned: every plan declares PUB-01 and PUB-02, which are the two requirements mapped to Phase 4.

### Test Quality Audit

| Test file | Linked requirement | Active | Skipped | Circular | Assertion level | Verdict |
| --- | --- | ---:| ---:| --- | --- | --- |
| `public-tracer.php`, `public-migrated.php` | PUB-01 | Yes | 0 | No | Behavioral | Independent fixture-derived IDs, relationships, public outputs, and snapshots. |
| `public-layout.php`, override fixtures | PUB-02 | Yes | 0 | No | Behavioral | Renders resolved templates and asserts owner/bundled marker behavior. |
| `public-html.php` | PUB-01, PUB-02 | Yes | 0 | No | Behavioral | Parses DOM/JSON and compares hostile values to independent fixtures. |
| `public-feeds.php` | PUB-01 | Yes | 0 | No | Behavioral | Parses RSS/XML/iCalendar values, types, folds, and snapshots. |

No disabled test idiom was found in the requirement-linked PHP test files. The test code writes only disposable runtime fixtures; it does not generate its expected public-output values from the system under test.

### Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
| --- | ---:| --- | --- | --- |
| `css/gigpress.css` | 127 | `#999` cancelled desktop text is below normal-text contrast on white, per the refreshed UI review. | ⚠️ Warning | The 320px selector inherits color and current PUB-02 UAT passed; this remains a desktop accessibility quality item. |
| `templates/shows-artist-heading.php` | 19 | Image-only artist subscription anchors rely on title text and theme focus styles. | ⚠️ Warning | Existing feed links remain functional, but their discoverability and accessible naming need improvement. |

No unreferenced `TBD`, `FIXME`, or `XXX` marker was found in Phase 04 implementation files. The UI review's 17/24 score is advisory for this phase: its warnings do not falsify the actual PUB-01/PUB-02 contracts verified above.

### Decision Coverage

All 16 trackable decisions in `04-CONTEXT.md` are honored by shipped artifacts according to `check.decision-coverage-verify`. The configured key was absent, so the default enabled gate was applied. This is non-blocking corroboration.

### Human Verification Required

**Resolved 2026-10-11:** The user replied `pass` to UAT test 22, explicitly accepting the static assessment below. This closes the human decision; no network-level check was requested or performed. The original verification question and evidence are retained for traceability.

### 1. Resolve the PUB-01 privacy prohibition

**Test:** Choose one: explicitly accept the static assessment that the changed public production paths add no server-side outbound HTTP request or new external provider for feed/calendar output, with Google Calendar and Maps remaining visitor-followed links; or define and run a network-level policy check.

**Expected:** A developer-owned record either accepts that boundary or records a concrete check showing the intended privacy boundary.

**Why human:** `04-01-PLAN.md` leaves this MUST NOT prohibition at `status: unresolved` and `verification: null`. Static inspection found no new production HTTP client in the changed public paths. It also found existing visitor-followed Google Calendar and Maps URLs. Neither fact authorizes the verifier to interpret or accept the site's privacy policy.

### Gaps Summary

There are no implementation gaps in the Phase 04 goal, its three roadmap success criteria, PUB-01, or PUB-02. The sole human decision was resolved by the user's explicit `pass` response to UAT test 22 on 2026-10-11. Canonical status is now `passed`; the original source verification timestamp and digest are unchanged.

---

_Verified: 2026-10-07T08:51:58Z_

_Verifier: the agent (gsd-verifier)_
