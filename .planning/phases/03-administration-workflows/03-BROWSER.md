---
phase: 03-administration-workflows
status: human_needed
observed: 2026-10-05
---

# Actual browser and authenticated HTTP evidence

## Environment and scope

Disposable synthetic WordPress 7.1.2 / PHP 8.3.35 fixture, loopback only. Official container image ID: `sha256:4abf7a450ee477dde967584f8174d7e03221d224c4971a0c38d84e7254426e64`. The fixture started at source `def9b51`; its read-only repository mount subsequently included the committed corrected-edit fix `63f0ace`. No real site data or credentials are included here.

Actual browser: Codex In-app Browser, browser ID 2, agent-created tab 3. Its backend exposes no engine version or script-disable capability; Chrome automation was unavailable. Engine version and genuinely disabled-script acceptance therefore cannot be certified. Keyboard observations below use real browser controls; PHP/HTTP assertions are recorded separately.

## Observed entry behavior

| Check | Actual outcome |
|---|---|
| Native picker | Opened the native calendar; Right then Return selected 2032-05-07 from 2032-05-06. |
| Optional time and minute | Initial Not specified choice disabled minutes; choosing 12 AM enabled minute 17. Saved edit retained 12 AM / 17. |
| Multi-day keyboard | Space revealed the end date and existing cutoff help; focus remained on the checkbox. Equal start/end 2032-05-07 saved. |
| Save navigation | Add via Enter showed success, saved Edit and View list links; fresh Add form reset multi-day. Saved Edit retained dates, minute, artist, venue and notes. |
| Incomplete native input | Typed incomplete `07`; browser reported badInput=true and value empty. Submission received an empty date, displayed linked validation and an editable authoritative text field plus separate replacement picker. No claim that invisible `07` reached the server. |
| Failed-entry recovery | Date/end, time 17, multi-day, existing artist/venue and notes remained editable. Raw `not-a-date` survived a subsequent rejected submission exactly. |
| Error link focus | Enter on the error-summary link focused `show_date`, observed through accessibility and activeElement. |
| Explicit replacement | Selected replacement 2032-05-07 and checked its explicit checkbox with Space; update saved. |
| Corrected-edit rendering | Browser found stale rejected text after successful persistence. Regression RED `ef92b95` reproduced four display failures; GREEN fix `63f0ace` passed 73 entry-controls checks. Browser retest showed both saved dates 2032-05-07, no replacement retry controls, retained notes and success/Edit/List feedback. |

## Observed settings behavior

All six jump links were activated with Enter: Display & formatting, Show labels & links, Related posts, Feeds, Permissions, Advanced. Each moved the fragment and activeElement to its matching heading. Advanced was immediately present, with labeled radios/checkboxes and explanatory help. A single Save changes submitted Artist label = Browser performer; Settings saved appeared and a later reload retained that value. Seeded uncommon current choices remained visible. Independent protected/unknown/falsey storage checks belong to HTTP evidence below.

## Observed list behavior

Space selected shows #1 and #2; Enter on Trash selected shows opened a two-ID review with dates/artist/venue and Confirm/Cancel. Cancel reported no changes and retained 27 shows. Single-show Trash #1 opened a one-ID review. Confirm moved only #1, reported one success/zero failures and provided Undo; Undo reported one restored and restored the count to 27.

Descending sort and page size 10 were submitted with the Filter button. Page 2 displayed 11–20 of 27 and retained descending/10 in controls and links. Reset returned page 1 and preserved scope/page size/sort while clearing entity filters. Past scope showed the explicit empty-state text and Reset link. Filter and pagination were verified by pointer activation; attempted locator Enter on those controls did not navigate, so a full keyboard traversal of those paths is not certified.

## Separate automated HTTP evidence

Authenticated real login/form/nonces/options.php requests, independent database/option snapshots, unauthorized/invalid-nonce denial and selected-only mutation guards passed 48 named assertions (entry 9, settings 19, guards 19, aggregate 1). Initial recovered run: 21 seconds. Final committed-source rerun at `edf959e75c7f74f692a6f57cb33749648b3d0f06`: 17 seconds, WordPress 7.1.2/PHP 8.3.35, zero PHP/HTTP errors, loopback_only=true; cleanup PASS with both services_removed and volumes_removed true.

## Required human checks still pending

1. Genuinely disable script execution and repeat entry date correction/multi-day and single/bulk list Confirm/Cancel, including received-state preservation. The available automation cannot disable JavaScript; callback/HTTP evidence does not substitute for this.
2. Full Tab traversal and Enter activation of filter/pagination/Reset; automatic success/error notice focus. Explicit entry error-link focus and settings jump focus passed, but automatic notice focus was not observed.
3. Browser recovery of new artist/venue/tour/post choices and related-date radio; no-time versus exact midnight; mixed list outcomes when one selected row changes between preview and Confirm. Integration and HTTP checks cover the data contracts, but these complete browser sequences were not observed.

The three descriptor-less product prohibitions and ADMIN-03 unclassified edge remain downstream review items. This report does not certify migrated public/feed/template or CSV integration (Phases 04/05).

## Fixture cleanup

Owned-session stop exited 0 with cleanup PASS, services_removed=true and volumes_removed=true. Only the verified owned project was removed. The agent-created browser tab was closed. No credentials or private session metadata are committed.

## Post-review HTTP regression (2026-10-05)

At source f3f1781 (the production fix was present during the preceding run), real HTTP all now passes 54 checks: entry 9, settings 19, guards 25, aggregate 1. New checks use the action nonce delivered on the actual Artists page, subscriber/invalid-nonce/GET denial snapshots and exact successful JSON subset read-back preserving omitted artists. Artists and Venues render their dependency guard normally. Runtime remains WordPress 7.1.2/PHP 8.3.35, zero PHP/HTTP errors, loopback-only, owned cleanup confirmed. The initial extra-guard run failed honestly with an Artists undefined helper fatal and insufficient artist fixture rows; both defects were corrected before the passing run. A final committed-source rerun is recorded in the review closeout.

Original interactive observations remain tied to their recorded source. Post-review normalization and settings fallback behavior have real integration checks but no new interactive certification. Additional human check: start with shows_page=/shows/ and rss_limit=' 25 ', save an unrelated editable setting in a real browser, then reload and verify both legacy values remain exact and the form was not blocked. Verify authorized sortable feedback and an unchanged/repeated order in the same session.

## Actual legacy-control browser regression

Codex In-app Browser ID2/tab4 on an owned synthetic WordPress7.1.2/PHP8.3.35 fixture, source fd097d4 (production equal to f3f1781). Seeded only this disposable fixture with shows_page=/shows/ and rss_limit=' 25 '. The browser exposed both as valid text controls and their exact strings; changing Artist label to Legacy browser performer and clicking Save changes produced Settings saved. A reload retained the new label and both original strings. Independent container read-back returned the same exact three values. CR-01's previously pending actual browser submission now passes; it must not remain an unresolved UAT item. Screenshot: 03-legacy-settings-browser.jpg (synthetic data only). Browser engine version remains unavailable.

The tab was closed and the owned fixture stop confirmed services/volumes removed. Authorized sortable feedback remains unobserved interactively; actual AJAX success/denial/read-back checks pass. The three original grouped human procedures, descriptor-less prohibitions and unidentified settings intent remain pending. Final committed HTTP all rerun at fd097d4 passed54 checks in18seconds, zero PHP/HTTP errors and confirmed owned teardown.
