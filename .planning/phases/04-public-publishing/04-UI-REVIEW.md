# Phase 04 — UI Review

**Audited:** 2026-10-07
**Baseline:** Abstract six-pillar standards plus Phase 04 public-publishing context (no approved `UI-SPEC.md`)
**Screenshots:** not captured (no local dev server on ports 3000, 5173, or 8080). Historical final-source browser observations in `04-BROWSER.md` are supporting evidence only.
**Interaction captures:** off (workflow.ui_interaction_capture is false)

---

## Pillar Scores

| Pillar | Score | Key Finding |
|--------|-------|-------------|
| 1. Copywriting | 3/4 | Main calendar actions are explicit, but grouped-artist subscriptions are unlabeled icons and the footer uses ambiguous `iCal` shorthand. |
| 2. Visuals | 3/4 | Historical 320px and wide evidence supports hierarchy and readable reflow; tiny artist-feed icons are not discoverable. |
| 3. Color | 2/4 | Fixed grays conflict with theme inheritance and `#999` cancelled text fails normal-text contrast on white. |
| 4. Typography | 3/4 | The main listing inherits theme type, but desktop detail text is reduced to 90% and legacy calendar panels force 11px text. |
| 5. Spacing | 3/4 | The mobile stack has consistent relative rhythm; artist and calendar actions rely on literal separators instead of spaced action groups. |
| 6. Experience Design | 3/4 | Direct no-JavaScript calendar/feed flows and empty states exist, but artist subscriptions lack explicit focus treatment or accessible names. |

**Overall: 17/24**

---

## Top 3 Priority Fixes

1. **WARNING — Label grouped artist subscriptions** — two 12px image-only links with empty image alternatives are difficult to find and unnamed for assistive technology — render visible `RSS` and `iCalendar` text, or provide anchor-level accessible names plus a visible group label.
2. **WARNING — Replace fixed status and detail colors** — cancelled desktop text is about 2.85:1 against white and fixed grays override theme intent — inherit theme foregrounds and apply a contrast-safe badge or documented semantic custom properties.
3. **WARNING — Extend focus and target treatment to every public subscription action** — artist subscription anchors depend on browser or theme defaults and use a nonbreaking-space separator — add a scoped `:focus-visible` rule and a flex or inline-flex action group with adequate padding and gap.

---

## Detailed Findings

### Pillar 1: Copywriting (3/4)

- **WARNING:** The main listing uses clear task labels, `Add to Google Calendar` and `Download iCalendar`, for its direct actions at [templates/shows-list.php:44](/Users/davidzenz/gigpress/templates/shows-list.php:44). Historical final-source browser evidence also records these links as visible and keyboard-operable with JavaScript blocked.
- **WARNING:** The subscription footer calls the web-calendar feed `iCal` at [templates/shows-list-footer.php:15](/Users/davidzenz/gigpress/templates/shows-list-footer.php:15). Use `iCalendar` or `Subscribe via iCalendar` in visitor-facing feed copy.
- **WARNING:** Artist headings use image-only RSS and calendar anchors whose images have `alt=""`; the anchors have only `title` text at [templates/shows-artist-heading.php:19](/Users/davidzenz/gigpress/templates/shows-artist-heading.php:19). Give each anchor a visible label or an `aria-label` naming the artist and action.
- **WARNING:** Empty output is a configurable sentence without a next action at [templates/shows-list-empty.php:12](/Users/davidzenz/gigpress/templates/shows-list-empty.php:12). A default that names the scope and links to a complete archive would make an empty listing recoverable.

### Pillar 2: Visuals (3/4)

- **WARNING:** Historical final-source observations measure no horizontal overflow at 320px and 1280px. The implementation supports that result with label markup at [templates/shows-list.php:16](/Users/davidzenz/gigpress/templates/shows-list.php:16), long-word wrapping at [css/gigpress.css:174](/Users/davidzenz/gigpress/css/gigpress.css:174), and a 42em stacked breakpoint at [css/gigpress.css:258](/Users/davidzenz/gigpress/css/gigpress.css:258). No fresh screenshot was available to independently confirm the current render.
- **WARNING:** Grouped artist feeds are visually just two small, floated glyphs at [templates/shows-artist-heading.php:19](/Users/davidzenz/gigpress/templates/shows-artist-heading.php:19) and [css/gigpress.css:12](/Users/davidzenz/gigpress/css/gigpress.css:12). They compete with the heading and do not establish an identifiable feed action hierarchy.
- **WARNING:** The grouped status treatment uses border, uppercase and theme-dependent foreground at [css/gigpress.css:222](/Users/davidzenz/gigpress/css/gigpress.css:222); its visual distinction is weaker on themes whose foreground and border colors are similar.

### Pillar 3: Color (2/4)

- **WARNING:** Public CSS hard-codes multiple grays and fills, including `#333`, `#666`, `#999`, `#111`, `#EEE`, and `#F6F6F6` at [css/gigpress.css:29](/Users/davidzenz/gigpress/css/gigpress.css:29), [css/gigpress.css:116](/Users/davidzenz/gigpress/css/gigpress.css:116), and [css/gigpress.css:127](/Users/davidzenz/gigpress/css/gigpress.css:127). With no defined token system, this prevents the stated theme-color inheritance from being reliable.
- **WARNING:** Desktop cancelled content is set to `#999` at [css/gigpress.css:127](/Users/davidzenz/gigpress/css/gigpress.css:127), about 2.85:1 on white, below the normal-text contrast target. The narrow layout resets it to inherited color at [css/gigpress.css:319](/Users/davidzenz/gigpress/css/gigpress.css:319), so the same status has inconsistent contrast by viewport.
- **WARNING:** The status rule declares `#fffdeb`, `#111`, `#FFF`, and a border before overwriting several of them at [css/gigpress.css:153](/Users/davidzenz/gigpress/css/gigpress.css:153). Remove dead declarations and express the intended badge colors once through semantic variables.

### Pillar 4: Typography (3/4)

- **WARNING:** The current main markup does not set a font family or fixed scale, preserving theme inheritance. Historical final-source evidence records inherited `Manrope, sans-serif`; mobile detail text returns to `100%` at [css/gigpress.css:297](/Users/davidzenz/gigpress/css/gigpress.css:297).
- **WARNING:** Wide-screen show details are forced to `90%` at [css/gigpress.css:115](/Users/davidzenz/gigpress/css/gigpress.css:115), then become `100%` on the smaller layout. Key logistical details should retain the base text size unless their lower priority is deliberate and tested.
- **WARNING:** Owner templates retaining the legacy calendar popover inherit a fixed `11px` size at [css/gigpress.css:386](/Users/davidzenz/gigpress/css/gigpress.css:386). Replace it with a relative size and line-height compatible with the host theme.

### Pillar 5: Spacing (3/4)

- **WARNING:** The narrow bundled layout uses a coherent relative rhythm: 0.25em row gap, 0.7em/0.5em/0.55em row padding, 0.2em detail spacing, and calendar spacing at [css/gigpress.css:277](/Users/davidzenz/gigpress/css/gigpress.css:277). Historical browser observations support its readable 320px reflow, but no fresh capture was produced.
- **WARNING:** Per-show calendar links use a literal `|` at [templates/shows-list.php:46](/Users/davidzenz/gigpress/templates/shows-list.php:46), while artist subscriptions use `&nbsp;` between two icons at [templates/shows-artist-heading.php:21](/Users/davidzenz/gigpress/templates/shows-artist-heading.php:21). Wrap each action set in a flex group with `gap` so spacing remains stable as text wraps or zoom increases.
- **WARNING:** Footer text links have a 2.25em minimum height at [css/gigpress.css:420](/Users/davidzenz/gigpress/css/gigpress.css:420), but artist subscription icons have no matching target-size rule. Give those controls the same minimum interactive area.

### Pillar 6: Experience Design (3/4)

- **WARNING:** The core visitor route is robust: main calendar actions are ordinary anchors at [templates/shows-list.php:44](/Users/davidzenz/gigpress/templates/shows-list.php:44), and historical final-source evidence records keyboard activation of ticket, Google Calendar, iCalendar, RSS, and webcal links with JavaScript disabled. This audit did not capture a new interaction state.
- **WARNING:** The explicit focus outline applies to `.gigpress-layout-bundled`, `.gigpress-listing`, and footer links at [css/gigpress.css:433](/Users/davidzenz/gigpress/css/gigpress.css:433). Artist-heading subscriptions are outside those selectors despite their `gigpress-layout-bundled-heading` class at [templates/shows-artist-heading.php:12](/Users/davidzenz/gigpress/templates/shows-artist-heading.php:12), so focus visibility depends on the theme.
- **WARNING:** Main and sidebar empty states exist at [templates/shows-list-empty.php:12](/Users/davidzenz/gigpress/templates/shows-list-empty.php:12) and [templates/sidebar-list-empty.php:12](/Users/davidzenz/gigpress/templates/sidebar-list-empty.php:12). Server-rendered output does not need a client loading state, but the empty copy should guide the visitor to a meaningful next destination.
- **WARNING:** No confirmation or disabled state is needed for these navigation links. The unresolved accessibility issue is the artist feed controls: users cannot reliably discover, name, focus, or activate them as a coherent action group.

---

## Files Audited

- `templates/shows-list-start.php`, `templates/shows-list.php`, `templates/shows-list-footer.php`, `templates/shows-list-empty.php`, and `templates/shows-artist-heading.php`
- `templates/sidebar-list.php`, `templates/sidebar-list-empty.php`, and `templates/related.php`
- `output/gigpress_shows.php`, `output/gigpress_sidebar.php`, and `output/gigpress_related.php`
- `css/gigpress.css`, `scripts/gigpress.js`, and `docs/public-template-adoption.md`
- Phase 04 plans, summaries, context, UAT, browser evidence, and prior UI review

Registry audit: skipped; `components.json` is absent, so no shadcn or third-party registry source is installed for review.
