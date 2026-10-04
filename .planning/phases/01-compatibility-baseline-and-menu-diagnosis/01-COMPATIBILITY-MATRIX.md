# Compatibility Matrix Evidence

**Checked:** 2026-10-04T08:44:16Z

Source revision: `259c0ca92cc99e8b3bfc2f6da16719ea3805f90e`

## Refreshed upstream sources

- WordPress release archive: <https://wordpress.org/download/releases/> — latest
  7.0 patch: **7.0.6**; latest 7.1 patch: **7.1.2**.
- PHP supported versions: <https://www.php.net/supported-versions.php> — target
  branches at or above the selected 8.3 floor: **8.3**, **8.4**, and **8.5**.
- Official WordPress image tags: <https://hub.docker.com/_/wordpress>.

The exact 7.0.6 core came from the official WordPress release archive. Docker
Hub no longer provides a `7.0.6-php8.x-apache` tag, so the disposable runner
uses the listed official 7.1.2 PHP/Apache image as its runtime base and replaces
only WordPress core with the checked 7.0.6 archive before boot. The `image`
and `image ID` values below identify that actual official container base.

## Supported full-workflow matrix

Each row ran with `E_ALL` via the OrbStack Docker-compatible runner. `PASS`
means: plugin activation and warning-free menu; representative admin create,
edit, and read; public shortcode output; RSS; iCalendar; CSV export/import;
and duplicate-row preservation all passed. PHP 8.2 is absent from this table.

| WordPress | PHP | Image | Image ID | Admin | Public | RSS | iCalendar | CSV + duplicate | Result | Result SHA-256 |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 7.0.6 | 8.3.35 | `wordpress:7.1.2-php8.3-apache` | `sha256:4abf7a450ee477dde967584f8174d7e03221d224c4971a0c38d84e7254426e64` | PASS | PASS | PASS | PASS | PASS | PASS | `828d749693af51b6e56b9b1e833fe20bd4983e574462a47a0147a6c8b0c2543c` |
| 7.0.6 | 8.4.26 | `wordpress:7.1.2-php8.4-apache` | `sha256:85ee71a393b0f3f7a45b2e293978f1c9ab94fb421161080e54f911eeb2501bdc` | PASS | PASS | PASS | PASS | PASS | PASS | `61286d92c0e244167a5fe52cbc877cdd8ee316fa624bd5eb36ca94eb62c742ed` |
| 7.0.6 | 8.5.11 | `wordpress:7.1.2-php8.5-apache` | `sha256:9d881655fccdcfd19b779320ca819310b51061e1ba3c086012ecbf69a177d521` | PASS | PASS | PASS | PASS | PASS | PASS | `5786cfdd2ac9551c5014246920a110f87aa51f66c840cdac290f2b5a699a9a52` |
| 7.1.2 | 8.3.35 | `wordpress:7.1.2-php8.3-apache` | `sha256:4abf7a450ee477dde967584f8174d7e03221d224c4971a0c38d84e7254426e64` | PASS | PASS | PASS | PASS | PASS | PASS | `bd62265f284191c7f8aca6f5294344c9b2c1a7c2aa7b609c35b68e0c991867a9` |
| 7.1.2 | 8.4.26 | `wordpress:7.1.2-php8.4-apache` | `sha256:85ee71a393b0f3f7a45b2e293978f1c9ab94fb421161080e54f911eeb2501bdc` | PASS | PASS | PASS | PASS | PASS | PASS | `69cfeeeb201b001eb05d50d03c58cf6ecfeeeaae35c1a026a6fed1d3f5e081ea` |
| 7.1.2 | 8.5.11 | `wordpress:7.1.2-php8.5-apache` | `sha256:9d881655fccdcfd19b779320ca819310b51061e1ba3c086012ecbf69a177d521` | PASS | PASS | PASS | PASS | PASS | PASS | `012c7e063753d2ccd6c3c4ff991d2e1055766530127690394f3530e6b5365476` |

Fixture SHA-256: `8ff7322ca6afdd134c96a9cc0c6fe6a87ed00ac405fc3f300606b8fb6f082975`
for `tests/compat/fixtures/shows.csv`.

Verification command:

```text
rtk bash tests/compat/run.sh matrix --scenario full-workflows --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL
```

## D-03 runtime-floor lifecycle

The following lifecycle passed on both WordPress 7.0.6 and 7.1.2 using the
same isolated database for each 8.3 → 8.2 → 8.3 transition:

```text
rtk bash tests/compat/run.sh runtime-floor --plugin gigpress/gigpress.php --wp-lines 7.0,7.1 --supported-php 8.3 --diagnostic-php 8.2
```

The already-active plugin retained its active state and stored data under the
diagnostic PHP 8.2 transition, exposed only its repeated authorized manager
notice, left normal modules/hooks/functions unavailable, did not expose the
notice to unauthorized or public requests, and recovered normal behavior with
the notice removed on PHP 8.3. The low-floor checks booted the actual WordPress
7.0.6 and 7.1.2 sites with GigPress already active; they did not use the plugin
API facade for the real-plugin path.

## D-02 diagnostic-only evidence

PHP 8.2 remains diagnostic-only and contributes to no supported row. The
controlled exact-key trace remains recorded in
[`01-MENU-VERIFICATION.md`](./01-MENU-VERIFICATION.md): it attributes
`separator-gigpress` to the fixture callback at priority 20. It does not
identify the unavailable live-site callback.
