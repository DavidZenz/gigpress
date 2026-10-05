# Phase 03 — UI Review

**Audited:** 2026-10-05
**Baseline:** Abstract six-pillar standards, locked D-01–D-18, and existing WordPress administration patterns; no UI-SPEC.md exists.
**Screenshots:** Not captured (no dev server reached on localhost:3000, 5173 or 8080; disposable fixture already removed).
**Interaction captures:** off
**Evidence:** Code review plus the explicitly recorded browser observations in 03-BROWSER.md. No new browser interaction was performed. HTTP/integration assertions establish data and markup contracts, not rendered appearance, keyboard traversal or disabled-script behavior.

## Pillar Scores

| Pillar | Score | Key Finding |
|--------|-------|-------------|
| 1. Copywriting | 3/4 | Specific guidance and agreed labels are present; confirmation/result plurals and normal date help need refinement. |
| 2. Visuals | 3/4 | Clear native sections/actions; constrained selects and inconsistent page headings weaken hierarchy and identity recognition. |
| 3. Color | 2/4 | Cancelled rows inherit low-contrast #999 text; added error/focus colors are fixed rather than scheme-aware. |
| 4. Typography | 3/4 | Mostly inherited WordPress typography; supporting row content is demoted with small and page headings remain inconsistent. |
| 5. Spacing | 2/4 | Five labeled filters share a native fixed-height navigation container without scoped wrapping/height rules. |
| 6. Experience Design | 2/4 | Useful recovery and confirmation are evidenced, but required no-JS/full keyboard and remaining recovery sequences are pending. |

**Overall: 15/24**

Scores are independent. No confirmed task-breaking defect was established in this audit; pending interaction acceptance prevents shipping certification. Findings below distinguish a source defect from a layout risk and an evidence gap.

## Top 3 Priority Fixes

1. **Complete required interaction acceptance** — D-18 cannot be certified from semantic markup or HTTP — repeat genuinely disabled-script entry/list, full keyboard filter/pagination/Reset and automatic notice focus, plus the remaining recovery/mixed outcomes on the disposable fixture; record observed results and fix any failures.
2. **Make list controls and long identities usable at narrow widths** — crowded navigation and capped names can impede filtering/selection — add a scoped wrapping filter layout with natural height; check the eight-column table and long names at 375, 768 and 1440px, using native WordPress layout and an explicit table overflow strategy where needed.
3. **Restore readable cancelled-row text** — #999 carries record details below normal text contrast — retain a readable foreground and communicate cancelled state with text; verify ordinary, alternate and linked row content in supported WordPress admin color schemes.

## Detailed Findings

### Pillar 1: Copywriting (3/4)

- **WARNING C1 — Confirmation and result grammar:** admin/handlers.php:418 uses `%d selected show(s)`; :496 and :908 use plural `shows` even for one. The recorded one-show flow therefore receives mechanically plural feedback. Use translated singular/plural forms (`_n`) for selection, moved and restored counts. Confirm/Cancel itself is explicit and correctly explains that Cancel keeps the shows.
- **WARNING C2 — Recovery language on normal entry:** admin/new.php:29 always says “The received value can be corrected here,” including a fresh native picker. Reserve that sentence for raw recovery; give the ordinary picker short date-entry guidance. Required labels, optional-time explanation and end-date cutoff help are present at :140–149. Exact empty-match/Reset wording is present in admin/shows.php:111.
- Positive evidence: admin/settings.php:9–53 supplies concise explanations and format examples; :120–121 links longer formatting guidance. admin/handlers.php:248–264 gives success/Edit/List and field-linked failure text. These do not rely on color alone.

### Pillar 2: Visuals (3/4)

- **WARNING V1 — Long identity recognition, code-derived risk:** css/gigpress-admin.css:45–46 caps every GigPress select at 180px. Entry venue labels include name plus city (admin/new.php:118); the same cap covers settings current-value choices and all entity filters. Long Unicode names may be visually indistinguishable when closed. Inspect actual long fixtures, allow scoped wider/responsive selects, and provide an adjacent full selected identity when the control truncates it. No truncation screenshot was captured.
- **WARNING V2 — Page hierarchy:** entry and settings use h1 (admin/new.php:130, admin/settings.php:58); list and confirmation use h2 as their page title (admin/shows.php:42, admin/handlers.php:418). Align the changed surfaces with native page h1 and retain h2 for sections.
- Positive evidence: entry has three named sections (:139, :150, :187); settings has six immediately visible sections and one primary save (:69–131); confirmation has one primary Confirm and ordinary Cancel (:425). All new actions use text, with row Trash also carrying an identifying aria-label (admin/shows.php:99). The browser report observed all six settings targets and exact selected identities. Pixel hierarchy and responsive appearance remain unobserved.

### Pillar 3: Color (2/4)

- **WARNING K1 — Readability defect in an affected surface:** css/gigpress-admin.css:49–50 sets cancelled-row td text to #999. Approximate contrast is 2.85:1 against white and 2.53:1 against #f1f1f1, below 4.5:1 for ordinary text. The rewritten list still emits `gigpress-cancelled` rows (admin/shows.php:91, :102). This is inherited styling carried into the changed screen, not a newly introduced palette. Use readable text and a clear status label; inspect links separately because link styles may override inheritance.
- **WARNING K2 — Scheme compatibility:** the phase adds two literal colors, #b32d2e for error text/borders (:14, :19) and #2271b1 for focus (:33), four literal uses total. They correspond to recognizable native defaults, but are fixed across admin color schemes. Check focus/error contrast in supported schemes and prefer existing native state classes or scoped theme-compatible values. No scheme or screenshot evidence establishes contrast for every background.
- No accent-overuse defect is established: changed templates use standard button/notice classes and decorative accents are limited. Tailwind counts and a branded 60/30/10 target are inapplicable to this native administration screen; rendered area distribution was not measured. Text errors, counts and Cancel explanations remain present regardless of color.

### Pillar 4: Typography (3/4)

- **WARNING T1 — Supporting details are unnecessarily small:** admin/shows.php:102–109 wraps the entire second show row, including time, price, ticket information, notes and related edit link, in `small`. These details can be material to identifying a show. Use normal native body size for actionable/identifying content and demote only genuinely secondary metadata after visual review.
- Changed CSS adds one explicit font size (1.2em section headings, css/gigpress-admin.css:6) and one explicit weight (600 field errors, :15). Most control/body typography remains WordPress-owned. Existing shared CSS includes bold dates and 90% details (:123–137), rather than a newly imposed type system. The h1/h2 inconsistency is detailed in V2. No font-size proliferation in the added phase styles was found; perceived legibility at mobile/zoom was not observed.

### Pillar 5: Spacing (2/4)

- **WARNING S1 — Filter-bar wrapping, code-derived risk:** admin/shows.php:53–75 places five visible labels/selects, Filter, Reset and pagination in a single `.tablenav` containing an `.alignleft` form. Each label/select is emitted inline (:128–131); no phase CSS supplies this bar with wrapping gaps or natural height. Native navigation sizing plus long labels can crowd or overlap the table when controls wrap. Add a list-specific class with a wrapping layout, consistent native gaps and auto height, then inspect narrow viewport and 200% zoom behavior. Actual overflow has not been observed.
- **WARNING S2 — Entry width and shared-style scope:** admin/new.php:195 sets the notes textarea to 45 columns with no entry-specific max-width; settings alone receives max-width:100% (:240–241). Check entry fields at mobile widths and constrain them within the native form area where required. css/gigpress-admin.css:210–221 also applies padding/fieldset/legend rules globally; scope future adjustments to GigPress so WordPress elements outside the owned form do not inherit them.
- Added spacing uses 1em entry heading/label margins, a 48em help width, .5em/1.5em jump gaps, 2em settings sections and 3em scroll margin. There is no declared spacing scale to violate. The settings jump links do wrap (:224–228); that same care is absent from the list filter bar. No arbitrary Tailwind spacing exists.

### Pillar 6: Experience Design (2/4)

- **WARNING E1 — Required browser acceptance remains partial:** 03-BROWSER.md, “Required human checks still pending,” explicitly lists genuinely disabled JavaScript entry/list, full Tab/Enter traversal of filter/pagination/Reset and automatic notice focus, complete new artist/venue/tour/post and related-date-radio recovery, no-time versus exact midnight, and mixed preview-to-confirm outcomes. Attempted Enter on list navigation did not navigate in that recorded run; this is an unresolved observation, not proof of either a working path or a production keyboard defect. Repeat those exact actions in a browser and record the result. A reproducible inaccessible control or lost recovery state would be a BLOCKER.
- Source supports progressive behavior: admin/new.php:23–46 renders an authoritative editable raw date plus explicitly selected replacement; :162–184 renders new-choice fields server-side. scripts/gigpress-admin.js:6–30 enhances reveal/minutes without forced blur, :32–45 links errors to focus targets and requests automatic feedback focus. That focus request has not been observed automatically. With scripts disabled, select-all headers (admin/shows.php:82) have no server selection function, while named row checkboxes (:92) still permit explicit selection; test and clarify this enhancement in the no-JS review.
- Positive observed subset: native picker, optional-minute enablement, multi-day Space without blur, equal dates, successful fresh add/Edit/List, raw correction and explicit replacement, error-link focus, corrected-edit rendering, six keyboard settings jumps/save, selected two-ID Cancel and single-ID Confirm/Undo. The prior stale corrected-edit display defect is fixed by 63f0ace and the browser report records a successful retest; it is not an open finding here.
- State coverage is appropriate to synchronous WordPress forms: field/system errors and retained values (admin/new.php:80–100; admin/handlers.php:162, :223, :254–264); empty matches (:111); minute disabled behavior (JS:13–18); explicit server preview/Confirm/Cancel (handlers :417–425); per-ID results and changed-only Undo (:496–500). No asynchronous loading skeleton is required for these server requests. HTTP snapshots and integration results support authority/preservation; they do not close E1.

## Flagged Product-Intent Review Items

These items remain **flagged-unverified**, as required by the plans and 03-VALIDATION.md. No descriptor, automated prohibition outcome or unidentified user intent is invented.

| Item | Available evidence | Remaining boundary |
|------|--------------------|--------------------|
| 03-01 prohibition: date entry must not silently redefine existing upcoming cutoff or require time | Existing sentinel/expiration code and matrix checks; browser equal-date save and optional-time controls | Descriptor-less prohibition retains unresolved status; full no-time/midnight browser sequence pending. |
| 03-03 prohibition: confirmation must not present filtered rows as selected or imply Cancel continues trash | Exact IDs/identity preview and Cancel text; observed two-ID Cancel and single-ID Confirm | Descriptor-less prohibition retains unresolved status; disabled-script and mixed-outcome sequences pending. |
| 03-02 prohibition: grouping must not hide Advanced or imply unchanged-save configuration reset | Six visible sections, observed Advanced/jumps; options.php preservation snapshots | Descriptor-less prohibition retains unresolved status; no matrix-only intent certification. |
| ADMIN-03/unclassified assumption | Explicit scalar/nested/unknown/falsey preservation tests and unchanged meanings are supplied | Unidentified intent remains unresolved; preservation assertions cannot define or resolve it. |

## Audit Limits and Follow-Up

No source changes, containers, installations or commits were made. Existing capture ignore gate was checked: all seven image-extension patterns plus interaction/ are already present in .planning/ui-reviews/.gitignore. No captures were created. components.json is absent, so the shadcn registry audit is inapplicable and was skipped.

Visual/color/spacing conclusions are code-derived; no desktop/mobile/tablet rendering, color-scheme rendering, assistive announcement or zoom result is claimed. Preserve the WordPress UI and fix scoped deficiencies; this review does not request a framework redesign or expand public/CSV work into Phase 03. Recommendation count: 3 priority actions; 7 additional refinements (C1, C2, V1 selected-identity detail, V2, K2, T1, S2). S1/K1/E1 are covered by the priorities.

## Files Audited

- admin/new.php
- admin/settings.php
- admin/shows.php
- admin/handlers.php (changed entry/list outcome and confirmation paths)
- gigpress.php (changed settings registration/sanitizer)
- scripts/gigpress-admin.js
- css/gigpress-admin.css
- .planning/phases/03-administration-workflows/03-CONTEXT.md
- .planning/phases/03-administration-workflows/03-01-PLAN.md through 03-04-PLAN.md
- .planning/phases/03-administration-workflows/03-01-SUMMARY.md through 03-04-SUMMARY.md
- .planning/phases/03-administration-workflows/03-BROWSER.md
- .planning/phases/03-administration-workflows/03-VALIDATION.md
- /tmp/gigpress-03-review-scope.json
