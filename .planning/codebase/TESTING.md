---
last_mapped_commit: 93f21160ad34b07f7ef2909c068ba51b4dd23dcc
last_mapped_at: 2026-10-03
---
# Testing Patterns

**Analysis Date:** 2026-10-03

## Test Framework

**Runner:**
- No test runner, PHPUnit configuration, or WordPress test bootstrap is detected.
- Config: Not detected.

**Assertion Library:**
- Not detected.

**Run Commands:**

```bash

# No project test commands are defined.

```

## Test File Organization

**Location:**
- No `tests/` directory or test files are present. Runtime code is organized under `admin/`, `output/`, `lib/`, and `templates/`.

**Naming:**
- Test naming convention is not established.

**Structure:**

```
No test tree detected
```

## Test Structure

**Suite Organization:**

```php
// No in-repository test suites detected.
```

**Patterns:**
- There are no recorded setup, teardown, fixture, or assertion patterns.
- The closest executable checks are runtime validation and compatibility assertions in `lib/upgrade.php`.

## Mocking

**Framework:** Not detected.

**Patterns:**

```php
// No mocks or WordPress test doubles detected.
```

**What to Mock:**
- If tests are added, isolate WordPress globals and `$wpdb` calls used by `gigpress.php`, `admin/db.php`, and `admin/handlers.php`.

**What NOT to Mock:**
- Keep pure transformations such as `gigpress_get_O_offset()` and `gigpress_prepare()` focused on representative input/output assertions.

## Fixtures and Factories

**Test Data:**

```php
// No fixtures or factories detected.
```

**Location:**
- Not applicable. Plugin defaults and schema seed values are defined in `admin/db.php`.

## Coverage

**Requirements:** None enforced.

**View Coverage:**

```bash

# No coverage command is configured.

```

## Test Types

**Unit Tests:**
- Not present. Candidate pure or near-pure units include date/offset formatting in `gigpress.php`, compatibility wrappers in `lib/upgrade.php`, CSV parsing in `lib/parsecsv.lib.php`, and show preparation in `gigpress.php`.

**Integration Tests:**
- Not present. High-value integration coverage would exercise WordPress hooks, `$wpdb` schema operations in `admin/db.php`, admin handlers in `admin/handlers.php`, and shortcode/feed output in `output/`.

**E2E Tests:**
- Not used or configured.

## Common Patterns

**Async Testing:**

```php
// Not applicable. Browser behavior in `scripts/gigpress.js` and `scripts/gigpress-admin.js` has no automated test harness.
```

**Error Testing:**

```php
// Not established. Production paths use `$errors`, WordPress return checks, and `trigger_error()`.
```

---

*Testing analysis: 2026-10-03*
