# Phase 1: Compatibility Baseline and Menu Diagnosis - Context

**Gathered:** 2026-10-03
**Status:** Ready for planning

<domain>
## Phase Boundary

Establish GigPress's supported WordPress 7.0+ and PHP 8.3+ baseline, diagnose and resolve the reported admin-menu warning, and define safe behavior for already-installed sites below the PHP minimum. Preserve existing show data and plugin behavior while completing compatibility work; broader data, admin UX, public layout, and CSV improvements remain in their later roadmap phases.

</domain>

<decisions>
## Implementation Decisions

### Runtime compatibility and below-minimum behavior
- **D-01:** Declare WordPress 7.0 and PHP 8.3 as the minimum supported versions, and validate current upstream-supported releases above those minimums.
- **D-02:** Treat WordPress 7.1.2 with PHP 8.2 as the reported diagnostic environment only. PHP 8.2 is not a support target.
- **D-03:** If GigPress is already installed on PHP below 8.3, deactivate it automatically and show a persistent compatibility notice to administrators who can manage plugins. Keep the notice until PHP 8.3 or later is detected.

### Admin menu placement
- **D-04:** Preserve GigPress's current menu position after Comments and its separator when compatible. If custom ordering conflicts with WordPress or another plugin, use WordPress's standard menu order so GigPress remains warning-free.

### The agent's Discretion
The implementation mechanism for the runtime check, notice, and menu-order correction is for phase research and planning to determine from the existing WordPress integration. The exact root cause of the reported warning remains to be reproduced and confirmed.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Product scope and phase boundaries
- `.planning/PROJECT.md` — project purpose, compatibility baseline, and preservation constraints.
- `.planning/REQUIREMENTS.md` — approved v1 requirements and support scope.
- `.planning/ROADMAP.md` — Phase 1 goal, requirements, and success criteria.

### Compatibility and migration research
- `.planning/research/STACK.md` — current WordPress/PHP version policy and validation recommendations.
- `.planning/research/ARCHITECTURE.md` — plugin integration points and incremental modernization sequence.
- `.planning/research/PITFALLS.md` — menu-order, PHP syntax, and upgrade risks.

### Existing codebase
- `.planning/codebase/STACK.md` — current runtime declarations and dependency overview.
- `.planning/codebase/ARCHITECTURE.md` — bootstrap, WordPress hooks, menu registration, and data flow.
- `.planning/codebase/CONCERNS.md` — known compatibility, warning, and security concerns.

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `gigpress.php` is the plugin bootstrap and already owns plugin metadata and WordPress hook registration.
- `custom_menu_order()` in `gigpress.php` contains the existing GigPress menu placement logic and can be investigated in place.

### Established Patterns
- GigPress is a procedural PHP plugin integrated through WordPress hooks and globals.
- Its admin menu is registered in the bootstrap; the current custom ordering inserts the top-level `gigpress/gigpress.php` item after `edit-comments.php` and adds a `separator-gp` entry to the global `$menu`.
- The reported warning names `separator-gigpress`, which differs from the separator identifier currently used in the mapped source. The cause is not confirmed.

### Integration Points
- Plugin version and WordPress/PHP metadata are maintained in `gigpress.php` and `readme.txt`.
- WordPress menu-order filters are registered in `gigpress.php`.
- Upgrade and stored-setting behavior is implemented through `admin/db.php` and `lib/upgrade.php`; Phase 1 should preserve those contracts.

</code_context>

<specifics>
## Specific Ideas

The user reported `Undefined array key "separator-gigpress"` from `wp-admin/includes/menu.php` on WordPress 7.1.2 with PHP 8.2. PHP 8.2 is not a target. Preserve the current menu position and separator where compatible, with standard WordPress menu order as the fallback.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within Phase 1 scope.

</deferred>

---

*Phase: 1-Compatibility Baseline and Menu Diagnosis*
*Context gathered: 2026-10-03*
