# Phase 1: Compatibility Baseline and Menu Diagnosis - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-10-03
**Phase:** 1-Compatibility Baseline and Menu Diagnosis
**Areas discussed:** Admin menu placement, Below-minimum runtime behavior

---

## Admin menu placement

| Option | Description | Selected |
|--------|-------------|----------|
| Preserve current position and separator | Keep GigPress after Comments with a separator when compatible. | ✓ |
| Use standard menu order | Allow GigPress to move if custom ordering conflicts with WordPress or another plugin. | ✓ |

**User's choice:** Preserve the current position and separator where compatible; fall back to WordPress's standard menu order if custom ordering conflicts.
**Notes:** The fallback prioritizes a warning-free menu that continues to appear.

---

## Below-minimum runtime behavior

| Option | Description | Selected |
|--------|-------------|----------|
| Clear notice and stop incompatible code | On an already-installed site below PHP 8.3, show a compatibility notice and prevent incompatible GigPress code from running. | ✓ |
| Metadata only | Declare the minimum without a custom runtime guard. | |

**User's choice:** Show a clear admin notice and stop incompatible code from running.

### Active-state behavior

| Option | Description | Selected |
|--------|-------------|----------|
| Remain active but inert | Skip normal GigPress code while leaving the plugin active and registering only the compatibility notice. | ✓ |
| Change active state | Remove GigPress from WordPress's active-plugin state below PHP 8.3. | |

**User's choice:** Keep GigPress active but inert below PHP 8.3. The early guard runs before modules and normal hooks/functions, registers only the capability-scoped compatibility notice, and returns.

### Notice audience

| Option | Description | Selected |
|--------|-------------|----------|
| Plugin-managing administrators | Show the notice to administrators who can manage plugins. | ✓ |
| All WordPress administrators | Show the notice to every user with WordPress admin access. | |

**User's choice:** Show the notice to administrators who can manage plugins.

### Notice lifetime

| Option | Description | Selected |
|--------|-------------|----------|
| Persistent until compatible runtime | Keep showing the notice until PHP 8.3 or later is detected. | ✓ |
| Permanently dismissible | Allow an administrator to dismiss it permanently. | |

**User's choice:** Keep the notice visible until PHP 8.3 or later is detected.

**Notes:** The notice repeats on later authorized admin requests while PHP remains below 8.3 and disappears automatically on PHP 8.3+. New activation remains blocked by `Requires PHP: 8.3`. PHP 8.2 is diagnostic context for the reported WordPress 7.1.2 warning, not a support target.

---

## the agent's Discretion

No explicit implementation choices were delegated. Research and planning will determine the safe technical mechanism.

## Deferred Ideas

None.
