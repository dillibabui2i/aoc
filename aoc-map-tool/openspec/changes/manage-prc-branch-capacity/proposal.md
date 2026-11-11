## Why

Branches need to cap how many PRCs can be booked per clock hour. Today the only branch-level control is one-off Blocked Availability (`manage-prc-availability`). This change adds a **recurring weekly hourly capacity template** per branch, configured in the existing Manage Schedule modal. Like `manage-prc-availability`, it ships **ahead of enforcement** so branches can configure capacity before the follow-on change (`enforce-prc-branch-capacity`) starts restricting PRC times.

## What Changes

- The Manage Schedule modal (PRC Calendar "+" icon) is retitled "Manage Branch Capacity & Blocked Availability". After a branch is selected it shows a **Weekly Capacity Template** above the existing Blocked Availability section.
- The template is a Monday–Sunday grid. Each day has zero or more segments (Start, End, Capacity / hr), with "+ Add Segment" and Remove actions. A day with no segments shows "No segments added — {Day} is fully unavailable for this branch."
- Segment Start/End pickers are **whole hours only** (8:00am … 5:00pm). Capacity is configured per hour, so 15-minute segment boundaries would be confusing. Blocked Availability keeps its 15-minute pickers.
- Save does a full replace of the selected branch's blocks **and** segments in one transaction.
- Validation, on both server and client:
  - start/end are required and on the hour
  - start/end fall within 8:00am–5:00pm
  - end is after start
  - capacity is a whole number from 1 to 99
  - segments on the same day don't overlap (end exclusive)
- **No effect on scheduling** in this change.

## Capabilities

### New Capabilities
- `prc-branch-capacity-management`: per-branch recurring weekly hourly PRC capacity, configured in the Manage Schedule modal.

## Impact

- **DB**: new table `ccr_availability_capacity` (`aoc_facility_id`, `day_of_week` ISO 1–7, `start_time`, `end_time`, `capacity_per_hour`, audit columns). Indexed on (`aoc_facility_id`, `day_of_week`).
- **Backend**:
  - new `App\AvailabilityCapacity` model and `App\Enums\WeekDayEnum`
  - `CCRAvailabilityRepository` gets `getCapacitySegments()`, and `saveForFacility()` now also saves segments
  - `CCRAvailabilityController::get()` returns `segments`; `save()` persists them
  - `SaveAvailabilityRequest` gets the segment rules
- **Frontend**: `resources/views/ccr/manageSchedule.blade.php`.
- **No** route or permission changes. The existing "Manage PRC Availability" permission covers it, so `process:permissions` doesn't need to run.
