# Menu Verification Record

**Verified:** 2026-10-04T08:44:16Z
Source revision: `259c0ca92cc99e8b3bfc2f6da16719ea3805f90e`

## Scope

This record distinguishes the controlled `separator-gigpress` fixture from the
checkout's former `separator-gp` lifecycle defect. It does not identify the
callback running on the unavailable production site.

## D-02 diagnostic-only exact-key attribution

Command:

```text
rtk bash tests/compat/run.sh diagnose-menu --wp 7.1.2 --php 8.2 --diagnostic tests/compat/diagnostics/menu-trace.php --conflict-fixture tests/compat/fixtures/menu-conflict-plugin.php --conflict-mode exact-key-late-add --expect-key separator-gigpress
```

Disposable WordPress 7.1.2 requests on PHP 8.2.34 and PHP 8.3.35 recorded the
fixture callback `gigpress_menu_conflict_late_add` at priority 20 as the sole
creator of `separator-gigpress`. The callback adds the row after the
default-order snapshot and returns an empty custom order. WordPress core then
emits the exact `Undefined array key "separator-gigpress"` warning from its
fallback sort; the request-local trace captures the message, row creator,
callback, priority, and ordering maps. Under PHP 8.2 GigPress was not activated
because its declared PHP 8.3 floor correctly rejects that runtime. PHP 8.2 is
excluded from every supported pass count; the PHP 8.3 fixture run is a separate
diagnostic scenario, not a clean compatibility cell.

## Supported preferred-order cells

Every listed cell activated GigPress, emitted zero captured WordPress-menu
warnings and plugin errors, contained each menu slug once, and placed:

```text
edit-comments.php -> separator-gp -> gigpress.php
```

| WordPress | PHP | WordPress image ID |
| --- | --- | --- |
| 7.0.6 | 8.3.35 | `sha256:4abf7a450ee477dde967584f8174d7e03221d224c4971a0c38d84e7254426e64` |
| 7.0.6 | 8.4.26 | `sha256:85ee71a393b0f3f7a45b2e293978f1c9ab94fb421161080e54f911eeb2501bdc` |
| 7.0.6 | 8.5.11 | `sha256:9d881655fccdcfd19b779320ca819310b51061e1ba3c086012ecbf69a177d521` |
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
zero captured menu warnings, no duplicate slugs, and no owned `separator-gp`;
GigPress remained present in WordPress's default menu order.

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

The exact `separator-gigpress` warning remains attributable only to the
controlled fixture. The unavailable live site's callback identity remains
unproven from repository evidence.

## Visual admin check

In disposable WordPress 7.1.2 / PHP 8.3 admin cells, the preferred view showed
Comments → GigPress separator → GigPress. The GigPress Add a show screen opened
without warning or raw diagnostic output. With the order-only conflict fixture,
WordPress's default menu order remained intact, GigPress stayed available at
the end of the sidebar without its owned separator, and the same Add a show
screen opened successfully. The live-site callback identity remains outside
this check's scope.
