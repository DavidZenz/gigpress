---
phase: 03-administration-workflows
status: passed
observed: 2026-10-05
source_revision: edf959e75c7f74f692a6f57cb33749648b3d0f06
---

# Cross-phase regression evidence

Prior-phase suites were discovered from 01-VERIFICATION.md and 02-VERIFICATION.md: runner/probe/Compose isolation, menu conflict contract, real PHP-floor recovery, seven historical migration fixtures, and the eleven-case upgrade-preservation registry. No workflow.test_command or conventional Makefile/Justfile/package runner exists; concrete inherited compatibility commands were run instead of counting the generic fallback `true` as a test.

Bounded one-shot command (600 seconds, exit 0): shell syntax, runner self-test, menu-contract with preferred/index-zero/missing/duplicate/empty/single/order-conflict/no-global-mutation, and real runtime-floor transitions on both WordPress lines.

| Suite | Outcome |
|---|---|
| bash -n tests/compat/run.sh | PASS |
| run.sh self-test | PASS: ordering, patch pinning, PHP support parsing, diagnostic exclusion, recovery bootstrap and Compose cleanup |
| menu-contract WP 7.1.2 / PHP 8.3 | PASS: all eight requested cases, failures empty |
| runtime-floor WP 7.0.6 | PASS: PHP 8.3.35 → 8.2.34 → 8.3.35; active plugin/data preserved and supported surfaces recovered |
| runtime-floor WP 7.1.2 | PASS: same diagnostic transition and preservation/recovery outcomes |

PHP 8.2 is diagnostic-only. Its inert behavior is not supported-workflow evidence. The new 03-ADMIN-MATRIX.md records inherited eleven-case preservation and fresh full-workflows regressions PASS on all six pinned supported cells, zero errors. The measured build completed in 541 seconds; validate and 21-check corruption self-test passed. A redundant standalone exact preservation retry stalled and exited 137; the already measured matching pinned cell (45 seconds before teardown) supplies its regression evidence. Migrated public/feed/template and CSV integration remain assigned to Phases 04/05.

No watch mode, package installs, real-site writes or unbounded gate commands were used. Existing earlier evidence reports are historical snapshots; changed source is certified by the fresh Phase 03 matrix rather than relabeling old source-bound reports.

## Post-review rerun (2026-10-05)

At committed production source f3f1781 and documentation-only descendants, the inherited one-shot regression ran under a 600-second bound and exited 0. Syntax, runner self-test, all eight menu conflict cases, the foreign browser-session contract, the missing-report contract and all five cleanup contract cases passed. Both exact WordPress 7.0.6/7.1.2 runtime-floor transitions again passed PHP 8.3.35 → diagnostic 8.2.34 → 8.3.35 with active-plugin/data preservation and normal supported-surface recovery. The current rebuilt 03-ADMIN-MATRIX.md supplies the full preservation/fresh-workflow supported evidence; the original pre-review matrix described above is historical.
