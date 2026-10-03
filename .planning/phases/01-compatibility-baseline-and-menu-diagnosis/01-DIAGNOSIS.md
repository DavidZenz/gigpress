# Menu Warning Diagnosis

## Fixture-only attribution

The disposable WordPress 7.1.2 / PHP 8.2 diagnostic runs the controlled `menu-conflict-plugin.php` fixture. Its `gigpress_menu_conflict_late_add` callback runs at priority 20, appends the synthetic `separator-gigpress` row after WordPress has taken its default order snapshot, and returns the incoming order unchanged. The request-local trace records that row creator, callback identity, priority, its absence from both ordering maps, and the final global menu slugs as separate evidence. PHP 8.2 is diagnostic-only evidence, never a supported compatibility result.

This is fixture-only attribution. It proves the repository diagnostic can reproduce and identify the exact reported key under controlled conditions; it does not identify a callback from the unavailable production installation.

## Checkout evidence: `separator-gp`

The current checkout independently creates `separator-gp` in `gigpress.php` inside `custom_menu_order()` and adds that slug to its rebuilt order. That late global `$menu` mutation has the same conflict-sensitive missing-default-map failure class: WordPress has already captured the default slug order before the callback runs, so an added row can be absent from the default map.

`git log --all -S'separator-gigpress' -- gigpress.php` has no result in this repository. The checkout evidence concerns `separator-gp`; it does not rename or attribute that checkout slug from the live warning text.

## Related historical evidence

The official GigPress support archive contains the historical report [“Undefined index: separator-gp — severe conflicts with other plugins”](https://wordpress.org/support/topic/undefined-index-separator-gp-severe-conflicts-with-other-plugins/). It is related evidence for the late-menu failure class and is not production proof for the currently reported installation or its callback identity.

## Unavailable live-site identity and correction boundary

The reported live site's deployed build, active extensions, and callback registry are unavailable. Its exact callback identity therefore cannot be proven from repository evidence.

Plan 01-04 may correct the checkout's independently observable late `separator-gp` mutation and verify the checkout across the supported PHP 8.3+ matrix. Under D-04, it should preserve GigPress after Comments with its separator only when the ordering contract is safe; otherwise it must return the standard WordPress order.
