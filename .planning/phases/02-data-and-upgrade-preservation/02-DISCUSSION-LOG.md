# Phase 2: Data and Upgrade Preservation - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered and the delegation of recommendations.

**Date:** 2026-10-04
**Phase:** 02-Data and Upgrade Preservation
**Areas selected:** Upgrade starting points; what preserved means; unexpected or interrupted updates; trash and restore.

## Area selection

| Option | Description | Selected |
|--------|-------------|----------|
| 1. Upgrade starting points | Which older GigPress versions or saved site states should be represented when proving preservation? | Yes |
| 2. What preserved means | Exact IDs, relationships, and setting values versus content and behavior remaining usable. | Yes |
| 3. Unexpected or interrupted updates | Site-owner behavior when an update encounters data it cannot safely handle. | Yes |
| 4. Trash and restore | Restored show/tour content and links to artists, venues, and tours. | Yes |

**User's choice:** `1-4`.

## Upgrade starting points

**Question presented:** How far back should direct upgrades preserve existing GigPress data?

| Option | Description | Selected |
|--------|-------------|----------|
| All existing upgrade paths (Recommended) | Every database version the plugin recognizes, from 1.0 through current 1.6. | Recommended choice accepted |
| Current database layout only | Cover 1.6; older installations must upgrade through an earlier GigPress release first. | No |
| A specific starting version | User names the oldest version to cover. | No |

**User's response:** "recommendations are fine".

**Notes:** Accepted the displayed recommendation and interpreted the response as delegation to use recommended policies across the four selected areas. The agent announced that interpretation before creating artifacts. This user direction superseded the default four-question interview loop. No additional individual answers were invented. The recommended coverage uses populated reproducible fixtures after inventorying historical schemas, verifies repeated loads and upgrade retries, and retains the supported runtime minimums.

## What preserved means

**Selection method:** Agent recommendation under the user's delegation; no separate question or alternatives were presented.

**Recommended policy recorded:** Preserve existing IDs, records, relationship links, saved settings, and linked WordPress posts. Necessary established legacy transformations may change storage while preserving meaning, with explicit expected results. Retain intentional empty/disabled settings and unknown keys; fill genuinely missing defaults safely. Preserve trashed records and verify populated fixtures.

## Unexpected or interrupted updates

**Selection method:** Agent recommendation under the user's delegation; no separate question or alternatives were presented.

**Recommended policy recorded:** Stop the affected failed migration, preserve data, and report actionable information to an authorized administrator. Do not mark failed migrations complete, reset stored content, downgrade an unknown newer schema, or guess associations. Research safe retries and affected mutation paths; do not assume atomic rollback of database schema operations. No new backup/restore product interface.

## Trash and restore

**Selection method:** Agent recommendation under the user's delegation; no separate question or alternatives were presented.

**Recommended policy recorded:** Preserve the identity and references of edited/trashed/restored shows; copy creates a distinct record while preserving the source. Retain established tour detachment/undo semantics while preventing incorrect associations and overwriting deliberate edits. Prevent deletion of artists/venues referenced by active or trashed shows, consistent with the existing stated no-deletion-while-in-use behavior. Characterize repeated tour deletions and intervening edits instead of extending trash-history scope.

## The agent's Discretion

The user accepted recommendations for the selected areas. Fixture design, migration checks, safe recovery mechanics, notice wording, and focused preservation repairs remain for research and planning. Context capture does not enable automatic planning or execution, and project workflow settings were not changed.

## Deferred Ideas

None were proposed by the user. Existing administration, publishing, and CSV improvement work remains in Phases 3–5.
