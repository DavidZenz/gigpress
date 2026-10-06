# Phase 04 — UI Review

**Audited:** 2026-10-06
**Baseline:** Abstract six-pillar standards plus Phase 04 public-publishing context (no approved `UI-SPEC.md`)
**Screenshots:** not captured in this audit (no local dev server on ports 3000, 5173, or 8080). Approved final-source screenshots and measurements from `04-BROWSER.md` were inspected as historical evidence.
**Interaction captures:** off (workflow.ui_interaction_capture is false)

---

## Pillar Scores

| Pillar | Score | Key Finding |
|--------|-------|-------------|
| 1. Copywriting | 3/4 | Main calendar actions are explicit, but grouped-artist feed controls are unlabeled icons and the visible `iCal` shorthand is ambiguous. |
| 2. Visuals | 3/4 | The 320px and 1280px rendered listing has clear hierarchy and no overflow; artist subscription controls remain tiny unlabeled icons. |
| 3. Color | 2/4 | Public CSS relies on fixed grays and a low-contrast `#999` cancelled state instead of theme-compatible semantic colors. |
| 4. Typography | 3/4 | The primary listing inherits the active theme and retains a restrained scale, while legacy public calendar panels still force 11px text. |
| 5. Spacing | 3/4 | The bundled mobile stack has a coherent relative rhythm; public calendar and subscription controls still use separator characters rather than self-contained, padded action targets. |
| 6. Experience Design | 3/4 | Direct no-JavaScript calendar/feed flows, empty states, and wrapped content are present; grouped feed links lack a visible action name and their focus treatment is delegated to the theme. |

**Overall: 17/24**

---

## Top 3 Priority Fixes

1. **WARNING — Give grouped artist feeds visible text** — the two 12px icons next to every artist heading are difficult to identify and their images have empty `alt` text. Render `RSS` and `iCalendar` text links, or retain the icons with explicit `aria-label` values and a visually visible label in the heading area.
2. **WARNING — Replace fixed public foreground colors with semantic, contrast-safe styling** — `#999` for cancelled rows fails normal-text contrast on white and fixed grays override theme intent. Use inherited foreground plus a class/badge treatment, or documented CSS custom properties with a contrast-safe fallback; remove overridden status declarations while doing so.
3. **WARNING — Extend the bundled focus and target treatment to all public feed/calendar links** — explicit focus styling currently misses artist-heading subscriptions and legacy owner tables. Add a scoped public-link `:focus-visible` rule and give all direct action links an adequate line-height/padding without relying on a literal `|` or nonbreaking-space separator.

---

## Detailed Findings

### Pillar 1: Copywriting (3/4)

- **WARNING:** Per-show actions use clear task language, `Add to Google Calendar` and `Download iCalendar` ([templates/shows-list.php:44](/Users/davidzenz/gigpress/templates/shows-list.php:44)). The approved final-source browser evidence confirms both links were visible at 320px and keyboard-activated with JavaScript blocked.
- **WARNING:** The footer makes its feed purpose visible, but calls the calendar feed `iCal` rather than `iCalendar` ([templates/shows-list-footer.php:15](/Users/davidzenz/gigpress/templates/shows-list-footer.php:15)). `iCal` is a legacy shorthand and less explicit for visitors who do not know it means a web calendar subscription.
- **WARNING:** Artist headings render two icon-only links whose child images have `alt=""`; the anchors supply only `title` text ([templates/shows-artist-heading.php:18](/Users/davidzenz/gigpress/templates/shows-artist-heading.php:18)). The wide and mobile screenshots show these as unexplained feed/calendar glyphs. Use visible labels or anchor-level `aria-label` values that identify both the feed type and artist.
- **WARNING:** Empty output is a configurable message with no built-in next step ([templates/shows-list-empty.php:12](/Users/davidzenz/gigpress/templates/shows-list-empty.php:12), [templates/sidebar-list-empty.php:12](/Users/davidzenz/gigpress/templates/sidebar-list-empty.php:12)). A concise default that names the scope and points to a complete archive would make the state more recoverable.

### Pillar 2: Visuals (3/4)

- **WARNING:** The approved 320px screenshot shows the date-first stack with in-flow field labels, distinct action links, and readable dividers; the approved 1280px screenshot shows a conventional table with the long venue wrapping in its own column. `04-BROWSER.md` records no horizontal overflow at either width, and the corresponding CSS uses `overflow-wrap: anywhere` and a 42em breakpoint ([css/gigpress.css:174](/Users/davidzenz/gigpress/css/gigpress.css:174), [css/gigpress.css:258](/Users/davidzenz/gigpress/css/gigpress.css:258)).
- **WARNING:** Grouped artist subscription controls are visually only two 12px icons aligned to the right ([templates/shows-artist-heading.php:18](/Users/davidzenz/gigpress/templates/shows-artist-heading.php:18), [css/gigpress.css:12](/Users/davidzenz/gigpress/css/gigpress.css:12)). In the inspected mobile screenshot they compete with the heading without a legend, making the available feeds hard to discover.
- **WARNING:** The UI intentionally preserves owner control for custom and mixed templates ([public-template-adoption.md:20](/Users/davidzenz/gigpress/docs/public-template-adoption.md:20)). The approved complete-override screenshot demonstrates why this matters: it is a much denser, date-only owner layout. This is not a bundled-layout defect, but documentation should keep directing owners to the explicit opt-in marker before they expect responsive visual quality.

### Pillar 3: Color (2/4)

- **WARNING:** The public stylesheet hard-codes borders, background fills, and foreground grays throughout the listing, including `#333`, `#666`, and `#999` ([css/gigpress.css:29](/Users/davidzenz/gigpress/css/gigpress.css:29), [css/gigpress.css:116](/Users/davidzenz/gigpress/css/gigpress.css:116)). This undermines the stated theme inheritance for a public component and prevents a controlled semantic color system.
- **WARNING:** Cancelled shows use `#999` ([css/gigpress.css:127](/Users/davidzenz/gigpress/css/gigpress.css:127)), approximately 2.85:1 against white, below the normal-text contrast target. The mobile bundled layout resets this to inherited color ([css/gigpress.css:319](/Users/davidzenz/gigpress/css/gigpress.css:319)), creating different status contrast between desktop and mobile.
- **WARNING:** The status rule contains superseded declarations for `#fffdeb`, `#111`, `#FFF`, and a border before later declarations overwrite them ([css/gigpress.css:153](/Users/davidzenz/gigpress/css/gigpress.css:153)). This obscures the intended accent treatment and makes theme adaptation needlessly uncertain.

### Pillar 4: Typography (3/4)

- **WARNING:** The refreshed browser evidence confirms the bundled listing inherited `Manrope, sans-serif` from the theme, and the 320px detail row returns to `100%` size ([04-BROWSER.md](/Users/davidzenz/gigpress/.planning/phases/04-public-publishing/04-BROWSER.md), [css/gigpress.css:297](/Users/davidzenz/gigpress/css/gigpress.css:297)). The main public surface therefore has a readable, restrained type hierarchy.
- **WARNING:** At wider widths, show details are forced to `90%` ([css/gigpress.css:115](/Users/davidzenz/gigpress/css/gigpress.css:115)), then change to `100%` below 42em. The breakpoint-specific size shift lacks a declared semantic reason and can make key detail text less legible on large displays.
- **WARNING:** Legacy public calendar panels still set `font-size: 11px` and `line-height: 16px` ([css/gigpress.css:376](/Users/davidzenz/gigpress/css/gigpress.css:376)). The bundled listing avoids this UI, but owner templates that retain the old hooks can still ship below a comfortable reading size.

### Pillar 5: Spacing (3/4)

- **WARNING:** The narrow bundled layout uses a consistent relative rhythm: a 0.25em grid gap, 0.7em/0.5em/0.55em row padding, 0.2em detail gaps, and 0.35em/0.55em calendar-action spacing ([css/gigpress.css:277](/Users/davidzenz/gigpress/css/gigpress.css:277)). The accepted 320px screenshot visibly supports this finding.
- **WARNING:** Footer links now correctly override the former icon-only cascade with `inline-flex`, a 2.25em minimum height, visible text, and wrapping ([css/gigpress.css:420](/Users/davidzenz/gigpress/css/gigpress.css:420)). This resolves the prior review’s report that labels were hidden.
- **WARNING:** Per-show calendar links still depend on a literal `|` separator ([templates/shows-list.php:46](/Users/davidzenz/gigpress/templates/shows-list.php:46)), and artist links use `&nbsp;` between two bare icons ([templates/shows-artist-heading.php:19](/Users/davidzenz/gigpress/templates/shows-artist-heading.php:19)). Group the links with gap/padding styling so target separation remains intentional when text wraps or user font size increases.

### Pillar 6: Experience Design (3/4)

- **WARNING:** The core calendar journey is strong: the PHP emits ordinary anchors without a JavaScript prerequisite ([templates/shows-list.php:44](/Users/davidzenz/gigpress/templates/shows-list.php:44)). The accepted final-source record confirms keyboard activation of ticket, Google Calendar, iCalendar, RSS, and webcal links with JavaScript blocked, plus successful calendar-import validation.
- **WARNING:** Bundled table, compact-list, and footer links receive a visible `currentColor` focus outline ([css/gigpress.css:433](/Users/davidzenz/gigpress/css/gigpress.css:433)). Artist-heading subscription anchors do not match any of those selectors: their heading has `gigpress-layout-bundled-heading`, not `gigpress-layout-bundled` ([templates/shows-artist-heading.php:12](/Users/davidzenz/gigpress/templates/shows-artist-heading.php:12)). Their keyboard focus therefore depends on the active theme.
- **WARNING:** The listing and sidebar both implement an empty state ([templates/shows-list-empty.php:12](/Users/davidzenz/gigpress/templates/shows-list-empty.php:12), [templates/sidebar-list-empty.php:12](/Users/davidzenz/gigpress/templates/sidebar-list-empty.php:12)). There is no loading state because public output is server-rendered. Native feed/navigation failures rely on browser handling; no inline asynchronous error state is expected from these templates.
- Interaction findings beyond the accepted historical keyboard evidence are code-derived. No new interaction state was captured in this audit.

---

## Files Audited

- `templates/shows-list-start.php`
- `templates/shows-list.php`
- `templates/shows-list-end.php`
- `templates/shows-list-footer.php`
- `templates/shows-list-empty.php`
- `templates/shows-artist-heading.php`
- `templates/sidebar-list.php`
- `templates/sidebar-list-footer.php`
- `templates/sidebar-list-empty.php`
- `templates/related.php`
- `output/gigpress_shows.php`
- `output/gigpress_related.php`
- `output/gigpress_sidebar.php`
- `css/gigpress.css`
- `docs/public-template-adoption.md`
- `.planning/phases/04-public-publishing/04-CONTEXT.md`
- `.planning/phases/04-public-publishing/04-01-SUMMARY.md` through `04-05-SUMMARY.md`
- `.planning/phases/04-public-publishing/04-01-PLAN.md` through `04-05-PLAN.md`
- `.planning/phases/04-public-publishing/04-BROWSER.md`
- `.planning/phases/04-public-publishing/04-UAT.md`

Registry audit: skipped; `components.json` is absent, so no shadcn or third-party registry source is installed for review.
