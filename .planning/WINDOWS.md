---
schema_version: 1
open_count: 6
waived_count: 0
fixed_count: 2
total_count: 8
last_updated: 2026-10-04T21:07:11.104Z
---

# Broken Windows Ledger

> Cross-phase defect register. With `workflow.windows_enforce` enabled, `/gsd-ship` blocks while `open_count > 0`.
> Waive with `gsd-tools windows waive <id> "<reason>"` (reason required).
> Mark fixed with `gsd-tools windows fixed <id>`.

| id | phase | kind | file | line | description | status | reason | recorded_at | resolved_at |
|----|-------|------|------|------|-------------|--------|--------|-------------|-------------|
| 1 | 01 | deviation | tests/compat/run.sh |  | User-authorized runner/probe exception implements required metadata and real-plugin runtime-floor commands. | open |  | 2026-10-04T06:34:09.245Z |  |
| 2 | 02 | deviation | tests/compat/upgrade-preservation-migrations.php | 39 | Fixture setting manifests may omit a settings subset; the matrix treats that as no additional setting assertion. | open |  | 2026-10-04T13:38:24.105Z |  |
| 3 | 02 | deviation | tests/compat/probe.php |  | Aggregate child cases required explicit plugin reactivation after inheriting an active-plugin database record. | open |  | 2026-10-04T14:38:06.119Z |  |
| 4 | 02 | deviation | tests/compat/run.sh |  | Preservation evidence validator now quotes hyphenated DATA requirement keys for jq. | open |  | 2026-10-04T14:38:06.302Z |  |
| 5 | 03 | deviation | tests/compat/probe.php |  | Resolved aggregate child-probe bootstrap under WP_INSTALLING by fresh real-plugin activation; verified delivered cases pass and absent cases fail closed. | fixed |  | 2026-10-04T20:52:24.971Z | 2026-10-04T20:52:37.284Z |
| 6 | 03 | deviation | admin/new.php |  | Resolved renderer regression by restoring welcome dismissal with capability, nonce and readiness guards; four regression checks pass. | fixed |  | 2026-10-04T20:52:25.172Z | 2026-10-04T20:52:37.481Z |
| 7 | 03 | unrun-verify | admin/new.php |  | Actual browser picker, incomplete typed date, focus and no-JS keyboard evidence belongs to Plan 03-04 and remains pending. | open |  | 2026-10-04T20:52:25.383Z |  |
| 8 | 03 | unrun-verify | admin/settings.php |  | 03-04 must verify actual settings options.php HTTP save, invalid nonce and unauthorized denial, plus keyboard jump/focus behavior; 03-02 callback and markup checks do not establish these interactions. | open |  | 2026-10-04T21:07:11.104Z |  |

````json
[
  {
    "id": 1,
    "kind": "deviation",
    "phase": "01",
    "file": "tests/compat/run.sh",
    "line": null,
    "description": "User-authorized runner/probe exception implements required metadata and real-plugin runtime-floor commands.",
    "status": "open",
    "reason": "",
    "recorded_at": "2026-10-04T06:34:09.245Z",
    "resolved_at": null,
    "milestone": null
  },
  {
    "id": 2,
    "kind": "deviation",
    "phase": "02",
    "file": "tests/compat/upgrade-preservation-migrations.php",
    "line": 39,
    "description": "Fixture setting manifests may omit a settings subset; the matrix treats that as no additional setting assertion.",
    "status": "open",
    "reason": "",
    "recorded_at": "2026-10-04T13:38:24.105Z",
    "resolved_at": null,
    "milestone": null
  },
  {
    "id": 3,
    "kind": "deviation",
    "phase": "02",
    "file": "tests/compat/probe.php",
    "line": null,
    "description": "Aggregate child cases required explicit plugin reactivation after inheriting an active-plugin database record.",
    "status": "open",
    "reason": "",
    "recorded_at": "2026-10-04T14:38:06.119Z",
    "resolved_at": null,
    "milestone": null
  },
  {
    "id": 4,
    "kind": "deviation",
    "phase": "02",
    "file": "tests/compat/run.sh",
    "line": null,
    "description": "Preservation evidence validator now quotes hyphenated DATA requirement keys for jq.",
    "status": "open",
    "reason": "",
    "recorded_at": "2026-10-04T14:38:06.302Z",
    "resolved_at": null,
    "milestone": null
  },
  {
    "id": 5,
    "kind": "deviation",
    "phase": "03",
    "file": "tests/compat/probe.php",
    "line": null,
    "description": "Resolved aggregate child-probe bootstrap under WP_INSTALLING by fresh real-plugin activation; verified delivered cases pass and absent cases fail closed.",
    "status": "fixed",
    "reason": "",
    "recorded_at": "2026-10-04T20:52:24.971Z",
    "resolved_at": "2026-10-04T20:52:37.284Z",
    "milestone": null
  },
  {
    "id": 6,
    "kind": "deviation",
    "phase": "03",
    "file": "admin/new.php",
    "line": null,
    "description": "Resolved renderer regression by restoring welcome dismissal with capability, nonce and readiness guards; four regression checks pass.",
    "status": "fixed",
    "reason": "",
    "recorded_at": "2026-10-04T20:52:25.172Z",
    "resolved_at": "2026-10-04T20:52:37.481Z",
    "milestone": null
  },
  {
    "id": 7,
    "kind": "unrun-verify",
    "phase": "03",
    "file": "admin/new.php",
    "line": null,
    "description": "Actual browser picker, incomplete typed date, focus and no-JS keyboard evidence belongs to Plan 03-04 and remains pending.",
    "status": "open",
    "reason": "",
    "recorded_at": "2026-10-04T20:52:25.383Z",
    "resolved_at": null,
    "milestone": null
  },
  {
    "id": 8,
    "kind": "unrun-verify",
    "phase": "03",
    "file": "admin/settings.php",
    "line": null,
    "description": "03-04 must verify actual settings options.php HTTP save, invalid nonce and unauthorized denial, plus keyboard jump/focus behavior; 03-02 callback and markup checks do not establish these interactions.",
    "status": "open",
    "reason": "",
    "recorded_at": "2026-10-04T21:07:11.104Z",
    "resolved_at": null,
    "milestone": null
  }
]
````
