# Required Phase 05 review follow-ups

Origin: Phase 03 source review, 03-REVIEW.md and 03-REVIEW-DISPOSITION.md. These inherited defects remain unresolved. Phase 05 planning and milestone completion must account for all three; this ledger is not an accepted risk or a completed fix.

| Finding | Required work | Required evidence |
|---|---|---|
| CR-04 | Destination-encode every imported artist/city/venue, filename and error notice, including skipped and upload-failure outcomes. | Actual hostile CSV import outcomes remain text, cannot create executable markup and preserve intended stored data. |
| CR-05 | Tour conversion must verify artist identity and every show reassignment before deleting the source; preserve failed relationships for safe retry and accumulate all outcomes. | Inject INSERT/UPDATE/DELETE failures for early and late tours; no dangling relationships/data loss, truthful partial results, safe retry without duplicates. |
| CR-07 | Use textual show_status format and validate active/cancelled/soldout supported values; prevent positional format drift. | Round-trip every supported status, exact relationships/counts; an explicit human decision for preexisting status 0 because original status cannot be inferred. |

Keep Phase 04 migrated public/feed/template and Phase 05 migrated CSV evidence separate from synthetic fresh-workflow matrix checks.
