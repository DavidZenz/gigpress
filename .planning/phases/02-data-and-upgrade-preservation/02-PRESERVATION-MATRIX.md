# Phase 02 Preservation Matrix

**Repository-reconstructed upgrade fixtures and existing workflows passed the declared WordPress 7.0/7.1 and PHP 8.3+ support matrix.**

## Scope and evidence boundary

The fixtures are reconstructed from repository evidence. They model direct upgrades
from database versions 1.0 through 1.6 with nondefault prefixes, populated records,
relationships, settings, linked content, interruption/retry paths, and show-management
operations. No live backup or live site was tested.

At run time, the harness resolves the latest stable patch for each WordPress minor line
once, then reuses that pinned patch across all PHP branches for the line. It uses the
[WordPress Core Version Check API](https://api.wordpress.org/core/version-check/1.7/)
and reads PHP branches from the [upstream supported-versions page](https://www.php.net/supported-versions.php),
keeping branches at or above the PHP 8.3 project minimum. This run resolved WordPress
7.0.6 and 7.1.2, and PHP 8.3, 8.4, and 8.5. PHP 8.2 is diagnostic-only and has no
supported result in this report. The evidence validator checks that every cell for a
WordPress line reports the same patch version.

Source revision: `a3591fb13521bbddaea84e9be479aea302cee1b3`

## Commands

```text
rtk bash tests/compat/run.sh matrix --scenario upgrade-preservation --case all --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL
rtk bash tests/compat/run.sh matrix --scenario full-workflows --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL
rtk bash tests/compat/run.sh preservation-evidence --report .planning/phases/02-data-and-upgrade-preservation/02-PRESERVATION-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3
```

## Exact runtime cells

| WordPress line | Resolved patch | PHP | Image tag | Image ID | Aggregate | Workflows | Warnings / fatals / plugin errors |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 7.0 | 7.0.6 | 8.3.35 | `wordpress:php8.3-apache` | `sha256:4abf7a450ee477dde967584f8174d7e03221d224c4971a0c38d84e7254426e64` | PASS | PASS | 0 / 0 / 0 |
| 7.0 | 7.0.6 | 8.4.26 | `wordpress:php8.4-apache` | `sha256:85ee71a393b0f3f7a45b2e293978f1c9ab94fb421161080e54f911eeb2501bdc` | PASS | PASS | 0 / 0 / 0 |
| 7.0 | 7.0.6 | 8.5.11 | `wordpress:php8.5-apache` | `sha256:9d881655fccdcfd19b779320ca819310b51061e1ba3c086012ecbf69a177d521` | PASS | PASS | 0 / 0 / 0 |
| 7.1 | 7.1.2 | 8.3.35 | `wordpress:php8.3-apache` | `sha256:4abf7a450ee477dde967584f8174d7e03221d224c4971a0c38d84e7254426e64` | PASS | PASS | 0 / 0 / 0 |
| 7.1 | 7.1.2 | 8.4.26 | `wordpress:php8.4-apache` | `sha256:85ee71a393b0f3f7a45b2e293978f1c9ab94fb421161080e54f911eeb2501bdc` | PASS | PASS | 0 / 0 / 0 |
| 7.1 | 7.1.2 | 8.5.11 | `wordpress:php8.5-apache` | `sha256:9d881655fccdcfd19b779320ca819310b51061e1ba3c086012ecbf69a177d521` | PASS | PASS | 0 / 0 / 0 |

WordPress 7.0.6 uses its official core archive over each PHP/Apache base image. WordPress
7.1.2 is installed directly by the same official images. Image IDs are recorded for
every cell and checked by the evidence parser.

## Required preservation cases

Every case below passed once in every runtime cell; each aggregate remained active and
ready after completion.

| Case | Reconstructed fixture versions | DATA behavior proved |
| --- | --- | --- |
| `tracer-1.4` | 1.4 | Representative migration preserves rows, IDs, relationships, settings, and linked content. |
| `safety-1.4` | 1.4 | Interrupted migration preserves the marker and converges safely on retry. |
| `metadata-classification` | 1.4 | Unsafe metadata blocks mutation without changing stored data. |
| `versions-1.0-1.2` | 1.0, 1.1, 1.2 | Early direct upgrades preserve manifests and retry safely. |
| `versions-1.3-1.5` | 1.3, 1.5 | Later direct upgrades retain deterministic mappings. |
| `current-1.6` | 1.6 | Current populated state remains a no-op on repeat bootstrap. |
| `settings-repeat` | 1.0–1.6 | Falsey, disabled, custom, and unknown settings remain stable. |
| `show-lifecycle` | 1.4 | Create/edit/copy/trash/restore remain usable; nine mutation handlers block writes or uploads while readiness is blocked, and forged requests without a nonce are rejected. |
| `optional-request-fields` | 1.4 | Missing optional show and venue POST fields normalize without warnings and persist empty values. |
| `entity-guards` | 1.4 | Active and trashed dependencies prevent artist/venue deletion without cascade. |
| `tour-undo` | 1.4 | Tour undo restores only owned detached shows and preserves reassignment. |

## Requirement coverage

### DATA-01 — PASS

Fixtures 1.0 through 1.6 retain show, artist, venue, and tour IDs, row values,
relationships, linked posts, table prefix, and settings. Interruption/retry safety,
metadata classification, final markers, and repeat bootstrap all passed.

### DATA-02 — PASS

The upgraded fixture exercises real handlers for create, edit, copy, selected trash,
restore, dependency guards, ownership-safe tour undo, and requests with omitted optional
show and venue fields. Full workflows also passed administration, shortcode, RSS,
iCalendar, CSV import/export, and duplicate handling.

<!-- preservation-evidence: {"schema":"gigpress-preservation-evidence/v1","support_boundary":{"php_min":"8.3","diagnostic_only_php":["8.2"]},"wordpress_lines":["7.0","7.1"],"php_branches":["8.3","8.4","8.5"],"fixtures":["1.0","1.1","1.2","1.3","1.4","1.5","1.6"],"commands":["rtk bash tests/compat/run.sh matrix --scenario upgrade-preservation --case all --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL","rtk bash tests/compat/run.sh matrix --scenario full-workflows --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL"],"requirements":{"DATA-01":{"status":"PASS"},"DATA-02":{"status":"PASS"}},"required_case_statuses":{"tracer-1.4":"PASS","safety-1.4":"PASS","metadata-classification":"PASS","versions-1.0-1.2":"PASS","versions-1.3-1.5":"PASS","current-1.6":"PASS","settings-repeat":"PASS","show-lifecycle":"PASS","optional-request-fields":"PASS","entity-guards":"PASS","tour-undo":"PASS"},"cells":[{"wordpress_line":"7.0","wordpress_version":"7.0.6","php_branch":"8.3","php_version":"8.3.35","status":"PASS","warning_count":0,"fatal_count":0,"plugin_error_count":0,"required_case_status":"PASS","upgrade_preservation":{"status":"PASS"},"full_workflows":{"status":"PASS"},"image":"wordpress:php8.3-apache","image_id":"sha256:4abf7a450ee477dde967584f8174d7e03221d224c4971a0c38d84e7254426e64"},{"wordpress_line":"7.0","wordpress_version":"7.0.6","php_branch":"8.4","php_version":"8.4.26","status":"PASS","warning_count":0,"fatal_count":0,"plugin_error_count":0,"required_case_status":"PASS","upgrade_preservation":{"status":"PASS"},"full_workflows":{"status":"PASS"},"image":"wordpress:php8.4-apache","image_id":"sha256:85ee71a393b0f3f7a45b2e293978f1c9ab94fb421161080e54f911eeb2501bdc"},{"wordpress_line":"7.0","wordpress_version":"7.0.6","php_branch":"8.5","php_version":"8.5.11","status":"PASS","warning_count":0,"fatal_count":0,"plugin_error_count":0,"required_case_status":"PASS","upgrade_preservation":{"status":"PASS"},"full_workflows":{"status":"PASS"},"image":"wordpress:php8.5-apache","image_id":"sha256:9d881655fccdcfd19b779320ca819310b51061e1ba3c086012ecbf69a177d521"},{"wordpress_line":"7.1","wordpress_version":"7.1.2","php_branch":"8.3","php_version":"8.3.35","status":"PASS","warning_count":0,"fatal_count":0,"plugin_error_count":0,"required_case_status":"PASS","upgrade_preservation":{"status":"PASS"},"full_workflows":{"status":"PASS"},"image":"wordpress:php8.3-apache","image_id":"sha256:4abf7a450ee477dde967584f8174d7e03221d224c4971a0c38d84e7254426e64"},{"wordpress_line":"7.1","wordpress_version":"7.1.2","php_branch":"8.4","php_version":"8.4.26","status":"PASS","warning_count":0,"fatal_count":0,"plugin_error_count":0,"required_case_status":"PASS","upgrade_preservation":{"status":"PASS"},"full_workflows":{"status":"PASS"},"image":"wordpress:php8.4-apache","image_id":"sha256:85ee71a393b0f3f7a45b2e293978f1c9ab94fb421161080e54f911eeb2501bdc"},{"wordpress_line":"7.1","wordpress_version":"7.1.2","php_branch":"8.5","php_version":"8.5.11","status":"PASS","warning_count":0,"fatal_count":0,"plugin_error_count":0,"required_case_status":"PASS","upgrade_preservation":{"status":"PASS"},"full_workflows":{"status":"PASS"},"image":"wordpress:php8.5-apache","image_id":"sha256:9d881655fccdcfd19b779320ca819310b51061e1ba3c086012ecbf69a177d521"}]} -->
