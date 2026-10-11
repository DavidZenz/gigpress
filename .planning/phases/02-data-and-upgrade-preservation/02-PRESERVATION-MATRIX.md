# Phase 02 Preservation Matrix

**Latest rerun: all 11 preservation cases passed in each of six supported runtime cells. The aggregate completed with zero warnings, fatals, or plugin errors.**

## Scope and evidence boundary

The fixtures are reconstructed from repository evidence. They model direct upgrades
from database versions 1.0 through 1.6 with nondefault prefixes, populated records,
relationships, settings, linked content, interruption/retry paths, and show-management
operations. No live backup or live site was tested.

At run time, the harness resolves the latest stable patch for each WordPress minor line
once, then reuses that pinned patch across all PHP branches for the line. It uses the
[WordPress Core Version Check API](https://api.wordpress.org/core/version-check/1.7/)
and reads PHP branches from the [upstream supported-versions page](https://www.php.net/supported-versions.php),
keeping branches at or above the PHP 8.3 project minimum. The latest final-source rerun resolved WordPress 7.0.7 and 7.1.3, with PHP 8.3, 8.4, and 8.5; all six cells passed all 11 registered cases. PHP 8.2 is diagnostic-only and has no
supported result in this report. The evidence validator checks that every cell for a
WordPress line reports the same patch version.

The matrix ran from base revision `0191910d12e8f9e0f8fb6b57884457f3ee996902` with the working-tree update to `tests/compat/upgrade-preservation-crud.php` described below.

## Commands

```text
rtk bash tests/compat/run.sh matrix --scenario upgrade-preservation --case all --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL
rtk bash tests/compat/run.sh matrix --scenario full-workflows --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL
rtk bash tests/compat/run.sh preservation-evidence --report .planning/phases/02-data-and-upgrade-preservation/02-PRESERVATION-MATRIX.md --wp-lines 7.0,7.1 --php-min 8.3
```

## Latest final-source rerun

Command: `rtk proxy bash tests/compat/run.sh matrix --scenario upgrade-preservation --case all --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL`

| WordPress | PHP | Runtime result | Case results |
| --- | --- | --- | --- |
| 7.0.7 | 8.3.35 | PASS | 11 PASS |
| 7.0.7 | 8.4.26 | PASS | 11 PASS |
| 7.0.7 | 8.5.11 | PASS | 11 PASS |
| 7.1.3 | 8.3.35 | PASS | 11 PASS |
| 7.1.3 | 8.4.26 | PASS | 11 PASS |
| 7.1.3 | 8.5.11 | PASS | 11 PASS |

The prior failure came from stale assertions in the Phase 02 lifecycle test: it expected the retired one-click tour conversion to write immediately, and it invoked the CSV import without its required POST action. The updated test now verifies that the stale conversion route is rejected without writes and submits a valid import request for the readiness and nonce checks. The confirmed tour conversion path is covered by the Phase 05 conversion cases. The separate full-workflows result below is historical and was not rerun by this command.

## Earlier runtime cells

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

## Preservation case coverage

All cases below passed in each cell of the latest final-source rerun.

| Case | Reconstructed fixture versions | DATA behavior proved |
| --- | --- | --- |
| `tracer-1.4` | 1.4 | Representative migration preserves rows, IDs, relationships, settings, and linked content. |
| `safety-1.4` | 1.4 | Interrupted migration preserves the marker and converges safely on retry. |
| `metadata-classification` | 1.4 | Unsafe metadata blocks mutation without changing stored data. |
| `versions-1.0-1.2` | 1.0, 1.1, 1.2 | Early direct upgrades preserve manifests and retry safely. |
| `versions-1.3-1.5` | 1.3, 1.5 | Later direct upgrades retain deterministic mappings. |
| `current-1.6` | 1.6 | Current populated state remains a no-op on repeat bootstrap. |
| `settings-repeat` | 1.0–1.6 | Falsey, disabled, custom, and unknown settings remain stable. |
| `show-lifecycle` | 1.4 | Create/edit/copy/trash/restore remain usable; the retired one-click conversion is rejected without writes; current mutation and import requests respect readiness and nonce guards. |
| `optional-request-fields` | 1.4 | Missing optional show and venue POST fields normalize without warnings and persist empty values. |
| `entity-guards` | 1.4 | Active and trashed dependencies prevent artist/venue deletion without cascade. |
| `tour-undo` | 1.4 | Tour undo restores only owned detached shows and preserves reassignment. |

## Requirement coverage

### DATA-01 — PASS

Fixtures 1.0 through 1.6 retain show, artist, venue, and tour IDs, row values,
relationships, linked posts, table prefix, and settings. Interruption/retry safety,
metadata classification, final markers, and repeat bootstrap all passed.

### DATA-02 — PASS for preservation handlers; broad full-workflows evidence is historical

The upgraded fixture exercises real handlers for create, edit, copy, selected trash,
restore, dependency guards, ownership-safe tour undo, and requests with omitted optional
show and venue fields. Full workflows also passed administration, shortcode, RSS,
iCalendar, CSV import/export, and duplicate handling.

<!-- preservation-evidence: {"schema":"gigpress-preservation-evidence/v1","support_boundary":{"php_min":"8.3","diagnostic_only_php":["8.2"]},"wordpress_lines":["7.0","7.1"],"php_branches":["8.3","8.4","8.5"],"fixtures":["1.0","1.1","1.2","1.3","1.4","1.5","1.6"],"commands":["rtk bash tests/compat/run.sh matrix --scenario upgrade-preservation --case all --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL","rtk bash tests/compat/run.sh matrix --scenario full-workflows --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL"],"requirements":{"DATA-01":{"status":"PASS"},"DATA-02":{"status":"PASS"}},"required_case_statuses":{"tracer-1.4":"PASS","safety-1.4":"PASS","metadata-classification":"PASS","versions-1.0-1.2":"PASS","versions-1.3-1.5":"PASS","current-1.6":"PASS","settings-repeat":"PASS","show-lifecycle":"PASS","optional-request-fields":"PASS","entity-guards":"PASS","tour-undo":"PASS"},"cells":[{"wordpress_line":"7.0","wordpress_version":"7.0.7","php_branch":"8.3","php_version":"8.3.35","status":"PASS","warning_count":0,"fatal_count":0,"plugin_error_count":0,"required_case_status":"PASS","upgrade_preservation":{"status":"PASS","cases_passed":11,"cases_failed":0},"full_workflows":{"status":"PASS"},"image":"wordpress:php8.3-apache","image_id":"sha256:f32ffa85064d9c951b7b69c929fa2a9f682aa4443e0c0a56ac2000499cb64383"},{"wordpress_line":"7.0","wordpress_version":"7.0.7","php_branch":"8.4","php_version":"8.4.26","status":"PASS","warning_count":0,"fatal_count":0,"plugin_error_count":0,"required_case_status":"PASS","upgrade_preservation":{"status":"PASS","cases_passed":11,"cases_failed":0},"full_workflows":{"status":"PASS"},"image":"wordpress:php8.4-apache","image_id":"sha256:309307430abf80009378a63a79df73554a6449b700fac59a991b4959e6dda8ca"},{"wordpress_line":"7.0","wordpress_version":"7.0.7","php_branch":"8.5","php_version":"8.5.11","status":"PASS","warning_count":0,"fatal_count":0,"plugin_error_count":0,"required_case_status":"PASS","upgrade_preservation":{"status":"PASS","cases_passed":11,"cases_failed":0},"full_workflows":{"status":"PASS"},"image":"wordpress:php8.5-apache","image_id":"sha256:c12bed9c5af9d76261221c2af3a6216a074dd80b4fc2459588b3cbf2dd8e1798"},{"wordpress_line":"7.1","wordpress_version":"7.1.3","php_branch":"8.3","php_version":"8.3.35","status":"PASS","warning_count":0,"fatal_count":0,"plugin_error_count":0,"required_case_status":"PASS","upgrade_preservation":{"status":"PASS","cases_passed":11,"cases_failed":0},"full_workflows":{"status":"PASS"},"image":"wordpress:php8.3-apache","image_id":"sha256:f32ffa85064d9c951b7b69c929fa2a9f682aa4443e0c0a56ac2000499cb64383"},{"wordpress_line":"7.1","wordpress_version":"7.1.3","php_branch":"8.4","php_version":"8.4.26","status":"PASS","warning_count":0,"fatal_count":0,"plugin_error_count":0,"required_case_status":"PASS","upgrade_preservation":{"status":"PASS","cases_passed":11,"cases_failed":0},"full_workflows":{"status":"PASS"},"image":"wordpress:php8.4-apache","image_id":"sha256:309307430abf80009378a63a79df73554a6449b700fac59a991b4959e6dda8ca"},{"wordpress_line":"7.1","wordpress_version":"7.1.3","php_branch":"8.5","php_version":"8.5.11","status":"PASS","warning_count":0,"fatal_count":0,"plugin_error_count":0,"required_case_status":"PASS","upgrade_preservation":{"status":"PASS","cases_passed":11,"cases_failed":0},"full_workflows":{"status":"PASS"},"image":"wordpress:php8.5-apache","image_id":"sha256:c12bed9c5af9d76261221c2af3a6216a074dd80b4fc2459588b3cbf2dd8e1798"}],"final_source_rerun":{"command":"rtk proxy bash tests/compat/run.sh matrix --scenario upgrade-preservation --case all --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL","source_revision":"0191910d12e8f9e0f8fb6b57884457f3ee996902","working_tree_test_update":"tests/compat/upgrade-preservation-crud.php now asserts the stale one-click tour action is a no-op and submits import guard cases using the required POST action","wordpress_versions":{"7.0":"7.0.7","7.1":"7.1.3"},"php_versions":{"8.3":"8.3.35","8.4":"8.4.26","8.5":"8.5.11"},"aggregate_status":"PASS","per_cell_case_counts":{"pass":11,"fail":0},"other_registered_cases_status":"PASS","warning_count":0,"fatal_count":0,"plugin_error_count":0,"full_workflows_status":"historical-not-rerun"}} -->
