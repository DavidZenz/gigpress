# Compatibility Matrix Evidence

**Checked:** 2026-10-13T00:12:00Z

Source revision: `2674b35230b6f4cf94a857d5788cb13361416a9e`

## Refreshed upstream sources

- WordPress release archive: <https://wordpress.org/download/releases/> — latest
  7.0 patch: **7.0.7**; latest 7.1 patch: **7.1.3**.
- PHP supported versions: <https://www.php.net/supported-versions.php> — target
  branches at or above the selected 8.3 floor: **8.3**, **8.4**, and **8.5**.
- Official WordPress image tags: <https://hub.docker.com/_/wordpress>.

The runner obtained the exact WordPress core version for each line immediately
before the run. It used the official `wordpress:phpX.Y-apache` image as the
runtime base, replacing only WordPress core with the official release archive
when the requested core version differed from the base image.

## Supported full-workflow matrix

Each row ran with `E_ALL` via the OrbStack Docker-compatible runner. `PASS`
means: plugin activation and warning-free menu; representative admin create,
edit, and read; public shortcode output; RSS; iCalendar; CSV export/import;
and duplicate-row preservation all passed. PHP 8.2 is absent from this table.

| WordPress | PHP | Image | Image ID | Admin | Public | RSS | iCalendar | CSV + duplicate | Result | Result SHA-256 |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 7.0.7 | 8.3.35 | `wordpress:php8.3-apache` | `sha256:f32ffa85064d9c951b7b69c929fa2a9f682aa4443e0c0a56ac2000499cb64383` | PASS | PASS | PASS | PASS | PASS | PASS | `ab42d023e9e36647895a7f900caa73c47cd98310f2d17401d995ebf982c1b2d9` |
| 7.0.7 | 8.4.26 | `wordpress:php8.4-apache` | `sha256:309307430abf80009378a63a79df73554a6449b700fac59a991b4959e6dda8ca` | PASS | PASS | PASS | PASS | PASS | PASS | `0b4875c023c9d8e8735a77d01b2dd5ecf97189f5c5ed27a6d16219d286f19eb7` |
| 7.0.7 | 8.5.11 | `wordpress:php8.5-apache` | `sha256:c12bed9c5af9d76261221c2af3a6216a074dd80b4fc2459588b3cbf2dd8e1798` | PASS | PASS | PASS | PASS | PASS | PASS | `dac81b7cfc5fe51677a9bb5d2f6b7187e75e2cd2fa42a19869de950cd51ee4bd` |
| 7.1.3 | 8.3.35 | `wordpress:php8.3-apache` | `sha256:f32ffa85064d9c951b7b69c929fa2a9f682aa4443e0c0a56ac2000499cb64383` | PASS | PASS | PASS | PASS | PASS | PASS | `bbead667305ba640dc8f0ee948f47e33a5aef7be8d9d79725858430b7e402031` |
| 7.1.3 | 8.4.26 | `wordpress:php8.4-apache` | `sha256:309307430abf80009378a63a79df73554a6449b700fac59a991b4959e6dda8ca` | PASS | PASS | PASS | PASS | PASS | PASS | `545a5b6a10ab1e6582053bdd6eb83d58aba07d0da96fdfd8ab1b937278cc681a` |
| 7.1.3 | 8.5.11 | `wordpress:php8.5-apache` | `sha256:c12bed9c5af9d76261221c2af3a6216a074dd80b4fc2459588b3cbf2dd8e1798` | PASS | PASS | PASS | PASS | PASS | PASS | `8efbacb389d3e00f82e6ac6a4ab72dc3fe3df13e39e9abfd74612649972b6d5c` |

Fixture SHA-256: `8ff7322ca6afdd134c96a9cc0c6fe6a87ed00ac405fc3f300606b8fb6f082975`
for `tests/compat/fixtures/shows.csv`.

The result artifacts are the six `tests/compat/.results/*-full-workflows.json`
records named by their WordPress and PHP versions. Every result records
`plugin_active: true`, no fatal, zero `plugin_errors`, zero `menu_warnings`,
and all six aggregate workflow assertions as true.

Verification command:

```text
rtk bash tests/compat/run.sh matrix --scenario full-workflows --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL
```

## D-03 runtime-floor lifecycle

The following lifecycle passed on both WordPress 7.0.7 and 7.1.3 using the
same isolated database for each 8.3 → 8.2 → 8.3 transition:

```text
rtk bash tests/compat/run.sh runtime-floor --plugin gigpress/gigpress.php --wp-lines 7.0,7.1 --supported-php 8.3 --diagnostic-php 8.2
```

The already-active plugin retained its active state and stored data under the
diagnostic PHP 8.2 transition, exposed only its repeated authorized manager
notice, left normal modules/hooks/functions unavailable, did not expose the
notice to unauthorized or public requests, and recovered normal behavior with
the notice removed on PHP 8.3. The low-floor checks booted the actual WordPress
7.0.7 and 7.1.3 sites with GigPress already active; they did not use the plugin
API facade for the real-plugin path.

## D-02 diagnostic-only evidence

PHP 8.2 remains diagnostic-only and contributes to no supported row. The
controlled exact-key trace on WordPress 7.1.3/PHP 8.2.34 again emitted the
exact `Undefined array key "separator-gigpress"` core warning and attributed
the synthetic row to `gigpress_menu_conflict_late_add` at priority 20. The
fixture diagnostic does not identify the unavailable live-site callback.
