# Phase 04 browser observations

## Historical rendered-content review — PASS

On 2026-10-05, the user accepted the rendered-content/security review of the listing at the earlier fixture URL, http://127.0.0.1:32795/. That review covered escaped hostile content, emoji and feed icons, HTTPS Maps URLs, and the legacy Google Calendar endpoint. It did not cover final-source layout, disabled-JavaScript navigation, or calendar-client import.

## Final-source fixture identity

| Property | Value |
|---|---|
| Listing URL | http://127.0.0.1:32796/ |
| Session path | /private/var/folders/1l/t8mktk5j58g75tthg0q79qhw0000gn/T/gigpress-public-esbPYB/session.json |
| WordPress | 7.1.2 |
| PHP | 8.3.35 |
| Source revision | 5efc01d5d21c24fc6abb4cbf599e2ec6c9aa0c8e |
| Source fingerprint | 1a27ce0c2c8207467824eff53386500621418e90b9194d6b65ad7b862587edf5 |
| WordPress image | sha256:736761c95c327cb21602ab9175ee4dba6e779b00f3f4895536a3e1af5ac4fe4c |
| Database image | sha256:49117dcc565cf51aa57ac5fca59ab31213402ff0eae6ffc13c46a37b938f7e4b |
| Viewport browser | Codex In-app Browser; version unavailable |
| Keyboard browser | Google Chrome 154.0.8037.98, official arm64 build |
| Operating system | macOS 26.6.2 |

## Automated evidence

The source-bound public matrix passed six WordPress/PHP cells: WordPress 7.0.6 and 7.1.2 with PHP 8.3, 8.4, and 8.5. It covered all nine required cases and 798 assertions, with zero warnings, fatals, or plugin errors. The evidence report validated, and all 22 corruption checks passed. The retained final-source fixture check passed 13 assertions with all nine cases, HTTP 200 responses, and unchanged migrated snapshots.

## Listing at 320 CSS pixels — PASS

The final-source listing was shown at exactly 320 × 1000 CSS pixels and accepted by the user with “pass” on 2026-10-06.

| Measurement | Result |
|---|---:|
| window.innerWidth | 320 CSS px |
| documentElement clientWidth / scrollWidth | 320 / 320 px |
| body clientWidth / scrollWidth | 320 / 320 px |
| Main bounds | x=0, right=320, width=320 px |
| Listing table bounds | x=30, right=290, width=260 px; scrollWidth 260 px |

The table reflowed into stacked, labeled rows. The hostile values, long venue/address/note text, no-time, midnight, multi-day and cancelled examples were present. No page-level horizontal overflow was measured. The user saw the screenshot inline in the browser review and accepted it with “pass” on 2026-10-06; a local PNG export was not available.

## Wide listing — PASS

The final-source listing was shown at 1280 × 1000 CSS pixels and accepted by the user with “pass” on 2026-10-06.

- Document and body widths were both 1280 px with no page-level horizontal overflow.
- Five listing tables were 645 px wide, positioned x=317.5 to 962.5 within the Twenty Twenty-Five theme content column.
- On the long-venue row, date, city, venue, and country cells measured approximately 150.8, 122.4, 249.4, and 122.4 px. The date, city, and country remained readable; the unbroken venue wrapped within its own column.
- The long fixture row with hostile values also kept its date, city, and country columns above the new minimum widths.
- The plugin adds no maximum width. The 645 px table width comes from WordPress’s constrained-layout rule: .is-layout-constrained > :where(:not(.alignleft):not(.alignright):not(.alignfull)) uses the theme global content size.
- The final-source screenshot was shown inline and accepted by the user with “pass” on 2026-10-06; a local PNG export was not available.

## Compact surfaces and theme styling — PASS

The compact page used Manrope, sans-serif and inherited the theme link color rgb(17, 17, 17). The main content width was 1280 px with max-width none. The theme constrained its GigPress table to 645 px. The widget remained a vertical list with three wrapped rows, including long artist and venue values; the related-show section remained a compact detail list and wrapped its long content. These pages were shown inline for review, and the user accepted them with “pass” on 2026-10-06.

## Template override ownership — PASS

The child, parent, and wp-content pages each displayed the owner-start marker, used an unmarked owner table, and resolved their own body/end markers. The complete override displayed the explicit owner-start marker and carried the gigpress-layout-bundled opt-in class. The mixed page used the child start, parent body, and wp-content end and remained unmarked. These pages were shown inline for review, and the user accepted them with “pass” on 2026-10-06.

## Keyboard with JavaScript blocked — observed in Chrome 154

Chrome’s site-specific JavaScript setting blocked scripts for http://127.0.0.1:32796. The browser toolbar displayed “JavaScript was blocked on this page.”

With JavaScript blocked, Tab and Enter were used on the final-source listing:

- The ticket link received keyboard focus and Enter opened the fixture’s tickets.example.test/109 destination; that reserved test domain did not resolve.
- The Add to Google Calendar link received focus and Enter opened Google Calendar’s event page. The event was not saved.
- The Download iCalendar link received focus and Enter produced a completed 630-byte download in Chrome.
- The RSS subscription link received focus and Enter opened the XML feed.
- The iCal webcal subscription link received focus and Enter raised Chrome’s “Open Calendar?” prompt. The prompt was dismissed without creating a subscription.

The temporary JavaScript exception was removed after testing. Chrome’s JavaScript settings then showed no blocked-site entries. The 630-byte download was visible in Chrome’s download history, but its download-history label did not resolve to a file in the standard Downloads folder; a calendar-client import remains unverified.

## Calendar-client import — skipped; checkpoint remains blocked

Apple Calendar is installed, but its available calendar sources are iCloud, Google, and subscribed calendars; no isolated local calendar was present. Importing the synthetic fixture feed into one of those calendars could sync test events to the user’s account. No feed subscription or event import was created. On 2026-10-06, the user explicitly chose “Skip the import.” The plan’s required calendar-client import, including all-day multi-day, actual-midnight, and no-time confirmation, is therefore unverified and this blocking checkpoint cannot pass.

## Blocking observations

- [x] User confirmation of the final-source 320 CSS-pixel and wide listing views.
- [x] User confirmation of compact/theme and template-override views.
- [x] Keyboard focus/activation observed with JavaScript blocked for ticket, Google Calendar, iCalendar download, RSS, and webcal links; the webcal launch prompt was dismissed without subscribing.
- [ ] Calendar-client import and confirmation of no-time, actual-midnight, and all-day multi-day meanings; explicitly skipped by the user on 2026-10-06, so this required observation remains unverified.

**Checkpoint status:** blocked because the required calendar-client observation was skipped. The observed browser items above have been approved, but no overall phase approval or validation completion is recorded.
