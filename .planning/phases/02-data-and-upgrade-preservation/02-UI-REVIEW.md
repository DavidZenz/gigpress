# Phase 02 — UI Review

**Audited:** 2026-10-04
**Baseline:** Abstract 6-pillar standards (no Phase 02 `UI-SPEC.md`)
**Source baseline:** final Phase 02 source commit `61d60fd9c9afb09b65a653c8b7e3c0935ee5057b`
**Screenshots:** Not captured (no dev server at localhost:3000, 5173, or 8080)
**Interaction captures:** off (workflow.ui_interaction_capture is false)

---

## Pillar Scores

| Pillar | Score | Key Finding |
|--------|-------|-------------|
| 1. Copywriting | 2/4 | Upgrade-paused and dependency-blocked states give no recovery action; several mutation failures use vague copy. |
| 2. Visuals | 2/4 | A blocked artist or venue row silently loses Delete, so the visible action set does not explain the constraint. |
| 3. Color | 3/4 | The changed controls rely on default WordPress links/notices while the shared stylesheet retains non-semantic hard-coded status colors. |
| 4. Typography | 2/4 | The inherited admin stylesheet has five type sizes including an 11px status message, without a documented scale. |
| 5. Spacing | 2/4 | Admin styles mix px, em, and percentage spacing and retain a 240px footer gap. |
| 6. Experience Design | 2/4 | Final guards now cover all examined mutations, but paused upgrades and irreversible deletes still leave the administrator without a complete, safe flow. |

**Overall: 13/24**

---

## Top 3 Priority Fixes

1. **Explain unavailable deletion in each affected list row** — an artist or venue referenced only by trashed shows displays `0` active shows yet loses Delete without explanation — render disabled Delete text with an accessible reason: “Cannot delete: referenced by active or trashed shows.”
2. **Make paused-upgrade recovery actionable** — every guarded mutation stops with a raw database-state code and no next step — link the notice to a GigPress status/retry screen and state the available recovery action.
3. **Add confirmation to destructive entity deletion** — an unreferenced artist, venue, or tour is deleted through a nonce-protected GET link without an explicit confirmation — use WordPress’s standard destructive-action confirmation before dispatching the handler.

---

## Detailed Findings

### Pillar 1: Copywriting (2/4)

- **WARNING:** The common database-readiness notice exposes an implementation-facing code, “`unsafe_metadata`” or equivalent, and only says to resolve the condition. It gives no human-readable cause, status location, retry action, or support path ([admin/handlers.php](/Users/davidzenz/gigpress/admin/handlers.php:3)). The final commit applies this message to venue, tour, artist, import, trash, and tour-mapping mutations, expanding the affected surface ([admin/handlers.php](/Users/davidzenz/gigpress/admin/handlers.php:411), [admin/handlers.php](/Users/davidzenz/gigpress/admin/handlers.php:1120)).
- **WARNING:** An ordinary list row removes Delete when any active or trashed dependency exists, while the table still reports only active shows. The explanatory message only appears after a crafted/direct request reaches the handler ([admin/artists.php](/Users/davidzenz/gigpress/admin/artists.php:138), [admin/artists.php](/Users/davidzenz/gigpress/admin/artists.php:156), [admin/handlers.php](/Users/davidzenz/gigpress/admin/handlers.php:19)).
- **WARNING:** Repeated operation failures still say “Something ain't right - try again?” without identifying the failed action or recovery ([admin/handlers.php](/Users/davidzenz/gigpress/admin/handlers.php:437), [admin/handlers.php](/Users/davidzenz/gigpress/admin/handlers.php:735)).

### Pillar 2: Visuals (2/4)

- **WARNING:** Artist and venue action cells show Edit alone whenever dependencies block deletion. There is no disabled Delete affordance, helper text, icon, or tooltip distinguishing a dependency constraint from a missing capability ([admin/artists.php](/Users/davidzenz/gigpress/admin/artists.php:154), [admin/venues.php](/Users/davidzenz/gigpress/admin/venues.php:219)).
- **WARNING:** No screenshots could be captured, so the focus treatment, notice contrast, table overflow, and responsive rendering are unverified. This score is code-derived.

### Pillar 3: Color (3/4)

- **WARNING:** The unavailable Delete state has no semantic visual treatment because the control is omitted entirely. Default WordPress action-link and notice colors provide no visible warning before an administrator searches for the missing action ([admin/artists.php](/Users/davidzenz/gigpress/admin/artists.php:155), [admin/venues.php](/Users/davidzenz/gigpress/admin/venues.php:220)).
- **WARNING:** The shared admin stylesheet hard-codes independent status colors (`red`, `#ffebe8`, `#C00`, `#ffffe0`, `#e6db55`, `#d54e21`) rather than a documented semantic palette ([css/gigpress-admin.css](/Users/davidzenz/gigpress/css/gigpress-admin.css:25), [css/gigpress-admin.css](/Users/davidzenz/gigpress/css/gigpress-admin.css:37), [css/gigpress-admin.css](/Users/davidzenz/gigpress/css/gigpress-admin.css:78), [css/gigpress-admin.css](/Users/davidzenz/gigpress/css/gigpress-admin.css:109)). No Phase 02 color contract exists to demonstrate a 60/30/10 distribution.

### Pillar 4: Typography (2/4)

- **WARNING:** The admin CSS declares five font sizes (`11px`, `90%`, `0.9em`, `110%`, `1.2em`) and one weight (`bold`) rather than a documented, coherent scale. The 11px artist-sort status copy is especially small ([css/gigpress-admin.css](/Users/davidzenz/gigpress/css/gigpress-admin.css:76), [css/gigpress-admin.css](/Users/davidzenz/gigpress/css/gigpress-admin.css:86), [css/gigpress-admin.css](/Users/davidzenz/gigpress/css/gigpress-admin.css:188)).
- **WARNING:** The important upgrade-paused message uses the generic WordPress error notice without a heading or an action, making it easy to scan past in a dense admin page ([admin/handlers.php](/Users/davidzenz/gigpress/admin/handlers.php:7)).

### Pillar 5: Spacing (2/4)

- **WARNING:** The stylesheet mixes `3px`, `5px`, `7px`, `10px`, `.2em`, `.6em 1em`, `1em`, and `2em` values without a declared spacing scale ([css/gigpress-admin.css](/Users/davidzenz/gigpress/css/gigpress-admin.css:9), [css/gigpress-admin.css](/Users/davidzenz/gigpress/css/gigpress-admin.css:80), [css/gigpress-admin.css](/Users/davidzenz/gigpress/css/gigpress-admin.css:178)). New notices and actions inherit that inconsistent rhythm.
- **WARNING:** The footer includes `margin: 240px 0px 20px`, which can create a large unexplained empty region when displayed ([css/gigpress-admin.css](/Users/davidzenz/gigpress/css/gigpress-admin.css:120)).

### Pillar 6: Experience Design (2/4)

- **WARNING:** Commit `61d60fd` closes an important coverage gap by running `gigpress_require_database_ready()` after nonce validation in add/update venue, add/update tour, add/update artist, import, empty-trash, and tour-mapping handlers ([admin/handlers.php](/Users/davidzenz/gigpress/admin/handlers.php:411), [admin/handlers.php](/Users/davidzenz/gigpress/admin/handlers.php:455), [admin/handlers.php](/Users/davidzenz/gigpress/admin/handlers.php:523), [admin/handlers.php](/Users/davidzenz/gigpress/admin/handlers.php:662), [admin/handlers.php](/Users/davidzenz/gigpress/admin/handlers.php:892), [admin/handlers.php](/Users/davidzenz/gigpress/admin/handlers.php:1098), [admin/handlers.php](/Users/davidzenz/gigpress/admin/handlers.php:1120)). However, the shared failure output does not let an administrator recover in place ([admin/handlers.php](/Users/davidzenz/gigpress/admin/handlers.php:7)).
- **WARNING:** Artist, venue, and tour deletions remain nonce-protected GET links with no confirmation dialog or intermediate confirmation page ([admin/artists.php](/Users/davidzenz/gigpress/admin/artists.php:156), [admin/venues.php](/Users/davidzenz/gigpress/admin/venues.php:221), [admin/tours.php](/Users/davidzenz/gigpress/admin/tours.php:136)).
- **WARNING:** Tour restore reports only aggregate restored/skipped counts. A partial recovery gives no show identifiers or inspection path, so an administrator cannot resolve skipped relationships from the notice ([admin/handlers.php](/Users/davidzenz/gigpress/admin/handlers.php:850), [admin/handlers.php](/Users/davidzenz/gigpress/admin/handlers.php:875)).
- **WARNING:** The import-tours action now verifies a nonce at the page entry and again in the handler. The code is safe, but no user-facing loading, progress, or completion state identifies a potentially long-running migration ([admin/artists.php](/Users/davidzenz/gigpress/admin/artists.php:22), [admin/handlers.php](/Users/davidzenz/gigpress/admin/handlers.php:1116)).

---

## Files Audited

- `admin/artists.php`
- `admin/venues.php`
- `admin/tours.php`
- `admin/handlers.php`
- `css/gigpress-admin.css`
- `tests/compat/upgrade-preservation-crud.php`
- `.planning/phases/02-data-and-upgrade-preservation/02-01-PLAN.md`
- `.planning/phases/02-data-and-upgrade-preservation/02-02-PLAN.md`
- `.planning/phases/02-data-and-upgrade-preservation/02-03-PLAN.md`
- `.planning/phases/02-data-and-upgrade-preservation/02-04-PLAN.md`
- `.planning/phases/02-data-and-upgrade-preservation/02-01-SUMMARY.md`
- `.planning/phases/02-data-and-upgrade-preservation/02-02-SUMMARY.md`
- `.planning/phases/02-data-and-upgrade-preservation/02-03-SUMMARY.md`
- `.planning/phases/02-data-and-upgrade-preservation/02-04-SUMMARY.md`

Registry audit: skipped — `components.json` is absent, so shadcn and third-party registry blocks are not initialized.
