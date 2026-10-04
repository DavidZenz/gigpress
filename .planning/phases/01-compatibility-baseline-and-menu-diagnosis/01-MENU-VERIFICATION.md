# Menu Verification Record

## Scope

This record distinguishes the controlled `separator-gigpress` fixture from the
checkout's former `separator-gp` lifecycle defect. It does not identify the
callback running on the unavailable production site.

## D-02 diagnostic-only exact-key attribution

Command:

```text
rtk bash tests/compat/run.sh diagnose-menu --wp 7.1.2 --php 8.2 --diagnostic tests/compat/diagnostics/menu-trace.php --conflict-fixture tests/compat/fixtures/menu-conflict-plugin.php --conflict-mode exact-key-late-add --expect-key separator-gigpress
```

The disposable WordPress 7.1.2 / PHP 8.2.34 request recorded the fixture
callback `gigpress_menu_conflict_late_add` at priority 20 as the sole creator
of `separator-gigpress`. The synthetic key was absent from the input and
returned ordering maps. This is diagnostic-only evidence: GigPress was not
activated because its declared PHP 8.3 floor correctly rejects PHP 8.2.

The captured request had no surfaced `menu_warnings` entry despite the
controlled missing-map state. It therefore proves fixture attribution and the
failure class, but does not claim to reproduce every display condition of the
reported live warning. PHP 8.2 is excluded from every supported pass count.

## Supported preferred-order cells

Every listed cell activated GigPress, emitted zero captured WordPress-menu
warnings and plugin errors, contained each menu slug once, and placed:

```text
edit-comments.php -> separator-gp -> gigpress.php
```

| WordPress | PHP | WordPress image ID |
| --- | --- | --- |
| 7.0.3 | 8.3.33 | `sha256:a09147f15a882b956f67a617e9e1e053adf9322c45c797c2ff7c0e66522bf204` |
| 7.0.3 | 8.4.24 | `sha256:322fedc0b666dfdbbb7c940fc934ec9f5ac4d8b1c4c6147838291b6c7eb197db` |
| 7.0.3 | 8.5.9 | `sha256:33231db025d51deb8963bf8b01c39c07b5686d2559fb20a6263cc1c84d83c938` |
| 7.1.2 | 8.3.35 | `sha256:4abf7a450ee477dde967584f8174d7e03221d224c4971a0c38d84e7254426e64` |
| 7.1.2 | 8.4.26 | `sha256:85ee71a393b0f3f7a45b2e293978f1c9ab94fb421161080e54f911eeb2501bdc` |
| 7.1.2 | 8.5.11 | `sha256:9d881655fccdcfd19b779320ca819310b51061e1ba3c086012ecbf69a177d521` |

Verified by:

```text
rtk bash tests/compat/run.sh matrix --scenario admin-menu --wp-lines 7.0,7.1 --php-supported upstream
```

## Order-only conflict fallback

The controlled fixture was run before (priority 5) and after (priority 20)
GigPress's filter across the same six supported cells. In both positions it
marked the ordering contract as unsafe before menu ordering and the GigPress
callback returned WordPress's incoming standard order unchanged. Each cell had
zero captured menu warnings, no duplicate slugs, no `separator-gp`, and a
usable `gigpress.php` row in standard position after Comments.

```text
rtk bash tests/compat/run.sh matrix --scenario admin-menu --wp-lines 7.0,7.1 --php-supported upstream --conflict-fixture tests/compat/fixtures/menu-conflict-plugin.php --conflict-mode order-only --conflict-position before
rtk bash tests/compat/run.sh matrix --scenario admin-menu --wp-lines 7.0,7.1 --php-supported upstream --conflict-fixture tests/compat/fixtures/menu-conflict-plugin.php --conflict-mode order-only --conflict-position after
```

## Correction boundary

`gigpress.php` now creates its owned `separator-gp` row during `admin_menu`,
after its pages are registered and before WordPress creates the ordering map.
`custom_menu_order()` only transforms its input array, requires exactly one
Comments/GigPress/separator slug, and otherwise returns the incoming standard
order unchanged. It does not mutate global `$menu`.

The exact `separator-gigpress` diagnostic remains attributable only to the
fixture. The unavailable live site's callback identity remains unproven from
repository evidence.
