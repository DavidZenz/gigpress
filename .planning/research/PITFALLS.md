# Pitfalls Research

**Domain:** Modernizing a legacy procedural WordPress plugin (GigPress)
**Researched:** 2026-10-03
**Confidence:** MEDIUM

## Critical Pitfalls

### Pitfall 1: Mutating the global admin menu from inside `menu_order`

**Severity:** High — can emit admin warnings and destabilize menu sorting.

**What goes wrong:** WordPress builds `$menu_order` and `$default_menu_order` from the global `$menu`, then calls the `menu_order` filter. Core sorts actual `$menu` rows against those maps. In the checked repository, GigPress's `custom_menu_order()` adds a separator row to global `$menu` inside that callback and includes `separator-gp` in the returned ordering. This means core's original-order snapshot does not contain that newly-added row. In the 7.1.2 comparator, the warning key comes from a real `$menu` row's slug when that slug is absent from both the filtered ordering map and the original default-order map. The reported `separator-gigpress` therefore proves a slug/order mismatch at sort time, but does not by itself identify which active plugin callback introduced it. This checkout uses `separator-gp`, not the reported slug; the deployed plugin version or another menu-order callback may differ.

**Why it happens:** The plugin treats the menu order list as if it were also the menu registry. They are separate contracts. WordPress documents `custom_menu_order` as a boolean gate and `menu_order` as an ordered list of menu slugs; its core sorting logic snapshots default slugs before applying the latter.

**How to avoid:** First capture the exact installed GigPress version and active callbacks. Log/inspect `$menu`, the default slugs, and the value returned by every `menu_order` callback on the affected site. Reproduce with only GigPress enabled and compare the plugin's menu mutations. Prefer removing custom reordering if preserving it is unnecessary; otherwise keep the filter limited to ordering existing slugs, use strict `array_search`, handle index `0` correctly, and avoid adding `$menu` entries from the filter. If a separator is required, add it at a menu-registration stage before core snapshots order and verify its slug exists exactly once in both arrays. Do not silence the warning by suppressing diagnostics or inventing a default array entry.

**Warning signs:** Missing-key warnings mentioning a separator slug; different separator names across source and report; order changes with another menu plugin enabled; menu position varies by capability or admin context; separator rows are appended while sorting.

**Phase to address:** First compatibility/diagnosis phase, before UX work. Verify on WordPress 7.0 and 7.1.x with the same PHP 8.3+ matrix planned for support. PHP 8.2 is diagnostic context only, not a supported target.

**Confidence:** HIGH for the documented core contract and source behavior; MEDIUM for attributing this specific site's warning without the exact installed code and active filters.

### Pitfall 2: Treating the migration as PHP 8.2-to-8.3 only

**Severity:** Critical — legacy syntax can stop the plugin loading on PHP 8.3.

**What goes wrong:** The repository still uses curly-brace string offsets in `gigpress.php`, `lib/parsecsv.lib.php`, and `lib/upgrade.php` (for example `$show['show_time']{7}` and `$value{0}`). PHP deprecated this form in 7.4 and removed it in PHP 8.0, so affected files can fail at parse time before a request reaches the feature being checked. Older PHP/API assumptions elsewhere may likewise predate PHP 8.3 by several major versions.

**Why it happens:** A warning noticed on PHP 8.2 draws attention to one runtime symptom, while the selected baseline is PHP 8.3+. Compatibility must account for syntax and behavior changes from the codebase's actual age, not just the one-minor-version PHP 8.2 → 8.3 diff.

**How to avoid:** Run syntax checks on every shipped PHP file with PHP 8.3; search bundled and conditionally loaded libraries as well as main code. Resolve parse errors and removed APIs before doing visual/UI changes. Use a compatibility matrix for WordPress 7.0, 7.1.x, later supported WordPress, and PHP 8.3+; report PHP 8.2 only as the environment where the original warning was observed. Review the official PHP migration guides for 7.x→8.0 and 8.2→8.3 rather than assuming all newer-runtime failures are new in 8.3.

**Warning signs:** `ParseError` before plugin activation completes; bundled code copied from PHP 5-era libraries; fixes only applied to the main plugin file; support metadata says PHP 8.3 while unscanned source still has legacy syntax.

**Phase to address:** First compatibility phase. Establish a bootable supported matrix before secondary UX changes.

**Confidence:** HIGH. The source occurrences are present in this repository and PHP's official manual states curly-offset syntax is unsupported as of 8.0.

### Pitfall 3: “Cleanup” changes that alter stored data or migration behavior

**Severity:** Critical — can corrupt or orphan show, artist, venue, tour, settings, or related-post data.

**What goes wrong:** A broad rewrite of procedural handlers, table definitions, settings, or the large upgrade library can change IDs, status semantics, soft-delete behavior, date/time interpretation, relationships, or historical migration assumptions. A schema edit that appears harmless can strand user data if column names/types/defaults change or an old database version skips the intended upgrade. Current concerns identify a 3,530-line upgrade library and first-upgrade row-by-row updates, making this area particularly hard to reason about.

**Why it happens:** Persistence and UI logic share globals and direct SQL. The temptation is to replace the structure while also modernizing the screens, but the plugin has no automated test suite and existing installations contain real data from older schemas.

**How to avoid:** Treat the four custom tables, option array, DB version option, foreign-key-like IDs, soft-delete statuses, and related WordPress post links as compatibility contracts. Keep schema changes separate, versioned, repeat-safe, bounded, and tested against copies of representative old databases. Take before/after row counts and key/relationship checks; preserve backups and never drop/rename data in the same release as unrelated UX changes. Use `dbDelta()` only with its documented constraints and explicitly inspect its returned changes.

**Warning signs:** No inventory of supported historical schema versions; schema migration mixed with form refactoring; migration has no repeat-run behavior; mass update has no progress/bounds; import/export has no round-trip check.

**Phase to address:** Compatibility/data-integrity phase before settings or import/export redesign.

**Confidence:** HIGH for WordPress's versioned-upgrade guidance; MEDIUM for identifying legacy upgrade branches that require further source inspection.

### Pitfall 4: Applying HTML escaping rules to XML, iCalendar, or serialized output

**Severity:** High — malformed feeds, injected markup, or incorrect event data.

**What goes wrong:** Escaping all outputs with one HTML helper does not produce valid RSS XML or iCalendar. The existing concern audit reports RSS fields echoed into XML/CDATA without safe XML handling (`output/feed.php`) and iCalendar fields without complete RFC escaping or line folding (`output/ical.php`). Stored values such as `&`, `]]>`, commas, semicolons, backslashes, CR/LF, or Unicode can break consumers, inject lines, or silently truncate data.

**Why it happens:** GigPress prepares HTML-oriented fields in shared helpers and then reuses them across output formats; stored data is often mistakenly treated as already trusted or escaped.

**How to avoid:** Keep raw values until the serialization boundary. Escape for the exact destination: WordPress documents separate HTML, attribute, URL, XML, and textarea helpers. Use XML-aware escaping for RSS fields, safe CDATA splitting or escaped text nodes, and RFC 5545 text escaping plus CRLF line folding for iCalendar. Keep HTML template data separate from feed data and validate feed output with hostile punctuation and newline cases.

**Warning signs:** HTML entity output leaking into feed values; CDATA concatenation; manual format strings; unescaped notes or titles; feed output changes when HTML templates are edited.

**Phase to address:** Compatibility/output correctness phase; include RSS and iCalendar regression fixtures before theme-level presentation changes.

**Confidence:** HIGH for WordPress context-specific escaping guidance; HIGH for repo-specific defects recorded in CONCERNS.md.

### Pitfall 5: Replacing template paths or prepared fields during responsive redesign

**Severity:** High — breaks customer theme overrides even if bundled output looks better.

**What goes wrong:** Moving markup into a new renderer, renaming template files, changing variable shapes, or escaping/formatting values earlier can bypass established overrides under child theme, parent theme, and `wp-content/gigpress-templates`. Custom templates may depend on legacy prepared fields and HTML fragments.

**Why it happens:** The responsive listing request looks like a CSS-only task, but current architecture resolves templates from multiple external locations and exposes prepared arrays to PHP partials.

**How to avoid:** Preserve the existing lookup precedence, filenames, include variables, and documented output fields where practical. Add responsive styles around the current structure first. If markup contracts must evolve, provide compatibility aliases or a documented migration period. Test with a copied override in each supported location and with overrides absent.

**Warning signs:** New hard-coded include path; `$showdata` keys disappear or change escaping type; public list no longer loads a theme-provided file; HTML becomes embedded in a data field with no contract.

**Phase to address:** Responsive public-listings phase, after compatibility baseline. Make override compatibility an explicit acceptance criterion.

**Confidence:** HIGH. The project brief calls overrides established behavior, and architecture/source show multi-location lookup.

## Technical Debt Patterns

| Shortcut | Immediate Benefit | Long-term Cost | When Acceptable |
|----------|-------------------|----------------|-----------------|
| Rewrite all procedural code while fixing compatibility | One consistent architecture | Expands regression surface across mature CRUD, query, feed, and template behavior | Never in this scoped update |
| Keep legacy parsecsv library and patch only the observed import branch | Smaller patch | Unvisited methods still contain PHP 8-incompatible syntax and weak edge-case handling | Only as a temporary compatibility patch after scanning the whole file |
| Store escaped HTML in shared show data | Less work in templates | Reuse in feeds/attributes double-escapes or leaks unsafe formats | Never for new code; preserve old fields only as compatibility adapters |
| Add schema changes to a UI phase | Fewer releases | User-visible work can leave persisted data half-migrated | Only when schema change is required for that feature and has a separate migration plan |

## Integration Gotchas

| Integration | Common Mistake | Correct Approach |
|-------------|----------------|------------------|
| WordPress admin menus | Treat `menu_order` as a place to create menu items or separators | Register items before order snapshot; return existing menu slugs; check ordering with other menu plugins active |
| Theme template overrides | Change markup path and fields without notice | Keep resolver precedence and field contract; verify external overrides |
| WordPress options/settings | Register an array without a strict sanitizer and assume DB values are safe | Allowlist keys/types/ranges on save; escape at each output context |
| RSS/iCalendar | Reuse HTML strings or escape with one generic HTML function | Serialize according to XML and RFC 5545 contexts |
| CSV import/export | Trust uploaded rows or break long text/quotes/newlines on export | Validate headers/types/IDs; use a CSV parser/writer; define duplicate/error/partial-import behavior; test round-trips and spreadsheet formula prefixes |

## Performance Traps

| Trap | Symptoms | Prevention | When It Breaks |
|------|----------|------------|----------------|
| Running whole-table row-by-row migration on request | Slow admin/activation, timeouts, DB locks | Measure affected rows; use bounded/idempotent batches or safe set-based SQL; report progress | Large existing show tables; no one-size numeric threshold because hosting varies |
| Adding unreviewed indexes or changing schema as an optimization | Long ALTER locks, unexpected dbDelta result, shared-site upgrade delay | Inspect query plans and production-like schema; make upgrade explicit and reversible | Large tables or constrained shared DBs |
| Exporting all records into memory | Memory exhaustion and long request | Cap, paginate/stream where library supports it, or clearly constrain export | Large historical archives, depending on row width and PHP memory limit |

## Security Mistakes

| Mistake | Risk | Prevention |
|---------|------|------------|
| Trusting database fields because they were once sanitized | Stored XSS in admin/public HTML or corrupt feeds | Validate input and escape late by output context; review all templates and debug output |
| Treating CSV export as harmless | Spreadsheet formula execution when untrusted text begins with formula characters | Define a safe export policy and test in common spreadsheet apps; preserve import round-trip semantics |
| Reusing admin menu visibility as authorization | Direct AJAX/action requests can mutate data without intended capability checks | Enforce capability and nonce inside every mutation boundary; current audit flags artist reorder AJAX |
| Displaying translations without escaping | Translator-controlled content can inject HTML/script in admin | Use escaped translation functions or escape translated output in the correct context |

## UX Pitfalls

| Pitfall | User Impact | Better Approach |
|---------|-------------|-----------------|
| Redesigning add-show dates/times without preserving legacy defaults | Existing workflows enter different dates or all-day events by mistake | Retain field semantics and test timed, all-day, multi-day, and timezone cases |
| Adding filters/bulk actions with stale selection state | Users apply destructive actions to the wrong shows | Keep selection visible, confirm destructive operations, show counts and result feedback |
| Reorganizing settings without translation/accessibility checks | Labels truncate, keyboard users lose context, translations become stale | Use semantic headings/labels, keyboard-visible focus, escaped and translatable strings, and test long locales |
| Making listings responsive only at the default breakpoint | Theme overrides overflow or become unreadable on narrow viewports | Preserve semantic table/list meaning and test default plus customized templates at narrow widths |
| Improving CSV page layout but not action feedback | Users cannot tell whether import was partial, skipped, or complete | Report imported/skipped/duplicate/error counts and row-level reasons; keep a safe retry story |

## "Looks Done But Isn't" Checklist

- [ ] **Menu warning:** Reproduce with exact installed plugin build; verify separator slug and menu row before/after all `menu_order` callbacks.
- [ ] **PHP compatibility:** Parse-check every shipped PHP file on PHP 8.3+, including bundled/legacy libraries and rarely used actions.
- [ ] **WordPress compatibility:** Check 7.0, 7.1.x, and the currently supported later release with another menu-ordering plugin active.
- [ ] **Data preservation:** Upgrade copies of old schema versions; compare entity counts, IDs, statuses, related post IDs, and settings before/after; rerun migration.
- [ ] **Feed correctness:** Validate RSS as XML and iCalendar as RFC 5545 for `&`, `]]>`, CR/LF, punctuation, non-ASCII, and long lines.
- [ ] **CSV correctness:** Round-trip commas, quotes, multiline notes, UTF-8, empty/optional columns, invalid IDs, duplicates, formula-like values, and partial failures.
- [ ] **Template compatibility:** Render with user theme overrides and confirm field names, URLs, classes, and override lookup still work.
- [ ] **Accessible UX:** Keyboard-only path, labels/errors, focus state, responsive zoom, and translated long strings are all usable.

## Recovery Strategies

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| Menu-order warning | LOW | Revert separator mutation; restore core menu order; then reintroduce ordering only with tested existing slugs |
| PHP parse/runtime regression | MEDIUM | Revert affected release or patch syntax across all shipped source; re-run syntax check on target runtime |
| Corrupt/changed data | HIGH | Stop further writes; restore verified backup; repair from pre/post migration diff; ship idempotent corrective migration |
| Broken theme override | MEDIUM | Restore prior path/variables or add compatibility shim; provide override migration notes |
| Malformed feeds/CSV | MEDIUM | Restore prior output path while serializer fix is developed; do not rewrite stored values to compensate |

## Pitfall-to-Phase Mapping

| Pitfall | Prevention Phase | Verification |
|---------|------------------|--------------|
| Menu separator/order contract and reported warning | Compatibility diagnosis | Menu arrays and warning-free admin across target WP versions, capabilities, and plugin interaction |
| PHP 8.3 parsing and runtime compatibility | Compatibility foundation | Every PHP source file parses; key admin/public flows operate on PHP 8.3+ |
| Historical data and migration safety | Compatibility/data integrity | Old database fixtures retain counts, IDs, relationships, and settings; migration safely repeats |
| HTML/XML/iCalendar output handling | Output/feed hardening | Context-specific rendering checks and parser validation on hostile values |
| Theme override contract and responsive listings | Responsive public-listings UX | Default templates and custom override paths both render correctly on mobile/desktop |
| CSV validation, safe export, and feedback | Import/export UX and integrity | Import/export round-trip, invalid-row reporting, formula safety, UTF-8 and multiline cases |
| Admin settings/form accessibility, i18n, and authorization | Admin UX improvements | Keyboard/labels/error flow, translation escaping, capability+nonce checks on mutations |

## Sources

- WordPress `custom_menu_order` contract: [Developer Reference](https://developer.wordpress.org/reference/hooks/custom_menu_order/) — HIGH.
- WordPress `menu_order` contract and treatment of unmentioned slugs: [Developer Reference](https://developer.wordpress.org/reference/hooks/menu_order/) — HIGH.
- WordPress 7.1.2 core menu construction/sort comparator: [wordpress-develop 7.1.2 `src/wp-admin/includes/menu.php`](https://github.com/WordPress/wordpress-develop/blob/7.1.2/src/wp-admin/includes/menu.php) — HIGH. The checked source snapshots defaults before applying `menu_order`, then indexes the default map for a fallback; exact warning attribution remains MEDIUM pending reproduction.
- PHP removal of curly-brace offset access: [PHP 8.0 incompatible changes](https://www.php.net/manual/en/migration80.incompatible.php), [PHP string offset documentation](https://www.php.net/manual/en/language.types.string.php) — HIGH.
- PHP 8.3 migration changes and production-testing guidance: [PHP 8.3 migration guide](https://www.php.net/manual/en/migration83.php) — HIGH.
- WordPress validation/sanitization guidance: [Common APIs Security Handbook](https://developer.wordpress.org/apis/security/) — HIGH.
- WordPress context-specific output escaping: [Escaping Data](https://developer.wordpress.org/apis/security/escaping/) — HIGH.
- WordPress schema creation and versioned upgrade guidance: [Creating Tables with Plugins](https://developer.wordpress.org/plugins/creating-tables-with-plugins/) and [`dbDelta()` reference](https://developer.wordpress.org/reference/functions/dbDelta/) — HIGH.
- WordPress translation and escaping guidance: [Internationalizing Your Plugin](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/) — HIGH.
- Repository evidence: `.planning/codebase/CONCERNS.md`, `.planning/codebase/ARCHITECTURE.md`, `gigpress.php`, `admin/db.php`, `output/feed.php`, `output/ical.php`, `admin/import-export.php` — HIGH for observed codebase findings; no external confidence tier applies.

---
*Pitfalls research for: GigPress WordPress/PHP compatibility update*
*Researched: 2026-10-03*
