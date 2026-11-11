## 1. Database
- [x] 1.1 Migration `2026_11_02_100000_create_ccr_availability_capacity_table`
- [x] 1.2 `App\AvailabilityCapacity` model; `App\Enums\WeekDayEnum` (ISO 1–7 + `label()`)

## 2. Backend
- [x] 2.1 `CCRAvailabilityRepository::getCapacitySegments()`; `saveForFacility()` full-replaces blocks + segments in one transaction
- [x] 2.2 `CCRAvailabilityController::get()` returns `segments`; `save()` persists them
- [x] 2.3 `SaveAvailabilityRequest`:
  - required fields
  - `g:ia` time format
  - end after start
  - capacity 1–99
  - whole hours
  - 8:00am–5:00pm
  - no same-day overlap

## 3. Frontend
- [x] 3.1 Modal title + banner + Weekly Capacity Template (Mon–Sun day blocks, + Add Segment, Remove, empty-state text)
- [x] 3.2 Hourly timepickers (`step: 60`) for segments; Blocked Availability pickers unchanged (15 min)
- [x] 3.3 Client-side validation mirroring the server; Save posts `segments` with `blocks`

## 4. Verification
- [x] 4.1 Server validation exercised via tinker (valid, overlap, end ≤ start, non-hour, out of range, capacity 0, invalid day, empty)
- [ ] 4.2 Run the migration
- [ ] 4.3 Manual checks:
  - pick a branch → 7 days render
  - add/edit/remove segments → save → reopen → persisted
  - another branch is unaffected
  - blocks still save and edit as before
- [ ] 4.4 No scheduling behavior changes
