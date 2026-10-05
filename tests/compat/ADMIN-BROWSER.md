# Disposable administration browser fixture

Run from the repository root through OrbStack. No host PHP or package install is used.

```
rtk proxy bash tests/compat/run.sh browser-fixture --action smoke --wp 7.1.2 --php 8.3 --case entry
rtk proxy bash tests/compat/run.sh browser-fixture --action smoke --wp 7.1.2 --php 8.3 --case all
rtk proxy bash tests/compat/run.sh browser-fixture --action start --wp 7.1.2 --php 8.3
rtk proxy bash tests/compat/run.sh browser-fixture --action status --session /private/tmp/gigpress-browser-XXXXXX/session.json
rtk proxy bash tests/compat/run.sh browser-fixture --action stop --session /private/tmp/gigpress-browser-XXXXXX/session.json
```

Start emits a loopback URL and the private session path. The session file has mode 0600 inside a mode 0700 temporary directory. Read `admin_user`, `subscriber_user`, and `password` privately for login; never paste credentials into committed evidence. Only synthetic users and rows are seeded. Repository files are mounted read only. WordPress core is installed from the exact requested official archive before seeding.

Smoke uses actual login cookies, rendered nonces and form POST, then independent database read-back. Cases are entry, settings, guards, and all; all requires every nonempty case. Settings exercises real options.php plus invalid nonce/subscriber requests and independent protected/unknown/falsey snapshots. Guards exercises exact selected IDs, preview/Cancel/Confirm, bypass, nonce, ownership, tampering and replay denial. It always removes its owned services and volumes, including on failure or interruption. Start retains the session for browser interaction; the executor must stop it in final cleanup. Status and stop require private, same-user metadata tied to this repository, a restricted project prefix, and matching container ownership labels. Foreign metadata is rejected before Docker teardown.

Open `/wp-admin/admin.php?page=gigpress/gigpress.php`, `/wp-admin/admin.php?page=gigpress-shows`, and `/wp-admin/admin.php?page=gigpress-settings` after login. Record browser version, exact fixture runtime and source revision, actual actions, observed outcomes, and failed or pending checks. HTTP assertions alone do not certify keyboard, picker, focus or disabled scripts.

Entry procedure: choose start and end dates with the native picker; equal dates; optional time, midnight and minute 17; toggle multi-day using Space and observe focus. Submit an incomplete native date and record what reached the server. Correct the recovered text and explicit replacement picker. Exercise new artist/venue/tour/post choices, notes and related-date radio recovery. Follow error-summary links, success Edit/List links, and add another show.

List procedure: Tab/Space select explicit rows, Enter request single and bulk trash; observe identity/count, Cancel and Confirm. Exercise scope/entity/sort/page-size/page choices and narrow Reset; an empty filter must explain the outcome. Observe mixed outcomes with a row changed between preview and Confirm. Repeat the entry and list sequence with script execution genuinely disabled in browser settings, documenting that setting and the resulting behavior.

Settings procedure: reach and activate all six jump links by keyboard; Advanced is visible immediately; inspect labels/help and save one editable value using the single Save Changes button. Reload and independently read storage. Always stop the owned session afterwards, and record cleanup results without secrets.
