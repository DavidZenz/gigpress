# Phase 04 — UI Review

**Audited:** 2026-10-06  
**Baseline:** Abstract six-pillar standards plus Phase 04 public-publishing context (no `UI-SPEC.md`)  
**Screenshots:** not captured (no local dev server on ports 3000, 5173, or 8080)  
**Interaction captures:** off (code-only audit; no interaction capture requested)

---

## Pillar Scores

| Pillar | Score | Key Finding |
|--------|-------|-------------|
| 1. Copywriting | 2/4 | The required subscription actions are rendered as terse `RSS`/`iCal` text, then hidden visually by the final CSS cascade. |
| 2. Visuals | 2/4 | The responsive main-list structure is sound, but no visible keyboard-focus treatment is provided and subscription actions degrade to icon-sized controls. |
| 3. Color | 2/4 | Hard-coded muted `#999` text is used for cancelled content and subscriptions, which can miss readable contrast on a white theme. |
| 4. Typography | 2/4 | The bundled layout inherits theme fonts, but legacy calendar/subscription controls still impose 11px text or icon-only text treatment. |
| 5. Spacing | 3/4 | The bundled narrow stack uses coherent `em` spacing and dividers; the later subscription rule defeats the intended link layout and target spacing. |
| 6. Experience Design | 2/4 | Direct calendar anchors and static empty output exist, but the final stylesheet hides the visible subscription link labels and supplies no focus-visible state. |

**Overall: 13/24**

---

## Top 3 Priority Fixes

1. **BLOCKER — Restore visible subscription labels in the final cascade** — RSS and iCalendar subscription actions are reduced to 12px icon-only links, undermining the required readable, wrapping Subscribe group. Move the readable `p.gigpress-subscribe a` rule after the legacy icon rules, or scope the legacy hiding rules to a legacy-only surface; set `text-indent: 0`, `width: auto`, and remove the icon background for the bundled public footer.
2. **WARNING — Provide a visible keyboard focus indicator and usable target size for all public links** — keyboard users cannot reliably locate focus from this stylesheet, while subscription anchors collapse to 12px. Add a theme-respecting `:focus-visible` outline/underline rule and ensure the subscription/calendar targets have adequate inline padding or line-height.
3. **WARNING — Remove low-contrast hard-coded muted text from public status and subscription content** — `#999` on light themes can fail normal-text contrast and makes cancelled details harder to read. Inherit the theme foreground color or use a theme-variable-compatible muted color with adequate contrast; retain the explicit Cancelled/Sold Out text and badge as the status signal.

---

## Detailed Findings

### Pillar 1: Copywriting (2/4)

- **WARNING:** The main subscription footer emits `RSS` and `iCal` rather than the context’s `RSS/iCalendar` wording ([templates/shows-list-footer.php:15](/Users/davidzenz/gigpress/templates/shows-list-footer.php:15), [templates/shows-list-footer.php:18](/Users/davidzenz/gigpress/templates/shows-list-footer.php:18)). The `title` attribute expands iCalendar, but visible text should identify the action without requiring a hover.
- **BLOCKER:** The final stylesheet removes the visible text from those same actions with `text-indent: -9999px` and `width: 12px` ([css/gigpress.css:413](/Users/davidzenz/gigpress/css/gigpress.css:413)). These later, equally specific rules override the Phase 04 attempt to keep labels visible at [css/gigpress.css:240](/Users/davidzenz/gigpress/css/gigpress.css:240).
- **WARNING:** Empty-state output is only a configurable raw message inside a paragraph ([templates/shows-list-empty.php:12](/Users/davidzenz/gigpress/templates/shows-list-empty.php:12)); there is no bundled guidance or action to help a visitor recover from an empty upcoming listing.

### Pillar 2: Visuals (2/4)

- **WARNING:** The narrow bundled table has a clear date-first single-column structure and real labels ([css/gigpress.css:266](/Users/davidzenz/gigpress/css/gigpress.css:266), [templates/shows-list.php:16](/Users/davidzenz/gigpress/templates/shows-list.php:16)), but the final subscription cascade collapses the Subscribe group to icon-sized items ([css/gigpress.css:413](/Users/davidzenz/gigpress/css/gigpress.css:413)). This contradicts the readable compact footer specified in D-12.
- **WARNING:** The public stylesheet contains hover styling for the legacy toggle but no `:focus` or `:focus-visible` styling for public links ([css/gigpress.css:372](/Users/davidzenz/gigpress/css/gigpress.css:372)). Focus visibility is therefore entirely delegated to each theme and is unverified by the bundled visual system.
- **WARNING:** The header is visually clipped on narrow screens ([css/gigpress.css:276](/Users/davidzenz/gigpress/css/gigpress.css:276)). That is acceptable only because every data cell carries an in-flow label; owner-adopted markup is documented as needing equivalent labels, but the stylesheet cannot protect an adopted custom template that omits them.

### Pillar 3: Color (2/4)

- **WARNING:** Public secondary information uses hard-coded `#333` and labels use `#666` ([css/gigpress.css:116](/Users/davidzenz/gigpress/css/gigpress.css:116), [css/gigpress.css:142](/Users/davidzenz/gigpress/css/gigpress.css:142)); cancelled rows and subscriptions use `#999` ([css/gigpress.css:128](/Users/davidzenz/gigpress/css/gigpress.css:128), [css/gigpress.css:408](/Users/davidzenz/gigpress/css/gigpress.css:408)). These fixed colors conflict with D-13’s theme inheritance intent and `#999` is too faint for normal text against common light backgrounds.
- **WARNING:** The status treatment uses multiple overriding hard-coded declarations, including `#fffdeb`, then `#111`/`#FFF`, in one rule ([css/gigpress.css:153](/Users/davidzenz/gigpress/css/gigpress.css:153)). The dead declarations make the actual color treatment difficult for theme owners to predict and do not establish a controlled accent distribution.
- Code scan found no custom color token system and no CSS variables for these colors. The audit cannot establish a 60/30/10 distribution from code alone; screenshots were unavailable.

### Pillar 4: Typography (2/4)

- **WARNING:** The responsive listing correctly inherits the theme font family, but it still changes detail text to `90%` on wider layouts ([css/gigpress.css:116](/Users/davidzenz/gigpress/css/gigpress.css:116)) and overrides it only for narrow bundled details ([css/gigpress.css:310](/Users/davidzenz/gigpress/css/gigpress.css:310)). This produces a breakpoint-dependent hierarchy without a declared scale.
- **WARNING:** Legacy calendar-panel content is fixed at `11px` with a 16px line-height ([css/gigpress.css:394](/Users/davidzenz/gigpress/css/gigpress.css:394)). Owner-controlled tables may still activate that panel, leaving a small-text interaction in the shipped public CSS.
- **WARNING:** Subscription link text is visually displaced by `text-indent: -9999px` ([css/gigpress.css:413](/Users/davidzenz/gigpress/css/gigpress.css:413)), eliminating the text hierarchy rather than merely de-emphasizing it.

### Pillar 5: Spacing (3/4)

- **WARNING:** The narrow listing uses a consistent, readable rhythm: `0.7em 0.5em 0.55em` show padding, `0.25em` row gap, and divided detail sections ([css/gigpress.css:285](/Users/davidzenz/gigpress/css/gigpress.css:285), [css/gigpress.css:305](/Users/davidzenz/gigpress/css/gigpress.css:305)). This substantially meets D-04’s divider-and-spacing requirement.
- **WARNING:** The planned subscription flex gap is present ([css/gigpress.css:231](/Users/davidzenz/gigpress/css/gigpress.css:231)), but the later icon rule restores a fixed 12px width and zeroes the intended wider link presentation ([css/gigpress.css:413](/Users/davidzenz/gigpress/css/gigpress.css:413)). The resulting touch/focus target spacing is inadequate.
- The Phase 04 responsive additions use relative `em`/`ch` values and no plugin-defined main-content maximum width, consistent with D-15. Exact 320px bounds could not be remeasured without a running fixture.

### Pillar 6: Experience Design (2/4)

- **BLOCKER:** The subscription experience fails its readable-action contract because final CSS hides the `RSS` and `iCal` labels ([templates/shows-list-footer.php:18](/Users/davidzenz/gigpress/templates/shows-list-footer.php:18), [css/gigpress.css:413](/Users/davidzenz/gigpress/css/gigpress.css:413)). The title attribute is insufficient as an always-visible label and unavailable on touch.
- **WARNING:** Per-show calendar actions are direct anchors rendered without JavaScript ([templates/shows-list.php:44](/Users/davidzenz/gigpress/templates/shows-list.php:44)), which satisfies the core interaction architecture. However, no bundled focus-visible style exists for these, ticket, or subscription links.
- **WARNING:** The listing has an empty state ([templates/shows-list-empty.php:12](/Users/davidzenz/gigpress/templates/shows-list-empty.php:12)) and statically conditional detail lines, but no loading, error, disabled-action, or recovery state is represented in public template code. A static WordPress listing does not require loading UI, yet feed/subscription failures offer no user-facing fallback.
- Interaction findings are code-derived. No interaction state was captured in this audit.

---

## Files Audited

- `templates/shows-list-start.php`
- `templates/shows-list.php`
- `templates/shows-list-end.php`
- `templates/shows-list-footer.php`
- `templates/shows-list-empty.php`
- `templates/sidebar-list.php`
- `templates/sidebar-list-footer.php`
- `templates/related.php`
- `output/gigpress_shows.php`
- `output/gigpress_related.php`
- `output/gigpress_sidebar.php`
- `css/gigpress.css`
- `docs/public-template-adoption.md`
- `.planning/phases/04-public-publishing/04-CONTEXT.md`
- `.planning/phases/04-public-publishing/04-01-SUMMARY.md` through `04-05-SUMMARY.md`
- `.planning/phases/04-public-publishing/04-01-PLAN.md` through `04-05-PLAN.md`

Registry audit: skipped; `components.json` is absent, so no shadcn or third-party registry source is installed for review.
