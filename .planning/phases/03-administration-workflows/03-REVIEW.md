---
phase: 03-administration-workflows
reviewed: 2026-10-05T06:40:56Z
depth: standard
files_reviewed: 16
files_reviewed_list:
  - admin/handlers.php
  - admin/new.php
  - admin/settings.php
  - admin/shows.php
  - css/gigpress-admin.css
  - gigpress.php
  - scripts/gigpress-admin.js
  - tests/compat/ADMIN-BROWSER.md
  - tests/compat/administration-entry.php
  - tests/compat/administration-list.php
  - tests/compat/administration-settings.php
  - tests/compat/browser-bootstrap.php
  - tests/compat/compose.browser.yaml
  - tests/compat/probe.php
  - tests/compat/run.sh
  - tests/compat/upgrade-preservation-crud.php
findings:
  critical: 7
  warning: 2
  info: 0
  total: 9
status: issues_found
---

# Phase 03: Code Review Report

**Reviewed:** 2026-10-05T06:40:56Z  
**Depth:** standard  
**Files Reviewed:** 16  
**Status:** issues_found

## Summary

Reviewed every submitted file, the phase diff from `b7d4a71e3ce383643de7363fff5b457e8a962800`, and relevant callers, persistence definitions, and public output consumers. The settings renderer introduces a preservation regression. The rewritten entry validator still permits empty required values after normalization. Five further blockers are inherited defects retained in the submitted production files; their provenance is explicit below. Two warnings concern test reliability and fixture cleanup recovery.

This was a source review. No additional tests, browser sessions, or containers were run. Existing matrix, HTTP, and validator results do not exercise all the counterexamples below. The pending genuine no-JavaScript, keyboard, notice-focus, and mixed-sequence checks in `03-BROWSER.md` remain pending. The browser locator Enter failure alone is insufficient evidence of a source defect. The three descriptor-less prohibitions and ADMIN-03 unclassified edge remain unresolved review items. No structural pre-pass or external reviewer evidence was supplied.

## Narrative Findings (AI reviewer)

### Critical Issues

### CR-01: Native settings controls lose or block unchanged legacy values

**Classification:** BLOCKER  
**File:** `/Users/davidzenz/gigpress/admin/settings.php:114`  
**Related:** `admin/settings.php:18`, `admin/settings.php:44`, `admin/settings.php:67`, `gigpress.php:492`, `gigpress.php:503-505`  
**Provenance:** Introduced by the settings rewrite.

**Issue:** The previous form used text inputs for `shows_page` and `rss_limit`; the new renderer always uses `url` and `number`. With a stored `rss_limit` of `' 25 '`, a number input sanitizes its current value to the empty string. Saving an unrelated setting therefore submits `rss_limit=''`, which the callback deliberately stores rather than preserving the original value. The feed consumer previously casts that value to 25; after this save its empty-value default becomes 100. A stored relative `shows_page='/shows/'` is another counterexample: the URL input is invalid and native validation blocks the entire form, although the server callback explicitly accepts an unchanged legacy value. These behaviors follow the HTML [number value sanitization rules](https://html.spec.whatwg.org/multipage/input.html#number-state-(type=number)) and [URL validity rules](https://html.spec.whatwg.org/multipage/input.html#url-state-(type=url)); they are not runtime observations from the existing matrix.

**Fix:** Render a text fallback for existing scalar values that cannot be represented and submitted exactly by the corresponding native control. Keep native controls for representable values. Preserve the current value unless an explicit replacement passes server validation. Adding `novalidate` alone does not fix number-input value loss. Cover unchanged whitespace-bearing numeric values, relative URLs, and an unrelated-field save with an actual browser submission.

### CR-02: Artist ordering AJAX bypasses the configured administration capability

**Classification:** BLOCKER  
**File:** `/Users/davidzenz/gigpress/gigpress.php:572-594`  
**Related:** `gigpress.php:706`, `scripts/gigpress-admin.js:57`  
**Provenance:** Inherited callback and AJAX registration retained in the submitted files.

**Issue:** `wp_ajax_gigpress_reorder_artists` reaches this callback for any authenticated account. The callback performs the database UPDATE without `current_user_can`, a nonce, or the database-readiness guard used by the other administration writes. A subscriber can POST `action=gigpress_reorder_artists&artist[]=<existing-id>` to `admin-ajax.php` and change ordering despite being excluded from the GigPress menu. Missing nonce validation also allows a cross-origin form to cause the write through an authenticated victim. The unrestricted CASE UPDATE has neither WHERE nor ELSE: submitting a subset also clears the order of omitted artists.

**Fix:** Require the configured GigPress capability, verify an action-specific AJAX nonce, and require database readiness before writing. Send that nonce with a POST from the sortable client. Validate a nonempty array of existing artist IDs, then constrain the update to those IDs and preserve omitted rows. Return a structured error for invalid requests or failed writes.

### CR-03: Feed discovery title permits stored script injection

**Classification:** BLOCKER  
**File:** `/Users/davidzenz/gigpress/gigpress.php:167`  
**Related:** `gigpress.php:482`, `gigpress.php:515-519`  
**Provenance:** Inherited unescaped public sink; the new settings callback continues to accept the payload.

**Issue:** `rss_title` is interpolated directly into the public page's `<link title="...">` attribute. The settings callback rejects only a NUL byte for this text field, so a new title of `"><script>alert(1)</script>` is accepted. With `rss_head` enabled, visiting a public page executes the injected script. This matters even when settings access requires `manage_options`: WordPress explicitly denies `unfiltered_html` to non-super-admin site administrators on multisite, and also when `DISALLOW_UNFILTERED_HTML` is enabled. See the official [capability mapping](https://developer.wordpress.org/reference/functions/map_meta_cap/).

**Fix:** Escape `rss_title` with `esc_attr()` at this HTML attribute sink and the feed URL with `esc_url()`. Audit other consumers of editable settings for the correct output-context escaping. Preserve untouched legacy option data in storage; preservation does not require emitting it as executable HTML.

### CR-04: CSV import notices execute raw imported markup

**Classification:** BLOCKER  
**File:** `/Users/davidzenz/gigpress/admin/handlers.php:1148`  
**Related:** `admin/handlers.php:1157`, `admin/handlers.php:1166`, `admin/handlers.php:1182`  
**Provenance:** Inherited CSV handler retained in the submitted file; CSV integration remains deferred to Phase 05.

**Issue:** The handler sanitizes separate persistence arrays but retains raw CSV `Artist`, `City`, and `Venue` values in its outcome collections. It then prints those values through `wptexturize`, which does not escape HTML. A CSV row with an Artist value such as `<img src=x onerror=alert(1)>` executes code in the importing user's administration page, including when the row is skipped. The upload-failure notice likewise emits the original uploaded filename and error text without escaping. A valid upload nonce does not make imported document contents trustworthy.

**Fix:** Apply `esc_html()` to every imported text value, filename, and upload/error message at these notice sinks. If typography is needed, escape the result of `wptexturize()`. Treat formatted date output as text too. Add an import-notice case with hostile values in each outcome path when CSV work is undertaken.

### CR-05: Tour conversion deletes source data after failed writes

**Classification:** BLOCKER  
**File:** `/Users/davidzenz/gigpress/admin/handlers.php:1223-1227`  
**Provenance:** Inherited tour-to-artist conversion retained in the submitted file.

**Issue:** Each iteration unconditionally inserts an artist, rewrites every linked show's artist/tour identity using `insert_id`, and deletes the original tour. If the INSERT fails, linked shows can be reassigned to an invalid artist ID and the tour is still deleted. If the UPDATE fails, the tour is still deleted while shows retain its now-dangling ID. The final success condition examines only the last tour's results, so a later successful iteration can also hide an earlier failure. This is destructive loss of existing tour relationships and recovery information.

**Fix:** Stop before reassignment unless the artist write succeeds and its persisted identity is verified. Delete a tour only after all its intended show changes are verified. Use a transaction where supported, or retain enough original state for a retry and compensate incomplete writes. Accumulate outcomes across all tours and report partial failures; do not infer overall success from the final iteration.

### CR-06: Required new-entry fields can become empty after validation

**Classification:** BLOCKER  
**File:** `/Users/davidzenz/gigpress/admin/handlers.php:99`  
**Related:** `admin/handlers.php:136-148`  
**Provenance:** Retained validation defect in the rewritten entry flow.

**Issue:** Required artist/tour names and venue name/city are checked for nonempty raw text, then separately normalized with `sanitize_text_field()` before INSERT. A value such as `<b></b>` passes the raw required check but becomes `''` because the sanitizer [strips tags](https://developer.wordpress.org/reference/functions/sanitize_text_field/). The schema permits empty strings, so creation and the show write can succeed with a blank required entity name or city. HTML `required` controls do not prevent this counterexample because the submitted raw string is nonempty.

**Fix:** Normalize required text into the values intended for persistence before deciding whether it is empty, while keeping the separate original raw values for recovery rendering. Reject any normalized empty required field before the first entity/post/show write. Reuse those already-validated normalized values for INSERT rather than normalizing again afterward.

### CR-07: CSV import converts every show status into zero

**Classification:** BLOCKER  
**File:** `/Users/davidzenz/gigpress/admin/handlers.php:1116`  
**Related:** `admin/handlers.php:1107`, `admin/handlers.php:1118`, `admin/handlers.php:476`  
**Provenance:** Inherited CSV insertion format retained in the submitted file.

**Issue:** The fourteenth member of `$new_show` is the textual `show_status`, but the fourteenth member of the `$wpdb->insert` format array is `%d`. Consequently normal `active`, `cancelled`, and `soldout` values—including the default `active`—are converted to integer zero and stored as `'0'`. Cancellation/sold-out semantics disappear, and the rewritten trash handler explicitly rejects the resulting unsupported status at line 476. This is a deterministic field/format mismatch rather than a speculative malformed-file case.

**Fix:** Change the `show_status` format to `%s` and validate the imported status against its supported domain before insertion. Prefer formats keyed to field names to prevent positional drift. Handle previously imported `'0'` rows through an explicit repair decision, since their original textual status cannot be recovered from that value alone.

### Warnings

### WR-01: Recovery escaping checks do not bind values to their fields

**Classification:** WARNING  
**File:** `/Users/davidzenz/gigpress/tests/compat/administration-entry.php:197-198`  
**Provenance:** Introduced test reliability defect.

**Issue:** Each `escaped_<field>` assertion independently searches the whole document for the field's name and for the same escaped hostile value. Because many inputs share that value, a field can be blank, corrupted, or unescaped and its assertion still passes as long as another input contains the expected escaped string. The raw-state assertions verify server state, not the value rendered in the named control, so they do not close this gap.

**Fix:** Locate each control by its `name` and compare that specific control's value to the expected recovered value. Check its escaped source representation where needed. Use different hostile strings per field to avoid accidental matches and ensure a single-field rendering regression fails the corresponding assertion.

### WR-02: Failed browser cleanup removes its own recovery metadata

**Classification:** WARNING  
**File:** `/Users/davidzenz/gigpress/tests/compat/run.sh:1032-1041`  
**Related:** `tests/compat/run.sh:1000-1018`  
**Provenance:** Introduced fixture recovery defect.

**Issue:** `browser_cleanup` sets a failure status when Compose teardown fails or project services/volumes remain, but still unconditionally deletes `$BROWSER_DIR`, including `session.json` and `cleanup.log`. The documented status/stop commands require that private session file, so a failed stop destroys the information needed to retry the ownership-checked cleanup and inspect its failure. A transient Docker/Compose failure is sufficient to reach this path.

**Fix:** Remove the private session directory only after cleanup is confirmed successful. On failure retain its restrictive permissions, session metadata, and log, print the session path and retry command, and return failure. Allow recovery of the remaining owned resources without requiring unrelated manual project deletion.

---

_Reviewed: 2026-10-05T06:40:56Z_  
_Reviewer: the agent (gsd-code-reviewer)_  
_Depth: standard_
