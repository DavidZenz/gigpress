# Phase 05 CSV browser fixture

This fixture owns an isolated WordPress/PHP container, database, loopback port, session identity, generated administrator credentials, deterministic data, and cleanup. Its HTTP smoke runs authenticated multipart import checks and verifies the Import/Export and tour-conversion routes. It does not substitute for visual browser evidence, disabled-JavaScript review, export downloads, or a spreadsheet-client observation.

## Lifecycle

Run commands from the repository root. The supported fixture cell is WordPress 7.1.2 / PHP 8.3:

```sh
rtk proxy bash tests/compat/run.sh csv-fixture --action smoke --wp 7.1.2 --php 8.3 --case all
rtk proxy bash tests/compat/run.sh csv-fixture --action start --wp 7.1.2 --php 8.3 --case all
rtk proxy bash tests/compat/run.sh csv-fixture --action status --session <private-session-path>
rtk proxy bash tests/compat/run.sh csv-fixture --action check --session <private-session-path>
rtk proxy bash tests/compat/run.sh csv-fixture --action stop --session <private-session-path>
```

`smoke` starts the fixture, checks authenticated routes/actions and multipart behavior, then stops it even on failure. `start` retains the owned fixture and returns a loopback URL and private session path. `status` verifies ownership and reports WordPress/PHP/image identity. `check` repeats the HTTP assertions while retaining the fixture. `stop` removes only the owned Compose project and volumes; cleanup failure retains private recovery metadata.

The session JSON is stored with mode `0600` in a mode `0700` directory. It contains generated administrator and database credentials; do not publish it. The login is `browser-admin`; obtain its generated password locally with `rtk jq -r .password <private-session-path>`. The browser fixture is loopback-only.

## Browser routes and deterministic inputs

Open the `url` returned by `start`, then visit:

- `/wp-admin/admin.php?page=gigpress-import-export` for the stacked Import/Export screen, multipart upload, result details and retry affordance.
- `/wp-admin/admin.php?page=gigpress-artists` for tour conversion selection and preview.

Use the authenticated administrator credentials from the private session. Disable JavaScript in the browser itself before checking the stacked layout and accessible disclosures. Browser/version, operating system and fixture session belong in `05-BROWSER.md`.

Import inputs include `tests/compat/fixtures/shows.csv`, `tests/compat/fixtures/csv/import-records.php`, `tests/compat/fixtures/csv/empty.csv`, and `tests/compat/fixtures/csv/header-only.csv`. The automated smoke uses actual `multipart/form-data`; its imported row has the notes `Quoted, durable notes` and ticket phone `+1-555-0101`.

Export expectations are the established 23 headers in the existing order, selected all-dates/upcoming/past scope and row count, unchanged literal values, empty-selection no-download behavior, retained current choices, and explicit Reset restoring All artists / All tours / All dates. `tests/compat/fixtures/csv/export-literals.php` supplies formula-like Unicode notes, leading-plus phones, midnight, multi-day and legacy status `0` values.

Conversion fixtures in `tests/compat/fixtures/csv/conversion.php` cover exact-one `Existing Exact Artist`, new target `New Target Artist`, ambiguous `Ambiguous Artist`, independent `Independent Later Tour`, and active/trashed show relationships. Preview, Cancel, Confirm and retry observations must record before/after identifiers and outcomes.

The human reviewer downloads selected nonempty exports and inspects the file; the fixture does not assert a spreadsheet application. If one is exercised, record its actual name/version and only the observed text-import, save and re-import workflow. Never generalize that observation to direct-open safety.

Cleanup after observations:

```sh
rtk proxy bash tests/compat/run.sh csv-fixture --action stop --session <private-session-path>
```
