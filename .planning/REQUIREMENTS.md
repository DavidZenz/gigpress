# Requirements: GigPress

**Defined:** 2026-10-03
**Core Value:** Site owners can manage show information and reliably publish it on their WordPress sites.

## v1 Requirements

Requirements for the first compatibility and UX delivery. Each maps to a roadmap phase.

### Platform Compatibility

- [x] **COMP-01**: GigPress declares WordPress 7.0 and PHP 8.3 as its minimum versions in its plugin metadata and readme; its tested-up-to version reflects a WordPress release that has passed the project’s compatibility checks.
- [x] **COMP-02**: GigPress activates and completes its existing administration, public-display, feed, and CSV workflows on WordPress 7.0 and the latest 7.1 release with PHP 8.3 and every newer PHP release branch still supported upstream at validation time, without PHP warnings or fatal errors in those workflows.
- [x] **COMP-03**: A controlled late-menu fixture reproduces the exact reported `separator-gigpress` key and is attributed by the repository diagnostic; GigPress’s own equivalent late `separator-gp` mutation is corrected so this checkout’s admin menu is warning-free on the supported WordPress/PHP matrix. Because the reported installation is unavailable and this repository has no `separator-gigpress` occurrence, the original live site’s callback identity is recorded as unprovable from repository evidence rather than inferred from the fixture.
- [x] **COMP-04**: An already-active GigPress installation below PHP 8.3 remains active but inert: before plugin modules or normal hooks/functions load, an early guard registers only a persistent compatibility notice for users who can manage plugins and returns. The notice repeats on later authorized admin requests while the runtime remains below PHP 8.3, is absent for unauthorized users and public requests, and disappears automatically when PHP 8.3+ returns; new activation below the floor is blocked by the `Requires PHP: 8.3` header.

### Existing Data and Behavior

- [ ] **DATA-01**: Updating GigPress leaves existing show, artist, venue, and tour records, relationships, and saved settings usable without data loss.
- [ ] **DATA-02**: Site owners can continue to create, edit, copy, trash, and restore shows and manage their artist, venue, and tour relationships using GigPress’s existing data model.

### Public Publishing

- [ ] **PUB-01**: Existing shortcodes, widgets, related-post displays, RSS and iCalendar feeds, and their established output contracts continue to work after the update.
- [ ] **PUB-02**: The bundled public show listing remains readable at a 320 CSS-pixel viewport without page-level horizontal scrolling, with existing show details and links available; child-theme, parent-theme, and `wp-content/gigpress-templates` overrides continue to resolve with their current filenames, variables, and CSS hooks.

### Administration Workflows

- [ ] **ADMIN-01**: Add and edit show forms make date, optional time, multi-day, and expiration fields clear; invalid values receive field-specific text feedback and all entered values remain available for correction.
- [ ] **ADMIN-02**: Show-list filters for scope, artist, tour, venue, sort, and page size remain visible after filtering and pagination; bulk actions identify their result and do not affect unselected shows.
- [ ] **ADMIN-03**: Settings are grouped with contextual help while retaining existing option keys, saved values, and setting meanings.

### CSV Import and Export

- [ ] **CSV-01**: CSV import and export retain the existing GigPress column format, supported filters, related-post option, and row-handling behavior.
- [ ] **CSV-02**: Import and export screens clearly label their tasks and report actionable results, including imported, duplicate, skipped, or rejected row counts and reasons where applicable.

### Usability and Safety

- [ ] **UX-01**: Administration controls changed for this delivery have associated labels, semantic table headings where applicable, keyboard operation, and text-based success and error feedback.
- [ ] **SEC-01**: Changed administration and import/export paths validate inputs, retain appropriate capability and nonce checks for mutations, and escape output for its destination format.

## v2 Requirements

Deferred until separately requested or supported by user evidence.

- **FUTURE-01**: New event operations such as ticket sales, booking, RSVP, reminders, or external synchronization.
- **FUTURE-02**: An import preview/mapping wizard for arbitrary third-party CSV schemas.

## Out of Scope

| Feature | Reason |
|---------|--------|
| PHP 8.2 as a supported target | The user selected PHP 8.3+; PHP 8.2 is only the environment associated with the reported warning. |
| Support for WordPress versions earlier than 7.0 or PHP versions earlier than 8.3 | The user selected these minimums. |
| Replacing GigPress’s custom tables, public template resolver, or CSV schema wholesale | Existing sites rely on these data and extension contracts; compatibility work should preserve them. |
| A wholesale admin or framework rewrite | The requested delivery is compatibility plus improvements to five existing workflows; a broad rewrite adds regression risk without serving that scope. |
| New event-management product features | They expand GigPress beyond the compatibility and UX work approved for this delivery. |

## Traceability

Which phases cover which requirements.

| Requirement | Phase | Status |
|-------------|-------|--------|
| COMP-01 | Phase 1 | Complete |
| COMP-02 | Phase 1 | Complete |
| COMP-03 | Phase 1 | Complete |
| COMP-04 | Phase 1 | Complete |
| DATA-01 | Phase 2 | Pending |
| DATA-02 | Phase 2 | Pending |
| ADMIN-01 | Phase 3 | Pending |
| ADMIN-02 | Phase 3 | Pending |
| ADMIN-03 | Phase 3 | Pending |
| UX-01 | Phase 3 | Pending |
| PUB-01 | Phase 4 | Pending |
| PUB-02 | Phase 4 | Pending |
| CSV-01 | Phase 5 | Pending |
| CSV-02 | Phase 5 | Pending |
| SEC-01 | Phase 5 | Pending |

**Coverage:**
- v1 requirements: 15 total
- Mapped to phases: 15
- Unmapped: 0 ✓

---
*Requirements defined: 2026-10-03*
*Last updated: 2026-10-03 after Phase 1 runtime-floor decision and repository-evidence boundary clarification*
