---
schema_version: 1
open_count: 2
waived_count: 0
fixed_count: 0
total_count: 2
last_updated: 2026-10-04T13:38:24.105Z
---

# Broken Windows Ledger

> Cross-phase defect register. With `workflow.windows_enforce` enabled, `/gsd-ship` blocks while `open_count > 0`.
> Waive with `gsd-tools windows waive <id> "<reason>"` (reason required).
> Mark fixed with `gsd-tools windows fixed <id>`.

| id | phase | kind | file | line | description | status | reason | recorded_at | resolved_at |
|----|-------|------|------|------|-------------|--------|--------|-------------|-------------|
| 1 | 01 | deviation | tests/compat/run.sh |  | User-authorized runner/probe exception implements required metadata and real-plugin runtime-floor commands. | open |  | 2026-10-04T06:34:09.245Z |  |
| 2 | 02 | deviation | tests/compat/upgrade-preservation-migrations.php | 39 | Fixture setting manifests may omit a settings subset; the matrix treats that as no additional setting assertion. | open |  | 2026-10-04T13:38:24.105Z |  |

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
  }
]
````
