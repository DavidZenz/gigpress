---
phase: "02"
slug: "data-and-upgrade-preservation"
status: verified
threats_open: 0
asvs_level: 1
created: "2026-10-04"
---

# Phase 02 — Security

> Per-phase security contract: threat register, accepted risks, and audit trail.

## Trust Boundaries

| Boundary | Description | Data Crossing |
|----------|-------------|---------------|
| Existing site data to migration code | Legacy schema and metadata may be incomplete, unknown, or ambiguous; migration code must validate before writing. | Stored rows, options, IDs, relationships, and linked content. |
| WordPress admin requests to mutation handlers | Authenticated requests can be forged, incomplete, or submitted while upgrade state is unsafe. | Nonces, selected IDs, form fields, uploaded CSV data, and mutation commands. |
| Compatibility runner to containers | The runner executes the plugin and synthetic fixtures in disposable database and official WordPress/PHP images. | Checkout mount, fixture SQL/state, database credentials, runtime image and result data. |
| Test results to support claims | Machine output becomes evidence for supported WordPress/PHP versions and data-preservation behavior. | Runtime versions, image digests, case statuses, warning/fatal counts, and invariant failures. |

## Threat Register

| Threat ID | Category | Component | Severity | Disposition | Mitigation | Status |
|-----------|----------|-----------|----------|-------------|------------|--------|
| T-02-01 | Tampering | Database upgrade coordinator | high | mitigate | Journal mappings, verify writes and postconditions, and read back the final version marker (`admin/db.php`). | closed |
| T-02-02 | Elevation of privilege | Plugin bootstrap and recovery notice | high | mitigate | Stop normal plugin loading on blocked state and scope recovery notice to `activate_plugins` (`gigpress.php`, `admin/db.php`). | closed |
| T-02-03 | Information disclosure | Upgrade recovery notice | medium | mitigate | Expose only a stable failure code and recovery guidance, not database details (`admin/db.php`). | closed |
| T-02-04 | Tampering | Compatibility runner and Compose boundary | high | mitigate | Reject external database/Compose overrides, isolate each run, and mount the checkout read-only (`tests/compat/run.sh`, `tests/compat/compose.yaml`). | closed |
| T-02-05 | Tampering | Legacy metadata classification | high | mitigate | Select only recognized source versions and block unknown metadata before mutation (`admin/db.php`). | closed |
| T-02-06 | Tampering | Generated entity mappings | high | mitigate | Journal source IDs and generated mappings, verify inserts, and reject ambiguous candidates (`admin/db.php`, migration probes). | closed |
| T-02-07 | Information disclosure | Synthetic fixture and result output | low | accept | Fixtures contain reconstructed synthetic data; probes report invariant failures without production credentials or raw SQL. See AR-02-07. | closed |
| T-02-08 | Elevation of privilege | Admin mutation handlers | high | mitigate | Gate all mutation handlers on database readiness while retaining nonce checks (`admin/handlers.php`). | closed |
| T-02-09 | Tampering | Selected show mutations | high | mitigate | Sanitize selected IDs and verify selected/unselected row outcomes (`admin/handlers.php`, `tests/compat/upgrade-preservation-crud.php`). | closed |
| T-02-10 | Tampering | Artist and venue deletion | high | mitigate | Check active and trashed dependencies before deletion; do not cascade (`admin/handlers.php`). | closed |
| T-02-11 | Tampering | Tour delete and undo | high | mitigate | Track per-tour/show ownership and restore only owned, still-detached shows (`admin/handlers.php`). | closed |
| T-02-12 | Repudiation | Preservation evidence | medium | mitigate | Record exact runtime cells and image identifiers; validate versions, image tag/digest, statuses, and error counts (`tests/compat/run.sh`, `02-PRESERVATION-MATRIX.md`). | closed |
| T-02-13 | Tampering | Aggregate case registry | high | mitigate | Require unique required cases and support modules; fail closed for missing, duplicate, skipped, unknown, or failed cases (`tests/compat/probe.php`, `tests/compat/run.sh`). | closed |
| T-02-14 | Tampering | Supported runtime selection | high | mitigate | Resolve upstream WordPress/PHP targets, pin a WordPress patch per line, enforce PHP 8.3 minimum, and reject incomplete or unsupported evidence (`tests/compat/run.sh`). | closed |
| T-02-SC | Tampering | Container setup and dependencies | high | mitigate | Use the existing Docker/Compose harness without npm, pip, or cargo installation paths; retain the existing image integrity controls. | closed |

*Status: open · closed · open — below high threshold (non-blocking)*
*Severity: critical > high > medium > low — only open threats at or above workflow.security_block_on count toward threats_open*
*Disposition: mitigate (implementation required) · accept (documented risk) · transfer (third-party)*

## Accepted Risks Log

| Risk ID | Threat Ref | Rationale | Accepted By | Date |
|---------|------------|-----------|-------------|------|
| AR-02-07 | T-02-07 | Synthetic reconstructed fixtures and invariant-only probe output contain no production credentials or raw SQL; this is limited to the repository-owned test harness. | Phase 02 approved plan disposition (`02-02-PLAN.md`) | 2026-10-04 |

## Security Audit Trail

| Audit Date | Threats Total | Closed | Open | Run By |
|------------|---------------|--------|------|--------|
| 2026-10-04 | 15 | 15 | 0 | GSD security auditor; follow-up against `a3591fb13521bbddaea84e9be479aea302cee1b3` |

The follow-up audit found 14 implemented mitigations and confirmed the remaining low-severity
T-02-07 risk was already an explicit planned acceptance. AR-02-07 above records that rationale;
no threat remains open after applying the accepted disposition. The latest runner audit covered
the per-line WordPress patch pinning and evidence checks at source revision
`a3591fb13521bbddaea84e9be479aea302cee1b3`.

## Sign-Off

- [x] All threats have a disposition (mitigate / accept / transfer)
- [x] Accepted risks documented in Accepted Risks Log
- [x] `threats_open: 0` confirmed
- [x] `status: verified` set in frontmatter

**Approval:** verified 2026-10-04
