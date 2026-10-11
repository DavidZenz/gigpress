# Deferred Items

- **Found during:** Plan 05-04 verification
- **Issue:** `upgrade-preservation --case all` fails only `show-lifecycle`; the same isolated failure occurs on the pre-05-04 baseline commit. The case reports that post-upgrade show lifecycle semantics are not preserved.
- **Scope:** Existing compatibility failure, not caused by the tour conversion changes.
- **Evidence:** WordPress 7.1.2 / PHP 8.3.35, `show-lifecycle` fails on both current code and the archived `HEAD^` baseline.
