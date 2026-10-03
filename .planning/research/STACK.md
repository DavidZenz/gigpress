# Stack Research

**Domain:** Compatibility update for a legacy procedural WordPress plugin
**Researched:** 2026-10-03
**Confidence:** MEDIUM

## Recommended Stack

The product has selected WordPress 7.0+ and PHP 8.3+ as its minimums. Keep those as the plugin support contract, then validate against the current stable patch release in each active WordPress branch and each currently supported PHP branch. On the research date, WordPress releases are 7.0.6 and 7.1.2; PHP branches 8.3, 8.4, and 8.5 remain supported upstream. WordPress core compatibility does not establish that GigPress itself is compatible, so the plugin needs its own matrix and behavioral checks.

### Core Technologies

| Technology | Version | Purpose | Why Recommended |
|-----------|---------|---------|-----------------|
| WordPress | Minimum 7.0; validate latest stable 7.0.x and 7.1.x | Host runtime and plugin API | The user selected 7.0 as the minimum. As of 2026-10-03, the latest releases are 7.0.6 and 7.1.2. Keep tracking current supported releases and do not invent a maximum version. |
| PHP | Minimum 8.3; CI on 8.3, 8.4, and 8.5 | Plugin runtime | The user selected 8.3+. PHP 8.3 is security-fixes-only through 2027-12-31; 8.4 and 8.5 are in active support on the research date. The 8.3 floor is the product contract; deploy on an actively supported PHP branch where available. |
| WordPress Plugin Check | Current stable action/tool release | Static and runtime plugin checks | It is the WordPress.org-maintained plugin checker and catches metadata, coding, security, and best-practice issues. It complements, but does not replace, behavioral tests. |
| PHPUnit with WordPress test environment | Current supported versions compatible with PHP 8.3+ | Plugin integration tests | A disposable WordPress database lets tests exercise actual hooks, admin initialization, CRUD, feeds, and migration behavior. Introduce it incrementally because this repository has no automated suite today. |

### Supporting Libraries

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| WordPress Coding Standards (WPCS) / PHP_CodeSniffer | Current compatible release, pinned in development tooling | PHP lint/style and WordPress-specific static checks | Add to CI with the target PHP version set to 8.3; scope rules to changed code first if a whole-repository run is noisy. |
| PHPCompatibilityWP | Current compatible release, pinned in development tooling | Detect syntax and runtime features incompatible with the declared PHP floor | Use as an additional scan while removing obsolete PHP-era compatibility branches. It cannot detect all behavioral incompatibilities, so retain runtime execution checks. |
| `@wordpress/env` or an equivalent disposable WordPress environment | Current compatible release | Repeatable local/CI WordPress plus database setup | Use only when adding integration tests; no build or package manager is currently part of GigPress production. |

### Development Tools

| Tool | Purpose | Notes |
|------|---------|-------|
| `php -l` on all tracked PHP files | Catch parser failures, including syntax removed before PHP 8.3 | Run using PHP 8.3 or newer in CI; PHP 8.0 removed curly-brace array/string-offset access, which is present in the bundled legacy CSV parser. |
| WordPress Plugin Check Action | Run the official checker on the packaged plugin in CI | Use the action's supported stable release and inspect findings rather than suppressing all legacy warnings. Runtime checks need a WordPress environment. |
| PHPUnit + WordPress integration bootstrap | Cover key behavior without replacing the existing plugin architecture | Start with focused tests and smoke cases around activation, menu rendering, existing data, feeds, import/export, and upgrade paths. |
| Manual disposable-site smoke pass | Verify UI behavior and the reported menu warning | Keep it as a release check until automated browser/admin coverage exists; never use a production database for first-run upgrade validation. |

## Installation

No production dependency needs to be added to this PHP plugin to declare or validate the support floor. Keep test and lint tooling development-only. For the first CI pass, configure PHP 8.3/8.4/8.5 and WordPress 7.0.6/7.1.2 jobs, run syntax and static checks, and run Plugin Check on the release artifact. Add a disposable database-backed WordPress test environment when the first focused integration tests are introduced.

```bash
# No runtime package installation is required.
# Development CI should lint with PHP 8.3+ and run Plugin Check.
# Integration-test tooling belongs in development dependencies only.
```

## Compatibility Metadata and Validation Contract

- In the main plugin PHP file, set `Requires at least: 7.0` and `Requires PHP: 8.3`. WordPress reads these headers to validate plugin requirements. Keep the `readme.txt` requirement fields aligned if they are present, but treat the main-file headers as canonical.
- Set `Tested up to: 7.1` in `readme.txt` only after testing against WordPress 7.1.2 or a newer stable 7.1 patch. WordPress.org expects a major version here and ignores minor releases; do not put `7.1.2` or a future/unverified version in the field. Update it as stable releases are actually tested.
- CI matrix: WordPress 7.0.6 and 7.1.2 × PHP 8.3, 8.4, and 8.5. Refresh the patch versions from the official release archive and supported-version table rather than maintaining an invented upper bound. When a new stable WordPress branch or PHP branch becomes supported, add it to the validation matrix.
- Add a non-blocking smoke job for WordPress beta/RC or development builds when feasible; promote it to blocking only when the project can keep it reliable.
- Until automated integration coverage exists, require a disposable-site release checklist: activate and load the admin menu without PHP warnings; create/edit/delete a show and its related artist, venue, and tour; render a shortcode/list; request RSS and iCalendar feeds; round-trip CSV import/export; and run the upgrade path against a copy of representative existing GigPress data.
- Run logs with `E_ALL` enabled in the supported matrix. Treat warnings/notices as actionable compatibility signals, but distinguish plugin defects from core or environment messages. Investigate the reported WordPress 7.1.2 / PHP 8.2 `separator-gigpress` warning separately; PHP 8.2 is repro context only and is not a supported target or a required CI job.

## PHP 8.3 and Legacy-Code Audit

PHP's migration guide identifies backward-incompatible changes that need execution testing, not just syntax checks. In GigPress's bundled `lib/parsecsv.lib.php`, the legacy `$data{$x}` offset syntax is removed in PHP 8.0, so lint and update it before claiming PHP 8.3 support. Audit loose request/data handling and old fallback code under PHP 8.3+ as well.

PHP 8.3 deprecates argument-less `get_class()` and `get_parent_class()` and some string increment/decrement cases. The existing `get_parent_class($alias)` call in `lib/upgrade.php` passes an argument and is not itself the deprecated form. PHP 8.2 dynamic-property deprecations are earlier-version diagnostic context; do not mislabel them as new PHP 8.3 changes or infer an 8.2 support commitment from the warning report.

## Alternatives Considered

| Recommended | Alternative | When to Use Alternative |
|-------------|-------------|-------------------------|
| Keep the product-selected PHP 8.3 minimum and validate PHP 8.3–8.5 | Raise the declared floor to PHP 8.4 | Only if the project changes its explicit compatibility decision; PHP 8.4+ is a stronger upstream support posture, but excluding 8.3 would violate the current requirement. |
| Test the two current WordPress release lines (7.0 and 7.1) at their latest patches | Test only WordPress 7.1 | Only for a future policy that deliberately drops the 7.0 minimum; current compatibility promises require coverage at the minimum line. |
| Add focused PHPUnit/WordPress integration coverage after static gates | Manual-only testing | A short bootstrap phase may use a manual checklist to get the first release out, but manual-only validation is too fragile for future data, feed, and admin regressions. |

## What NOT to Use

| Avoid | Why | Use Instead |
|-------|-----|-------------|
| PHP 8.2 as a supported/required CI target | The user explicitly excluded it; the reported 8.2 setup is evidence for reproducing one warning, not a change to the support promise. | Declare PHP 8.3 and test the current supported 8.3+ branches. |
| An invented maximum such as `Tested up to: 7.1.2`, “up to WordPress 7.1,” or a PHP upper cap | It is either the wrong metadata granularity or falsely freezes an open-ended, current-release support policy. | Use a verified major-only WordPress `Tested up to` value and keep testing current supported releases. |
| PHP 5 compatibility as a design constraint or broad rewrites to modern OOP/framework architecture | The project targets a modern PHP floor and is a brownfield procedural plugin; an architecture rewrite adds migration and regression risk without being required for compatibility. | Preserve entry points and data behavior; remove obsolete syntax and shims incrementally when proven unnecessary. |
| Treating Plugin Check or lint as proof of compatibility | Static tools cannot prove admin workflows, database upgrades, or feed behavior. | Pair static checks with a disposable WordPress/PHP matrix and explicit behavior smoke tests. |

## Stack Patterns by Variant

**If the repository keeps its current no-build PHP structure:**
- Keep WordPress and PHP as host-provided runtimes and add development tools only to CI/test configuration.
- Because the plugin has no dependency manager now, do not add a runtime Composer or npm dependency just to implement metadata or compatibility declarations.

**If integration tests are added:**
- Use PHPUnit against a disposable WordPress installation and database, with the same six WP/PHP compatibility cells used by CI.
- Start with activation, admin menu loading, one CRUD path, feeds, CSV round-trip, and schema upgrade regression tests; then expand coverage around risky legacy behavior.

## Version Compatibility

| Package A | Compatible With | Notes |
|-----------|-----------------|-------|
| WordPress 7.0 / 7.1 | PHP 8.3, 8.4, 8.5 | Official WordPress Core/Hosting tables report full compatibility for these versions as of 2026-10-03. This describes WordPress core, not GigPress. |
| GigPress declared minimum | WordPress 7.0+, PHP 8.3+ | Product-selected support baseline. No maximum is declared; track supported upstream releases. |
| PHP 8.3 | Security support through 2027-12-31 | Meets the selected minimum but is no longer in active bug-fix support on the research date. Prefer PHP 8.4/8.5 for new deployments while retaining 8.3 compatibility. |
| PHP 8.4 | Active support through 2026-12-31; security support through 2028-12-31 | Current actively maintained deployment target on the research date. |
| PHP 8.5 | Active support through 2027-12-31; security support through 2029-12-31 | Current actively maintained deployment target on the research date. |

## Sources

- [WordPress plugin header requirements](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/) — meanings of `Requires at least` and `Requires PHP`; updated 2026-03-11.
- [WordPress plugin readme documentation](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/) — `Tested up to` semantics and requirement parsing since WordPress 5.8.
- [WordPress release archive](https://wordpress.org/download/releases/) — current stable releases 7.1.2 and 7.0.6 on 2026-10-03.
- [WordPress PHP compatibility table](https://make.wordpress.org/core/handbook/references/php-compatibility-and-wordpress-versions/) and [Hosting server environment](https://make.wordpress.org/hosting/handbook/server-environment/) — supported WordPress/PHP combinations; hosting page updated 2026-08-16.
- [PHP supported versions](https://www.php.net/supported-versions.php) — support windows for PHP 8.3–8.5, checked 2026-10-03.
- [PHP 8.3 migration guide](https://www.php.net/manual/en/migration83.php), [8.3 incompatible changes](https://www.php.net/manual/en/migration83.incompatible.php), and [8.3 deprecated features](https://www.php.net/manual/en/migration83.deprecated.php) — runtime changes.
- [PHP 8.0 incompatible changes](https://www.php.net/manual/en/migration80.incompatible.php) — removal of curly-brace offset access affecting the bundled CSV parser.
- [PHP 8.2 deprecated features](https://www.php.net/manual/en/migration82.deprecated.php) — dynamic-property deprecation context only.
- [WordPress Plugin Check](https://github.com/WordPress/plugin-check) and [Plugin Check Action](https://github.com/WordPress/plugin-check-action) — official plugin static/runtime checks and CI integration.
- [WordPress automated testing handbook](https://make.wordpress.org/core/handbook/testing/automated-testing/) — PHPUnit as the PHP test suite and automation approach.

---
*Stack research for: GigPress WordPress plugin compatibility update*
*Researched: 2026-10-03*
