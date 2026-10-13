---
phase: "01"
slug: "compatibility-baseline-and-menu-diagnosis"
status: verified
threats_open: 0
asvs_level: 1
created: "2026-10-13"
---

# Phase 01 — Security

> Per-phase security contract: threat register, accepted risks, and audit trail.

---

## Trust Boundaries

| Boundary | Description | Data Crossing |
|----------|-------------|---------------|
| Checkout to OrbStack containers | Repository code and fixtures execute inside official WordPress, PHP, and database images. | Plugin source, synthetic fixtures, runtime images |
| Probe to WordPress database | Test mutations cross into a database that must remain isolated from real installations. | Synthetic show, artist, venue, and settings data |
| Controlled fixture to repository evidence | Synthetic callback evidence could be mistaken for evidence about an unavailable production installation. | Callback identity, priority, and menu slugs |
| WordPress callback registry to diagnostic output | Callable identities and menu state may disclose filesystem or plugin details. | Redacted callback metadata and menu slugs |
| Host runtime to plugin bootstrap | PHP version and plugin state decide whether GigPress continues loading. | Runtime version and active-plugin state |
| Compatibility notice to WordPress administrator | Compatibility details appear in privileged admin output. | Escaped, capability-scoped notice text |
| GigPress and third-party callbacks to WordPress menu state | Callbacks receive and return ordered menu slugs used for privileged navigation. | Menu order and known slugs |
| Runtime logs and workflow fixtures to compatibility claims | Test evidence is summarized as public compatibility metadata. | Exact releases, image IDs, result records, and checksums |

---

## Threat Register

| Threat ID | Category | Component | Severity | Disposition | Mitigation | Status |
|-----------|----------|-----------|----------|-------------|------------|--------|
| T-01-01 | Tampering | `tests/compat/compose.yaml`, container images | medium | mitigate | The refreshed matrix uses official WordPress/PHP images, records image IDs and per-result SHA-256 checksums, and passes on all six supported cells. | closed |
| T-01-02 | Tampering | `tests/compat/run.sh`, database target | high | mitigate | The compatibility harness uses isolated Compose projects and databases, rejects external targets, mounts the checkout read-only, and cleans up; fresh runner self-validation and matrix checks passed. | closed |
| T-01-03 | Information Disclosure | `tests/compat/diagnostics/menu-trace.php` | medium | mitigate | Diagnostic output is request-local and redacted to version/hash, callback identity/priority, and menu slugs; the controlled warning trace passed. | closed |
| T-01-04 | Spoofing | `01-DIAGNOSIS.md`, warning attribution | medium | mitigate | The diagnosis attributes the reproduced warning only to the synthetic fixture and explicitly leaves the unavailable live site's callback unproven. | closed |
| T-01-05 | Information Disclosure | Below-floor compatibility notice | medium | mitigate | The PHP-floor lifecycle check confirms an escaped notice only for authorized plugin managers and no notice on public or unauthorized requests. | closed |
| T-01-06 | Denial of Service | Conditional bootstrap and dependency loading | high | mitigate | Fresh WordPress 7.0.7 and 7.1.3 lifecycle checks confirm active/data preservation and inert behavior below PHP 8.3, followed by normal recovery at PHP 8.3. | closed |
| T-01-07 | Tampering | `custom_menu_order()` | high | mitigate | The fresh menu contract passes stable ordering and no-global-mutation cases while preserving unrelated entries. | closed |
| T-01-08 | Denial of Service | Conflicting `menu_order` callbacks | medium | mitigate | The menu contract passes missing, duplicate, empty, single, and ordering-conflict cases with the standard WordPress order fallback. | closed |
| T-01-09 | Repudiation | `01-COMPATIBILITY-MATRIX.md`, `readme.txt` | high | mitigate | The refreshed matrix records exact WordPress/PHP versions, image IDs, source revision, and checksums; the strict metadata evidence check passes. | closed |
| T-01-10 | Tampering | Workflow fixtures and matrix database | medium | mitigate | The isolated matrix and full-workflow assertions passed across all six current supported combinations. | closed |
| T-01-11 | Tampering | PHP syntax conversions and CSV behavior | medium | mitigate | The compatibility suite passes across PHP 8.3–8.5, including the real-plugin CSV workflow; the existing all-tracked-PHP lint and CSV round-trip checks remain mapped in `01-VALIDATION.md`. | closed |
| T-01-SC | Tampering | npm/pip/cargo installs | high | accept | No npm, pip, or cargo package is installed by this phase; container image pulls are constrained by T-01-01. | closed |

*Status: open · closed · open — below high threshold (non-blocking)*
*Severity: critical > high > medium > low — only open threats at or above the configured high threshold count toward `threats_open`.*
*Disposition: mitigate (implementation required) · accept (documented risk) · transfer (third-party)*

---

## Accepted Risks Log

| Risk ID | Threat Ref | Rationale | Accepted By | Date |
|---------|------------|-----------|-------------|------|
| AR-01-SC | T-01-SC | The phase adds no npm, pip, or cargo dependency; the explicit plan-time accept disposition is retained for this no-install case. | Phase plan (`accept` disposition) | 2026-10-13 |

---

## Security Audit Trail

| Audit Date | Threats Total | Closed | Open | Run By |
|------------|---------------|--------|------|--------|
| 2026-10-13 | 12 | 12 | 0 | GSD execute-phase (plan-time L1 review) |

The security register was authored in Phase 01 plans. At configured ASVS level 1, current evidence closes every mitigation and the no-install acceptance, so the workflow's plan-time short-circuit applies; no deeper auditor pass was required.

---

## Sign-Off

- [x] All threats have a disposition (mitigate / accept / transfer)
- [x] Accepted risks documented in Accepted Risks Log
- [x] `threats_open: 0` confirmed
- [x] `status: verified` set in frontmatter

**Approval:** verified 2026-10-13
