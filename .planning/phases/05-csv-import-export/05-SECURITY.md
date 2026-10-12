---
phase: "05"
slug: "csv-import-export"
status: verified
threats_open: 0
asvs_level: 1
created: "2026-10-12"
---

# Phase 05 — Security

> Per-phase security contract: threat register, accepted risks, and audit trail.

---

## Trust Boundaries

| Boundary | Description | Data Crossing |
|---|---|---|
| CSV request to WordPress handler | Authority, method, nonce, upload origin, and request readiness are established before file access or mutation. | Request parameters, nonce, uploaded file metadata and bytes |
| CSV bytes to persisted records | Untrusted values are parsed, validated as a complete row, written to GigPress tables/posts, and read back before success is reported. | Dates, text, IDs, status, venue/artist/tour/post associations |
| Database rows to CSV response | Stored values cross into a downloadable file that external spreadsheet clients may interpret. | Exact user-entered strings and identifiers |
| Admin state to HTML | Filter values, uploaded filenames, result identities, and bounded system reasons render in an authenticated admin page. | Query parameters and import/conversion outcomes |
| Conversion intent and journal to database mutations | Owner-bound intent and durable recovery state control staged reassignment and source deletion. | Tour, artist, show IDs, source/intended relationships, stage/failure evidence |
| Fixture and evidence runner | Owned processes, sessions, runtime facts, and human observations become validation evidence. | Temporary credentials, fixture records, source/result fingerprints, browser observations |

---

## Threat Register

| Threat ID | Category | Component | Severity | Disposition | Mitigation | Status |
|---|---|---|---|---|---|---|
| T-05-01 | Tampering | `gigpress_csv_prepare_record()` / `gigpress_csv_persist_record()` | high | mitigate | Validate complete rows before writes; validate field formats and require persisted identity/field read-back before reporting imported. Current six-cell CSV matrix includes pre-write invalid-input and retry snapshot cases. | closed |
| T-05-02 | Repudiation | CSV outcome accounting and duplicate retry | medium | mitigate | Give each parsed row one terminal structured outcome with row identity and count equality; compare unchanged duplicate snapshots. | closed |
| T-05-03 | Tampering | `csv-workflows` evidence dispatch | medium | mitigate | Enforce exact registry membership, selected-case identity, nonempty boolean checks, and zero runtime/plugin errors; validator self-tests reject corrupted evidence. | closed |
| T-05-04 | Elevation of Privilege | `gigpress_import()` upload route | high | mitigate | Enforce configured capability, exact POST/action, nonce, and readiness before file reads, moves, or writes; authenticated HTTP tests cover denials. | closed |
| T-05-05 | Denial of Service | CSV upload/parser boundary | high | mitigate | Require an actual uploaded file, nonempty bounded size, exact header/record structure, and owned temporary-file cleanup. | closed |
| T-05-06 | Tampering | Import identity/write retry | high | mitigate | Validate complete rows, distinguish query failure from absence, read back persisted values, and reuse verified completed work on retry. | closed |
| T-05-07 | Elevation of Privilege | Import notices and file errors | high | mitigate | Escape row values, filenames, and bounded system text at HTML output; hostile filename and result rendering cases pass. | closed |
| T-05-08 | Tampering | `gigpress_csv_export_state` filters | medium | mitigate | Allow only documented sentinels/IDs/scopes and build prepared predicates shared by displayed count and emitted rows. | closed |
| T-05-09 | Information Disclosure / Elevation of Privilege | Export handler and CSV response | high | mitigate | Enforce configured capability and nonce before querying or sending headers/body; preview and download authorization cases pass. | closed |
| T-05-10 | Tampering | External spreadsheet interpretation | high | transfer | Preserve values exactly and document the bounded Excel text-import guidance. Direct-open formula interpretation remains an explicitly accepted external-client risk; the UI makes no universal safety claim. | closed |
| T-05-11 | Spoofing / Elevation of Privilege | Conversion intent and confirm handler | high | mitigate | Bind capability, nonce, owner, expiry, selection, preview fingerprint, source state, and one-use status; reject invalid intents before writes. | closed |
| T-05-12 | Tampering | Target resolution and staged mutations | high | mitigate | Resolve all exact-name candidates, refuse ambiguity, journal original/intended relationships, compare ownership before retries, preserve intervening reassignment, and verify mutations by read-back. | closed |
| T-05-13 | Repudiation / Denial of Service | Batch conversion result | high | mitigate | Persist per-tour stage, evidence, and failure; continue independent tours; keep failed work visible; never report terminal success after persistence/read-back failure. | closed |
| T-05-14 | Tampering / Repudiation | CSV and inherited matrix reports | high | mitigate | Require exact registry/runtime membership, source/result fingerprints, independent validators, and corruption self-tests; refresh affected Phase 02/04 evidence after shared runner changes. | closed |
| T-05-15 | Spoofing / Denial of Service | Browser fixture process/session | medium | mitigate | Use unique ownership/session metadata, authenticated health checks, bounded lifecycle commands, diagnostics, and cleanup on success/failure. | closed |
| T-05-16 | Repudiation | Browser/spreadsheet acceptance | medium | mitigate | Keep named browser/client evidence separate from automation; record missing observations as waived or pending without claiming they passed. D-01 remains explicitly waived and untested; spreadsheet-client behavior remains unobserved. | closed |
| T-05-SC | Tampering | npm/pip/cargo installs | high | mitigate | No package installation was introduced in this phase; no new dependency supply chain was added. | closed |

*Status: open · closed · open — below high threshold (non-blocking)*
*Only open threats at or above the configured high threshold count toward `threats_open`.*

---

## Accepted Risks Log

| Risk ID | Threat Ref | Rationale | Accepted By | Date |
|---|---|---|---|---|
| AR-05-01 | T-05-10 | Exact CSV values must round-trip. Direct-opening formula-like values can be interpreted by external spreadsheet clients; the export preserves them and the UI gives bounded text-import guidance without claiming universal safety. The user accepted this behavior for the current phase. | Site owner (user) | 2026-10-12 |

---

## Security Audit Trail

| Audit Date | Threats Total | Closed | Open | Run By |
|---|---:|---:|---:|---|
| 2026-10-12 | 17 | 17 | 0 | Codex, ASVS L1 review against phase threat models and final evidence |

---

## Sign-Off

- [x] All threats have a disposition (mitigate / accept / transfer)
- [x] Accepted risks documented in Accepted Risks Log
- [x] `threats_open: 0` confirmed
- [x] `status: verified` set in frontmatter

**Approval:** verified 2026-10-12
