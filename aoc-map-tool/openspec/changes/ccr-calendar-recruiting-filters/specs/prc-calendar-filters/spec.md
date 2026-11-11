## ADDED Requirements

### Requirement: State filter
The Recruiting PRC Calendar filter bar SHALL display a State dropdown populated with the same active state list used elsewhere in the CCR/PRC surface (`CommonDatatableFilters` state source). No state SHALL be selected by default.

#### Scenario: State dropdown is populated
- **WHEN** the calendar filter bar renders
- **THEN** the State dropdown lists the same set of states as the Clinical Care Manager dashboard's Patient Review Calls bucket State filter
- **AND** no state is pre-selected

### Requirement: Branch filter scoped to selected State
The filter bar SHALL display a Branch dropdown that becomes populated only after a State is selected, listing only branches belonging to that State.

#### Scenario: Branch options follow State selection
- **WHEN** a user selects a State
- **THEN** the Branch dropdown becomes available
- **AND** it lists only branches belonging to the selected State

#### Scenario: No state selected yet
- **WHEN** no State has been selected
- **THEN** the Branch dropdown has no branch options to select

### Requirement: Searchable Assignee filter
The filter bar SHALL display a searchable Assignee field whose selectable population includes users holding the Clinical Care Manager or Clinical Quality Nurse role. Each PRC record SHALL resolve to exactly one assignee, using the same single-value resolution the Clinical Care Manager dashboard's Patient Review Calls bucket uses for its own "CCM" display column: the Recruiter Tracker's `assigned_ccr` if set, otherwise (when the `assign_ccm_to_coverage_tracker` flag is on) the schedule's own `ccm_id`, otherwise the client's `ccm_id`. The Assignee filter matches against this single resolved value, not an independent OR across multiple columns — a record cannot simultaneously "belong" to two different assignees.

#### Scenario: Assignee field is searchable
- **WHEN** a user types into the Assignee field
- **THEN** matching Clinical Care Manager and Clinical Quality Nurse users are offered as selectable options

#### Scenario: PRC assigned via ccm_id is matched
- **WHEN** a PRC record's resolved assignee (per the resolution order above) is set only through a `ccm_id` value (not `assigned_ccr`) and the calendar's Assignee filter is set to that user
- **THEN** the PRC record is displayed, consistent with how it appears in the Clinical Care Manager dashboard's Patient Review Calls bucket for that same user

#### Scenario: assigned_ccr takes priority over ccm_id
- **WHEN** a PRC record's Recruiter Tracker has `assigned_ccr` set to User A and its client's `ccm_id` is set to a different User B
- **THEN** the record resolves to User A as its assignee, and only appears when the calendar is filtered to User A, not User B

### Requirement: Assignee badge on calendar events
Each calendar event SHALL display a small badge showing the name of its resolved assignee (per the resolution order in the Searchable Assignee filter requirement), so the responsible CCM/CQN remains visible even when the calendar is filtered by State or Branch alone.

#### Scenario: Event shows assignee badge
- **WHEN** a PRC event with a resolved assignee is displayed on the calendar
- **THEN** the event shows a badge with that assignee's name

#### Scenario: Event has no resolvable assignee
- **WHEN** a PRC event has no `assigned_ccr` and no resolvable `ccm_id`
- **THEN** no assignee badge is displayed for that event

### Requirement: Role-based Assignee default
When the logged-in user holds the Clinical Care Manager or Clinical Quality Nurse role, the Assignee filter SHALL default to that user on calendar load and the calendar SHALL display that user's Training-shift PRC records immediately. For all other logged-in users, the Assignee filter SHALL remain empty on load.

#### Scenario: Clinical Care Manager opens the calendar
- **WHEN** a user with the Clinical Care Manager role loads the calendar
- **THEN** the Assignee filter is pre-populated with that user
- **AND** the calendar displays that user's Training-shift PRC records without further action

#### Scenario: Clinical Quality Nurse opens the calendar
- **WHEN** a user with the Clinical Quality Nurse role loads the calendar
- **THEN** the Assignee filter is pre-populated with that user
- **AND** the calendar displays that user's Training-shift PRC records without further action

#### Scenario: User without either role opens the calendar
- **WHEN** a user who holds neither the Clinical Care Manager nor the Clinical Quality Nurse role loads the calendar
- **THEN** the Assignee filter is empty
- **AND** no calendar records are displayed until the user applies at least one filter

#### Scenario: Defaulted assignee has no assigned records
- **WHEN** a Clinical Care Manager or Clinical Quality Nurse is auto-selected as Assignee but has no assigned Training-shift PRC records
- **THEN** the calendar displays no records for that user
- **AND** the Assignee filter still shows that user as selected

### Requirement: Calendar title reflects selected State
The calendar header SHALL display the currently selected State's name, and SHALL update immediately when the State filter changes.

#### Scenario: Title updates on State change
- **WHEN** a user changes the selected State
- **THEN** the calendar header updates to display the newly selected State's name without a full page reload

### Requirement: Filters are independent and combine with AND
State, Branch, and Assignee filters SHALL each be applicable independently of the others. When more than one filter has a value, the calendar SHALL display only records matching all of the applied filters.

#### Scenario: Single filter applied
- **WHEN** only the State filter has a value
- **THEN** the calendar displays Training-shift PRC records for that State regardless of Assignee or Branch

#### Scenario: Multiple filters applied
- **WHEN** State, Branch, and Assignee all have values
- **THEN** the calendar displays only Training-shift PRC records matching all three

### Requirement: No records without at least one applied filter
The calendar SHALL display no PRC records when neither State nor Assignee has a value, regardless of how the empty state was reached (role-based default not applying, or a user manually clearing all filters). (Branch alone cannot occur without State also being set, since Branch options are only populated after a State is chosen.)

#### Scenario: No filters set
- **WHEN** the calendar loads with no State, Branch, or Assignee value present (e.g. for a user without a role-based default)
- **THEN** no PRC records are displayed

### Requirement: Empty-filter guidance message
When neither State nor Assignee has a value, the calendar SHALL NOT render the weekly grid at all, and SHALL instead display a message instructing the user to select a State or Assignee, in place of the grid.

#### Scenario: Guidance shown on load with no default
- **WHEN** the calendar loads and no State or Assignee value is present (e.g. for a user without a role-based Assignee default)
- **THEN** the weekly grid is not rendered
- **AND** a message instructing the user to select a State or Assignee from the filter bar is displayed in its place

#### Scenario: Guidance shown after clearing filters
- **WHEN** a user who previously had a State or Assignee selected clears it, leaving both State and Assignee empty
- **THEN** the weekly grid is replaced by the same guidance message

#### Scenario: Grid shown once a qualifying filter is set
- **WHEN** the user selects a State or an Assignee
- **THEN** the guidance message is replaced by the weekly grid reflecting the applied filters

### Requirement: Training-shift PRCs only
The calendar SHALL display only PRC records whose shift type is Training (`recruiting_schedule.coverage_id` is null or 0). PRC records with any other shift type (e.g. Coverage) SHALL NOT appear on this calendar.

#### Scenario: Coverage-shift PRC excluded
- **WHEN** a PRC record has a non-zero `coverage_id` (Coverage shift type)
- **THEN** it does not appear on the Recruiting PRC Calendar, even if it matches all applied filters

#### Scenario: Training-shift PRC included
- **WHEN** a PRC record has a null or zero `coverage_id` (Training shift type) and matches all applied filters
- **THEN** it appears on the calendar

### Requirement: PRC positioned by its own scheduled date/time
Each PRC SHALL appear on the calendar using its own scheduled date and time (`recruiting_schedule.appointment_date`/`appointment_time`), never the associated clinical next step's date/time.

#### Scenario: PRC has an associated next step
- **WHEN** a PRC record is linked to a Recruiter Tracker with a proposed next-step date/time
- **THEN** the PRC is displayed on the calendar at its own scheduled date/time, not the next step's date/time

### Requirement: Multiple PRCs in the same time slot
The calendar SHALL display all PRCs scheduled for the same date and time within the corresponding time slot, without hiding or truncating any of them.

#### Scenario: Two PRCs share a time slot
- **WHEN** two or more Training-shift PRC records matching the applied filters share the same scheduled date and time
- **THEN** all of them are visible within that calendar time slot

### Requirement: Time-blocking control hidden
The calendar SHALL NOT display the "+" control previously used to create time-blocking entries.

#### Scenario: Calendar page loads
- **WHEN** the Recruiting PRC Calendar page loads
- **THEN** the "+" time-blocking control is not visible anywhere on the page
