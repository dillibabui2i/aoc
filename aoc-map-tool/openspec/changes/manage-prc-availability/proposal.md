## Why

Schedulers on the Recruiting and Coverage PRC calendars have no way to record when a branch is unavailable to conduct PRCs (Patient Review Calls), so they have no data to work from when placing PRC appointments. This change adds self-service blocked-availability management **per branch** (facility) and a permission for managing it. It does not change any scheduling/enforcement behavior — that is a separate, follow-on ticket. Shipping this alone lets users start populating blocked times ahead of enforcement.

Note: this proposal originally modeled availability per-**user** (CCM/Clinical Quality Nurse), with a weekly recurring schedule plus one-off blocks. Business rescoped this before either ticket reached production: availability is now scoped to the **branch/facility** a patient belongs to (not the individual CCM), and only one-off blocked times are captured — there is no recurring weekly schedule.

## What Changes

- Add a "+" icon with a "Manage Schedule" tooltip to the PRC Calendar, opening a "Manage Schedule" modal.
- The modal has a Branch dropdown (all `mssql_facilities`, via the existing `ConstantHelper::getFacilities()` helper) with no default selection — the user must pick a branch to see or edit its blocked times.
- Add a Blocked Availability section (repeatable date + start/end time + free-text notes entries, addable/editable/removable) scoped to the selected branch.
- Persist Blocked Availability **once per branch**, not per user and not per calendar.
- Render saved blocked-availability entries as red "BLOCKED" cards on the PRC calendar week grid: when a **branch** filter is active, only that branch's blocks show; when only a **state** filter is active (no branch narrowing it), all blocks for every branch in that state show. Display-only, layered alongside (not replacing) existing scheduled PRC appointments.
- Blocked-availability cards are double-click editable/deletable directly on the calendar (no need to reopen the Manage Schedule modal), gated behind the same "Manage PRC Availability" permission: double-click opens an inline edit panel pre-populated with date/start/end/notes; Save updates the block and its card immediately; "Delete Block" asks for confirmation (reusing the app's existing `modalConfirm()` pattern, e.g. the "Add New Intake" close-without-saving prompt) before removing it, at which point the time becomes selectable again for PRC scheduling.
- Add a new assignable permission, "Manage PRC Availability", gating the entire Manage Schedule modal and the calendar's inline block editor (view + edit, any branch) — there is no longer a per-assignee "self vs. others" distinction, since availability isn't tied to an individual.
- Add validation: for each blocked entry, End time must be later than Start time and the date cannot be in the past.
- Blocked-availability time pickers are restricted to 8:00am–5:00pm (narrowed from the original 6:00am–9:00pm range).

## Capabilities

### New Capabilities
- `prc-availability-management`: Self-service one-off blocked-availability entries per branch; "Manage Schedule" modal entry point on the PRC calendar; permission-gated access; storage of these records; red BLOCKED card rendering on the calendar grid.

### Modified Capabilities
(none — `prc-scheduling` and `prc-terminology` govern PRC appointment scheduling and naming conventions respectively; this change adds a new, additive capability alongside them without altering their requirements)

## Impact

- **DB**: single table `ccr_availability_block` (per-branch one-off blocks, keyed by `aoc_facility_id`). No `ccr_availability_schedule` table (dropped — no weekly recurring schedule in the final scope).
- **New permission**: `Manage PRC Availability`, in `config/permissions.php` under the existing `CCR Calendar` group; requires `php artisan process:permissions` post-deploy to sync into the `permissions` table and become assignable in Admin > Permissions/Role Management.
- **Backend code**: `CCRAvailabilityController` (`get`/`save`, branch-scoped, plus `updateBlock`/`deleteBlock` for single-row inline edit/delete) and `CCRAvailabilityRepository` (blocks-only, full-replace-on-save keyed by `aoc_facility_id`, plus `getBlocksForStateInRange` joining `mssql_facilities.state` and single-row `updateBlock`/`deleteBlock`/`findBlock`). Two new routes/permission-related entries: `ccrAvailability.updateBlock` (PUT), `ccrAvailability.deleteBlock` (DELETE).
- **Modified views**: `resources/views/ccr/calendar.blade.php` (single merged PRC calendar view, per the later AOC-5373 "merge calendars" change — supersedes the original two-calendar plan; now also includes the block editor partial); `resources/views/ccr/weekEvents.blade.php` (red BLOCKED card rendering, keyed to branch when a branch filter is active or to state when only a state filter is active; cards carry `data-*` attributes and are double-click editable when the viewer holds the permission); `resources/views/ccr/manageSchedule.blade.php` (Branch dropdown + Blocked Availability only, no weekly schedule UI, 8am–5pm time range); new `resources/views/ccr/blockAvailabilityEditor.blade.php` (inline edit/delete panel for a single blocked-availability card).
- **No changes** to routes/schema/behavior of existing PRC scheduling, the PRC date/time pickers, or any other calendar.
