# Final-source public browser fixture

This owned WordPress fixture is prepared for browser and calendar-client observation after the final public fixture and production source edits. It is not acceptance evidence by itself. HTTP assertions and the nine-case matrix are supporting evidence only.

Start it from the repository root after rebuilding `.planning/phases/04-public-publishing/04-PUBLIC-MATRIX.md`:

```sh
rtk proxy bash tests/compat/run.sh public-fixture --action start --wp 7.1.2 --php 8.3 --case all
```

The command reports a loopback-only `url`, page URLs, private `session` path, WordPress/PHP/image identities, source revision and source fingerprint. The session file is mode `0600` in a mode `0700` directory. Keep it local; it contains Compose credentials and ownership metadata. The WordPress site is public inside the loopback fixture and does not require a login.

Open the URLs returned under `pages`:

| Page key | What it contains |
|---|---|
| `listing` | Migrated show 109 plus supplemental shows 801–803, grouped main listing, visible details/calendar actions and subscription links. This is also the site front page. |
| `compact` | Artist-filtered listing, compact widget and related-show display. |
| `child` | Complete, unmarked child-theme override set. |
| `parent` | Complete, unmarked parent-theme override set. |
| `content` | Complete, unmarked `wp-content/gigpress-templates` override set. |
| `complete` | Complete child override with explicit bundled-layout opt-in. |
| `mixed` | Child start, parent body and `wp-content` end partials, with no bundled-layout opt-in. |

## Required observations

Record the actual browser name/version, operating system, session path, source revision/fingerprint, WordPress version, PHP version and image ID with the results in `.planning/phases/04-public-publishing/04-BROWSER.md`. Preserve screenshots or other direct evidence locally and reference them from that record. Record failed or unavailable items as blocked; do not infer a pass from the automated checks.

1. **Main listing at exactly 320 CSS pixels.** Set the browser viewport to exactly 320 CSS pixels wide. Record viewport width, `document.documentElement.clientWidth/scrollWidth`, `document.body.clientWidth/scrollWidth`, and the listing bounds from `getBoundingClientRect()`. Keep a screenshot. Confirm there is no page-level horizontal scrolling and the entire date, artist/group context, city, venue, detail text, status, ticket link, both calendar links and subscription group are visible and readable. Check the long supplemental venue, address and note text, and the no-time, midnight and multi-day examples.
2. **Wide listing.** At a wide viewport, confirm the existing table presentation, group headings, show order, labels, details, ticket destination and calendar-link placement.
3. **Theme inheritance and compact surfaces.** Inspect computed font family, link color and listing width on `listing` and `compact`. Confirm typography/link color inherit from the active theme and the plugin adds no maximum width. Confirm widget and related-show rows remain compact and wrap long text.
4. **Override ownership.** Compare `child`, `parent`, and `content`: each complete but unmarked owner set remains owner-controlled. `complete` has the explicit opt-in and may receive bundled responsive treatment. `mixed` combines partials from multiple locations and remains unmarked. Record the pages and the visible marker/layout behavior.
5. **Keyboard and actual disabled-JavaScript behavior.** Disable JavaScript in the browser itself, reload `listing`, and record the browser setting. Use Tab to reach and Enter to activate the ticket, “Add to Google Calendar”, “Download iCalendar”, RSS and iCalendar subscription links. Confirm the links are present, visible, keyboard reachable and have their expected destinations without executing page scripts.
6. **Calendar-client import.** Download the unfiltered iCalendar feed from `listing` and open that file in an available calendar client. Confirm supplemental show 801 is an all-day multi-day event spanning May 10–12, 2031; show 802 is an event at actual midnight rather than an unspecified-time event; and no-time show 801 has no invented clock time. Record the client/version, imported event details and any limitations.

The fixture includes no-time show 801 (`2031-05-10` through `2031-05-12`), actual-midnight show 802 (`2031-05-14 00:00`), and cancelled show 803. Do not edit fixture or production source during these observations. A fingerprinted change requires a new matrix/evidence build and a replacement fixture before repeating all observations.

The owned session can be inspected with `rtk proxy bash tests/compat/run.sh public-fixture --action status --session <private-session-path>` and its anonymous HTTP assertions can be repeated with `... --action check --session <private-session-path> --case all`. Cleanup belongs to the post-approval Task 04-05-03; do not treat stopping the fixture as a substitute for recording the browser and calendar-client observations.
