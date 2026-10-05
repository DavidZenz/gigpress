---
phase: 03-administration-workflows
fixed_at: 2026-10-05T06:48:57Z
review_path: .planning/phases/03-administration-workflows/03-REVIEW.md
iteration: 1
findings_in_scope: 2
fixed: 2
skipped: 0
status: all_fixed
---

# Phase 03 Entry Review Fix Report

Scoped to CR-06 and WR-01. Parent workflow owns the shared review disposition and final evidence regeneration.

## Fixed Issues

### CR-06: Required new-entry fields can become empty after validation

**Files modified:** `admin/handlers.php`, `tests/compat/administration-entry.php`
**RED commit:** `44e612c`
**GREEN commit:** `346d3d0`
**Status:** fixed: requires human verification
**Applied fix:** Normalize artist name, venue name/city and tour name into a separate array before validation. The save and preparation paths validate that array and reuse its values for insertion; original raw values remain available for correction. Rejection precedes every entity, post and show write.

Real WordPress RED: 48 failures among 283 checks, covering tag-only and percent-encoded values for all four fields in add/update modes. GREEN: all 283 checks pass, including unchanged snapshots, raw/control recovery, whitespace rejection and valid normalized name/city readbacks. Existing date, optional time, identity and completed-creation retry checks also remain passing.

### WR-01: Recovery escaping checks do not bind values to their fields

**File modified:** `tests/compat/administration-entry.php`
**RED commit:** `3f4db2f`
**GREEN commit:** `71d3578`
**Status:** fixed
**Applied fix:** Give each of the fifteen recovery inputs a distinct hostile value. Extract exactly one input with the requested name, compare that input's escaped value attribute, and decode it back to the received string. Blank, corrupted and unescaped controls cannot pass because another control contains the expected value.

Real WordPress RED: all 45 deliberately corrupted-control checks fail under the original whole-document predicate. GREEN: all 175 recovery checks pass, including those 45 negative checks. CR-06 subsequently extends this existing case to 283 checks; the exact eight-case registry is unchanged.

## Verification

Verification ran from the main checkout `/Users/davidzenz/gigpress`, using disposable real WordPress fixtures through the existing runner. No worktree was created under the explicit scoped task instruction. Each invocation was bounded; no matrix was run.

Runtime: WordPress 7.1.2 / PHP 8.3.35, image `sha256:4abf7a450ee477dde967584f8174d7e03221d224c4971a0c38d84e7254426e64`.

| Focused case | Checks | Result | Warnings / fatals / plugin errors | Cell time |
|---|---:|---|---|---:|
| entry-create | 9 | PASS | 0 / 0 / 0 | 11s |
| entry-recovery | 283 | PASS | 0 / 0 / 0 | 12s |
| entry-controls | 73 | PASS | 0 / 0 / 0 | 13s |

Commands: `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario administration-workflows --case <case>` for the three cases above. Scoped PHP 8.3 lint via `rtk proxy bash tests/compat/run.sh lint --php-branches 8.3 --files admin/handlers.php,tests/compat/administration-entry.php` passes both files. Modified sections were reread and scoped `git diff --check` passed. Every fixture runner exited successfully after owned teardown for its GREEN run.

The parent workflow must regenerate the final runtime/source evidence after all concurrent fixes. This focused report does not replace that evidence or human acceptance.

---

Fixer: gsd-code-fixer, entry scope. Report left uncommitted for the parent workflow.
