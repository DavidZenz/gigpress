---
phase: "04"
slug: "public-publishing"
status: verified
threats_open: 0
asvs_level: 1
created: "2026-10-06"
---

# Phase 04 — Security

> Per-phase security contract: threat register, accepted risks, and audit trail.

## Trust Boundaries

| Boundary | Description | Data Crossing |
|----------|-------------|---------------|
| Fixture manifests → disposable WordPress database | Reconstructed event data is loaded for public-read verification. | Show records, posts, options, and schema state |
| Anonymous requests → public renderers and feeds | Public scalar query values select existing read behavior. | Query parameters and stored show values |
| Stored values → HTML, JSON-LD, RSS, and iCalendar | Hostile text crosses browser and machine-readable format boundaries. | Text, URLs, rich notes, line breaks, and Unicode |
| Template overrides → bundled responsive styles | The plugin must not claim ownership of theme-controlled templates. | Paths, ownership markers, CSS classes |
| Fixture session → Compose resources and acceptance evidence | Private session metadata controls test resources and source-bound acceptance. | Session credentials, resource identity, source fingerprints, and human results |

## Threat Register

| Threat ID | Category | Component | Severity | Disposition | Mitigation | Status |
|-----------|----------|-----------|----------|-------------|------------|--------|
| T-04-01 | Tampering | Owned compatibility fixture | high | mitigate | Validate repository, Compose project, session ownership, and resource identity before lifecycle actions; final cleanup removed owned containers and volumes. Evidence: `04-05-SUMMARY.md`, `04-VALIDATION.md` (04-05-01/03). | closed |
| T-04-02 | Tampering | Public reads over migrated data | high | mitigate | Compare independent data snapshots before and after sequential and concurrent public reads. Evidence: `04-VALIDATION.md` (04-01-01/02, 04-05-03). | closed |
| T-04-03 | Denial of Service | Public query and case dispatch | medium | mitigate | Restrict dispatch and query inputs to scalar/allow-listed values; reject missing or unknown cases and keep diagnostics out of response bodies. Evidence: `04-01-SUMMARY.md`, `04-VALIDATION.md` (04-01-01/02). | closed |
| T-04-04 | Tampering | Template override ownership | high | mitigate | Resolve full structural override sets before adoption and verify child, parent, wp-content, mixed, and bundled priority behavior. Evidence: `04-02-SUMMARY.md`, `04-VALIDATION.md` (04-02-01/02, 04-05-02). | closed |
| T-04-05 | Denial of Service | Narrow public listing | medium | mitigate | Keep event details and actions visible, wrap long tokens, and verify real 320 CSS-pixel and desktop layouts. Evidence: user-approved final-source checks in `04-BROWSER.md`. | closed |
| T-04-07 | Tampering | HTML renderers and bundled partials | high | mitigate | Preserve plain values separately, contextually escape text/attributes/URLs, and sanitize only permitted rich notes. Hostile-value checks passed across public destinations. Evidence: `04-VALIDATION.md` (04-03-01/02/03, 04-05-01). | closed |
| T-04-08 | Tampering | Main and related JSON-LD | high | mitigate | Encode plain event data with WordPress JSON encoding and HTML-sensitive flags; parse values and verify script-container integrity. Evidence: `04-VALIDATION.md` (04-03-01/02, 04-05-01). | closed |
| T-04-09 | Tampering | Saved ticket, calendar, and subscription URLs | high | mitigate | Encode URL components once, apply WordPress protocol filtering and final attribute escaping, and verify executable schemes are absent. Evidence: `04-VALIDATION.md` (04-03-01/02/03, 04-05-01). | closed |
| T-04-10 | Tampering | RSS and iCalendar serialization | high | mitigate | Serialize XML and typed iCalendar properties; independently parse decoded values, line-injection cases, CDATA, UTF-8 folds, and date/time property types. Evidence: `04-VALIDATION.md` (04-04-01/02, 04-05-01/03). | closed |
| T-04-11 | Tampering | Feed membership and event UIDs | medium | mitigate | Preserve allow-listed scalar filters, limits, ordering, and UID construction; verify exact fixture-derived membership. Evidence: `04-VALIDATION.md` (04-04-01/02, 04-05-01). | closed |
| T-04-12 | Repudiation | Public compatibility evidence | high | mitigate | Bind cases to tracked source fingerprints and pinned runtime identities; reject missing, duplicate, empty, failed, stale, or corrupted evidence. Six cells, 798 assertions, and 22 corruption checks passed. Evidence: `04-PUBLIC-MATRIX.md`, `04-VALIDATION.md` (04-04-03/04-05-01). | closed |
| T-04-13 | Information Disclosure | Fixture session and evidence metadata | high | mitigate | Restrict session files/directories, reject symlinks and wrong ownership/repository, redact credentials, bind the fixture to loopback, and validate before teardown. Evidence: `tests/compat/run.sh`, `04-05-SUMMARY.md` (04-05-01/03). | closed |
| T-04-14 | Repudiation | Browser evidence and final validation | high | mitigate | Require human approval against final source/runtime/browser/client identity, then recheck the unchanged source before accepting evidence. User-approved observations and the matching final fixture fingerprint are recorded in `04-BROWSER.md` and `04-VALIDATION.md`. | closed |
| T-04-SC | Tampering | Third-party package installation | high | mitigate | No npm, pip, or Cargo installation was introduced; the plan requires a package-legitimacy review and human gate if scope adds one. Evidence: Phase 04 plans and summaries. | closed — scope condition not triggered |

*Status: open · closed · open — below high threshold (non-blocking)*
*Severity: critical > high > medium > low; only open threats at or above the blocking threshold count toward `threats_open`.*
*Disposition: mitigate (implementation required) · accept (documented risk) · transfer (third-party).* 

## Accepted Risks Log

No accepted risks.

## Security Audit Trail

| Audit Date | Threats Total | Closed | Open | Run By |
|------------|---------------|--------|------|--------|
| 2026-10-06 | 14 | 14 | 0 | Codex — ASVS 5.0 Level 1 |

## Sign-Off

- [x] All threats have a disposition (mitigate / accept / transfer)
- [x] Accepted risks documented in Accepted Risks Log
- [x] `threats_open: 0` confirmed
- [x] `status: verified` set in frontmatter

**Approval:** verified 2026-10-06
