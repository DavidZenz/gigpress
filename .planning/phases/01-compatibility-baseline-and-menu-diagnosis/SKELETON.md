# Walking Skeleton — GigPress

**Phase:** 1  
**Generated:** 2026-10-03

## Capability Proven End-to-End

> A site owner can install and activate the existing GigPress plugin in a real disposable WordPress/PHP environment, load its administration menu, use its stored show data, render public/feed output, and exchange CSV data through the unchanged brownfield stack.

This skeleton records the architecture already present in GigPress. Phase 1 validates and repairs that stack; it does not scaffold a new plugin or replace its established application layers.

## Architectural Decisions

| Decision | Choice | Rationale |
|---|---|---|
| Framework | Existing procedural WordPress plugin and native hook APIs | Preserves the plugin's stable callbacks, shortcodes, feeds, menus, and extension contracts; no framework is needed for compatibility work. |
| Data layer | Existing WordPress `$wpdb` access, `gigpress_settings`, and four prefix-aware GigPress tables | Existing installations depend on their IDs, relationships, statuses, options, and schema behavior; Phase 1 makes no schema change. |
| Auth | Existing WordPress roles/capabilities and nonce APIs | WordPress owns administrator identity and plugin-management visibility; compatibility feedback is restricted through the existing platform boundary. |
| Below-floor runtime | Keep an already-active plugin active but inert below PHP 8.3 | The main bootstrap registers only a capability-scoped compatibility notice below the floor; normal modules, hooks, and functions return automatically on PHP 8.3+. |
| Deployment target | Existing WordPress plugin directory; local proof through an isolated OrbStack-backed Docker-compatible Compose WordPress/PHP matrix | The shipped artifact remains the plugin checkout, while disposable containers prove compatibility without touching a production site or requiring host PHP. |
| Directory layout | `gigpress.php` composition root with existing `admin/`, `output/`, `lib/`, `templates/`, asset, and language directories | Later slices can change one workflow at a time without moving or renaming public/template contracts. |
| Validation surface | `tests/compat/` disposable runner plus per-phase evidence under `.planning/phases/01-compatibility-baseline-and-menu-diagnosis/` | The repository has no existing test runner; this gives the unchanged stack a reproducible activation, workflow, and warning gate without adding runtime dependencies. |

## Stack Touched in Phase 1

- [x] Existing plugin scaffold — bootstrap, direct PHP assets, and hook registration are already present.
- [x] Routing — real WordPress admin-menu, shortcode, feed, AJAX, and admin-post entry points already exist.
- [x] Database — activation and existing workflows perform real reads/writes against four custom tables and WordPress options.
- [x] UI — the existing WordPress administration menu/pages and public templates are real user-visible surfaces.
- [ ] Validation/deployment proof — Plan 01-01 adds the documented local full-stack command and exact WordPress/PHP matrix evidence.

## Out of Scope (Deferred to Later Slices)

- Historical database and upgrade preservation beyond the no-schema-change compatibility check — Phase 2.
- Add/edit show guidance, list filters/bulk feedback, and settings organization — Phase 3.
- Responsive public listing work and complete template-override verification — Phase 4.
- CSV screen clarity, outcome feedback, and mutation hardening — Phase 5.
- Composer, autoloading, a class/framework rewrite, a new schema, and new event-management product features — outside the approved milestone.

## Subsequent Slice Plan

Each later phase adds one vertical slice on top of this skeleton without replacing these architectural decisions:

- Phase 2: Preserve existing records, relationships, settings, and upgrade/CRUD behavior.
- Phase 3: Improve administration entry, listing, bulk-action, and settings workflows.
- Phase 4: Improve bundled public listings while preserving shortcodes, feeds, and theme overrides.
- Phase 5: Improve CSV task clarity and feedback while preserving its established exchange contract.
