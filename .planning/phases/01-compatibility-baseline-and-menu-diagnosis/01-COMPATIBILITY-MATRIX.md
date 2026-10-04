# Compatibility Matrix Evidence

**Checked:** 2026-10-04T07:20:40Z

Source revision: `0fab24d1c2cf470f8308011dd2c2e71ef2514ecd`

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
| 7.0.6 | 8.3.35 | `wordpress:7.1.2-php8.3-apache` | `sha256:4abf7a450ee477dde967584f8174d7e03221d224c4971a0c38d84e7254426e64` | PASS | PASS | PASS | PASS | PASS | PASS | `a79135c3ddedc24747ad296a659b967c7237ef3e20e300ee0d485deddffd71ab` |
| 7.0.6 | 8.4.26 | `wordpress:7.1.2-php8.4-apache` | `sha256:85ee71a393b0f3f7a45b2e293978f1c9ab94fb421161080e54f911eeb2501bdc` | PASS | PASS | PASS | PASS | PASS | PASS | `a014ade8cc19aae484d4bb385042cad3961dbbec58c6902c9ecda2d702ea087a` |
| 7.0.6 | 8.5.11 | `wordpress:7.1.2-php8.5-apache` | `sha256:9d881655fccdcfd19b779320ca819310b51061e1ba3c086012ecbf69a177d521` | PASS | PASS | PASS | PASS | PASS | PASS | `795d005bff0da2cf63b7784f5e9765095e527c2e1d5371a2f6695d27528ceb5a` |
| 7.1.2 | 8.3.35 | `wordpress:7.1.2-php8.3-apache` | `sha256:4abf7a450ee477dde967584f8174d7e03221d224c4971a0c38d84e7254426e64` | PASS | PASS | PASS | PASS | PASS | PASS | `6be9567e177b8a53b999dc3b32b7c36213139dde9ec3c99e9749ca9abe7be704` |
| 7.1.2 | 8.4.26 | `wordpress:7.1.2-php8.4-apache` | `sha256:85ee71a393b0f3f7a45b2e293978f1c9ab94fb421161080e54f911eeb2501bdc` | PASS | PASS | PASS | PASS | PASS | PASS | `72e553da5c1f25dc2cab1a3787d8ec701afa530e230cd9b3156b8cc7e578c033` |
| 7.1.2 | 8.5.11 | `wordpress:7.1.2-php8.5-apache` | `sha256:9d881655fccdcfd19b779320ca819310b51061e1ba3c086012ecbf69a177d521` | PASS | PASS | PASS | PASS | PASS | PASS | `29e6d0c4026aead5ae978637d792a6a7cc07184356b07d110ce6cbeaacae70af` |

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
the notice removed on PHP 8.3. WordPress 7.0 cannot fully bootstrap under the
diagnostic PHP runtime, so the runner uses its supported-runtime bootstrap
snapshot and direct low-runtime assertion for that deliberately diagnostic step.

## D-02 diagnostic-only evidence

PHP 8.2 remains diagnostic-only and contributes to no supported row. The
controlled exact-key trace remains recorded in
[`01-MENU-VERIFICATION.md`](./01-MENU-VERIFICATION.md): it attributes
`separator-gigpress` to the fixture callback at priority 20. It does not
identify the unavailable live-site callback.
