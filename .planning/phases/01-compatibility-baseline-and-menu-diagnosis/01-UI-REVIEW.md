# Phase 01 — UI Review

**Audited:** 2026-10-13  
**Baseline:** Abstract 6-pillar standards (no `UI-SPEC.md` exists for Phase 01)  
**Screenshots:** Not captured (no dev server on localhost ports 3000, 5173, or 8080)  
**Interaction captures:** off (workflow.ui_interaction_capture is false)

---

## Pillar Scores

| Pillar | Score | Key Finding |
|--------|-------|-------------|
| 1. Copywriting | 2/4 | The PHP-floor notice identifies the incompatibility but gives an administrator no next action. |
| 2. Visuals | 2/4 | Native WordPress menu primitives are used correctly, but no rendered desktop, tablet, or mobile state was available to validate hierarchy, clipping, or warning prominence. |
| 3. Color | 2/4 | The Phase 01 warning uses the native WordPress warning treatment, while the applied admin stylesheet retains 17 hard-coded hex colors with no token system. |
| 4. Typography | 2/4 | The admin stylesheet mixes five font-size forms and three weight forms, including a fixed 11px status treatment. |
| 5. Spacing | 2/4 | The admin stylesheet has 24 spacing declarations, including 12 fixed-pixel instances, with no documented scale. |
| 6. Experience Design | 2/4 | The compatibility guard safely limits the low-PHP path, but the persistent notice has no remediation link and lacks explicit alert semantics. |

**Overall: 12/24**

---

## Top 3 Priority Fixes

1. **Make the low-PHP notice actionable and announced** — Administrators are told the site is incompatible but cannot immediately resolve it or receive a guaranteed screen-reader announcement — add `role="alert"` and concise remediation such as “Ask your host to upgrade PHP to 8.3 or newer,” with a safe documentation link if one exists.
2. **Replace legacy admin color literals with WordPress admin design tokens** — The inherited stylesheet can produce inconsistent contrast and visual language on the Phase 01 admin screens — map error, warning, focus, and muted states to WordPress semantic variables or native notice/button classes; validate normal and high-contrast themes.
3. **Establish a compact type and spacing scale for GigPress admin UI** — Mixed `em`, `%`, and pixel values, including 11px copy, create unstable hierarchy and scaling — define a small rem-based scale and replace the legacy fixed values, then validate at 200% browser zoom.

---

## Detailed Findings

### Pillar 1: Copywriting (2/4)

- **WARNING:** `gigpress.php:29` says that PHP is incompatible, but omits the responsible action and route to resolution. The message should state who needs to act and what version to request. This matters because the guard intentionally disables the plugin’s normal UI below the runtime floor.
- **WARNING:** The affected navigation uses clear, task-based labels (`Add a show`, `Shows`, `Artists`, `Venues`, `Tours`, `Settings`, and `Import/Export`) at `gigpress.php:85-104`. That is good coverage for the Phase 01 menu, but it does not compensate for the unusable compatibility recovery path.

### Pillar 2: Visuals (2/4)

- **WARNING:** The menu is implemented through WordPress primitives at `gigpress.php:95-104` and uses the familiar calendar Dashicon at `gigpress.php:93`, which supports recognition. The ordering transform places the separator and GigPress after Comments at `gigpress.php:595-620`.
- **WARNING:** No visual capture was possible, so the audit could not verify the actual WordPress sidebar result at any viewport, the appearance of the blank separator row, focus treatment, notice wrapping, or menu collision behavior. The matrix record proves menu availability, but it is not a substitute for a rendered capture.

### Pillar 3: Color (2/4)

- **WARNING:** The Phase 01 compatibility message relies on the native `.notice.notice-warning` class at `gigpress.php:29`. This is appropriate for a WordPress warning and keeps the new component within the host application’s visual language.
- **WARNING:** `css/gigpress-admin.css` contains 20 hard-coded color declarations and 17 distinct hex colors. Examples include custom error/focus colors at `css/gigpress-admin.css:13-19,27-35`, raw `red` at `:57-59`, and legacy error colors at `:69-72`. There is no semantic token layer or evidence that the resulting palette meets a deliberate 60/30/10 distribution or contrast target.

### Pillar 4: Typography (2/4)

- **WARNING:** `css/gigpress-admin.css` mixes `1.2em` (`:4-7`), `110%` (`:91-94`), `11px` (`:109-121`), `90%` (`:127-137`), and `0.9em` elsewhere in the file. This makes hierarchy dependent on parent context and can produce undersized support text.
- **WARNING:** The fixed `font-size: 11px` in `#artist-sort-update` at `css/gigpress-admin.css:109-121` is especially likely to be difficult to read. The stylesheet also mixes `font-weight: 600` and `bold` without a documented type scale.

### Pillar 5: Spacing (2/4)

- **WARNING:** The applied admin stylesheet has 24 margin or padding declarations and 12 declarations with fixed pixel spacing or sizing. Examples include `padding: 3px` at `css/gigpress-admin.css:74-80`, `margin: 5px 0` at `:109-121`, and `margin: 5px 0 0` at `:145-147`.
- **WARNING:** The Phase 01 notice itself uses host-provided WordPress spacing, but GigPress admin screens around it inherit these unstructured legacy values. No responsive or zoom-specific validation was available because a runnable browser target was absent.

### Pillar 6: Experience Design (2/4)

- **WARNING:** The PHP guard branches before normal modules and hooks at `gigpress.php:26-34`, and the notice is limited to users with `activate_plugins` at `:27-33`. This meets the critical authorization and inertness intent.
- **WARNING:** The rendered notice at `gigpress.php:29` has no `role="alert"`, `aria-live`, remediation link, or next-step text. A persistent warning is easy to repeatedly dismiss mentally, especially when it provides no direct recovery path.
- **WARNING:** `custom_menu_order()` returns WordPress’s input unchanged for malformed or conflicted order data (`gigpress.php:595-605`) and performs a stable insert only after validating unique slugs (`:607-620`). This is a strong conflict fallback, but it was validated only through compatibility harness evidence because no interactive WordPress admin session was available.

---

## Files Audited

- `gigpress.php`
- `css/gigpress-admin.css`
- `scripts/gigpress-admin.js`
- `.planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-CONTEXT.md`
- `.planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-01-PLAN.md` through `01-05-PLAN.md`
- `.planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-01-SUMMARY.md` through `01-05-SUMMARY.md`
- `.planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-MENU-VERIFICATION.md`
- `.planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-DIAGNOSIS.md`

Registry audit: skipped; `components.json` is absent, so shadcn and third-party registry checks do not apply.
