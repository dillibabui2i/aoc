## Context

The PRC availability CRUD from `manage-prc-availability` (legacy Blade + jQuery modal, `CCRAvailabilityController`, `CCRAvailabilityRepository`, `ccr_availability_block`) is extended rather than duplicated. Capacity is shared across the branch (`mssql_facilities.id` = `aoc_facility_id`) and isn't tied to assignees.

## Decisions

- **Separate table `ccr_availability_capacity`**, named after `ccr_availability_block`. One row per segment, with `day_of_week` stored as ISO-8601 (1 = Monday … 7 = Sunday, PHP `date('N')`), modelled by `App\Enums\WeekDayEnum`.
- **One form, one save.** Matching the mockup, the modal submits blocks and segments together. `saveForFacility()` full-replaces both in a single transaction (last write wins, same as blocks).
- **Whole-hour segments.** Capacity means "PRCs starting within a clock hour", so segments are whole hours. Each clock hour then belongs to exactly one segment, which keeps enforcement unambiguous.
- **End is exclusive.** 8–12 and 12–5 are adjacent, not overlapping. The max End is 5:00pm. The PRC pickers end at 4:45pm (changed in the enforcement change).
- **No enforcement here.** Scheduling stays unchanged until `enforce-prc-branch-capacity` ships. Under that change, a branch with no segments has no PRC slots, so branches must configure capacity before it deploys.

## Risks / Trade-offs

- **Concurrent saves to the same branch**: last write wins. Accepted, same as blocks.
- **Go-live dependency**: unconfigured branches will be fully closed once enforcement ships. Mitigation: this change deploys first.

## Migration Plan

1. Deploy, then run `php artisan migrate` (creates `ccr_availability_capacity`).
2. Rollback: the migration's `down()` drops the table.
