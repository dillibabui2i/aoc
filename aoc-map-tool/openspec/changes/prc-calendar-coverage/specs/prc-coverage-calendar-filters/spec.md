## ADDED Requirements

### Requirement: Net-new calendar accessible alongside Recruiting
A "PRC Calendar – Coverage" calendar SHALL be accessible from the Calendars section (sidebar navigation), independently of and in addition to the existing "PRC Calendar – Recruiting" calendar. The calendar header SHALL display "PRC Calendar – Coverage".

#### Scenario: Sidebar link exists
- **WHEN** a permitted user views the sidebar navigation
- **THEN** a "PRC Calendar – Coverage" link is available alongside "PRC Calendar – Recruiting"

#### Scenario: Calendar page loads
- **WHEN** a user navigates to the PRC Calendar – Coverage page
- **THEN** the calendar header displays "PRC Calendar – Coverage"

### Requirement: State filter
The Coverage calendar's filter bar SHALL display a State dropdown populated with the same active state list used by the Recruiting calendar's State filter. No state SHALL be selected by default.

#### Scenario: State dropdown is populated
- **WHEN** the calendar filter bar renders
- **THEN** the State dropdown lists the same set of states as the PRC Calendar – Recruiting's State filter
- **AND** no state is pre-selected

### Requirement: Branch filter scoped to selected State
The filter bar SHALL display a Branch dropdown that becomes populated only after a State is selected, listing only branches belonging to that State.

#### Scenario: Branch options follow State selection
- **WHEN** a user selects a State
- **THEN** the Branch dropdown becomes available
- **AND** it lists only branches belonging to the selected State

### Requirement: Searchable Assignee filter
The filter bar SHALL display a searchable Assignee field whose selectable population includes users holding the Clinical Care Manager or Clinical Quality Nurse role, using the same single-value assignee resolution as the Recruiting calendar (`assigned_ccr` if set, else — behind the `assign_ccm_to_coverage_tracker` flag — the schedule's own `ccm_id`, else the client's `ccm_id`).

#### Scenario: Assignee field is searchable
- **WHEN** a user types into the Assignee field
- **THEN** matching Clinical Care Manager and Clinical Quality Nurse users are offered as selectable options

### Requirement: Role-based Assignee default
When the logged-in user holds the Clinical Care Manager or Clinical Quality Nurse role, the Assignee filter SHALL default to that user on calendar load and the calendar SHALL display that user's Coverage-shift PRC records immediately. For all other logged-in users, the Assignee filter SHALL remain empty on load.

#### Scenario: Clinical Care Manager opens the calendar
- **WHEN** a user with the Clinical Care Manager role loads the calendar
- **THEN** the Assignee filter is pre-populated with that user
- **AND** the calendar displays that user's Coverage-shift PRC records without further action

#### Scenario: Clinical Quality Nurse opens the calendar
- **WHEN** a user with the Clinical Quality Nurse role loads the calendar
- **THEN** the Assignee filter is pre-populated with that user
- **AND** the calendar displays that user's Coverage-shift PRC records without further action

#### Scenario: User without either role opens the calendar
- **WHEN** a user who holds neither role loads the calendar
- **THEN** the Assignee filter is empty
- **AND** no calendar records are displayed until the user applies at least one filter

#### Scenario: Defaulted assignee has no assigned Coverage records
- **WHEN** a Clinical Care Manager or Clinical Quality Nurse is auto-selected as Assignee but has no assigned Coverage-shift PRC records
- **THEN** the calendar displays no records for that user
- **AND** the Assignee filter still shows that user as selected

### Requirement: Calendar title reflects selected State
The calendar header SHALL display the currently selected State's name (in addition to "PRC Calendar – Coverage"), and SHALL update immediately when the State filter changes.

#### Scenario: Title updates on State change
- **WHEN** a user changes the selected State
- **THEN** the calendar header updates to display the newly selected State's name without a full page reload

### Requirement: Filters are independent and combine with AND
State, Branch, and Assignee filters SHALL each be applicable independently of the others. When more than one filter has a value, the calendar SHALL display only records matching all of the applied filters.

#### Scenario: Single filter applied
- **WHEN** only the State filter has a value
- **THEN** the calendar displays Coverage-shift PRC records for that State regardless of Assignee or Branch

#### Scenario: Multiple filters applied
- **WHEN** State, Branch, and Assignee all have values
- **THEN** the calendar displays only Coverage-shift PRC records matching all three

### Requirement: No records without at least one applied filter
The calendar SHALL display no PRC records when neither State nor Assignee has a value.

#### Scenario: No filters set
- **WHEN** the calendar loads with no State, Branch, or Assignee value present (e.g. for a user without a role-based default)
- **THEN** no PRC records are displayed

### Requirement: Empty-filter guidance message
When neither State nor Assignee has a value, the calendar SHALL NOT render the weekly grid at all, and SHALL instead display a message instructing the user to select a State or Assignee, in place of the grid.

#### Scenario: Guidance shown on load with no default
- **WHEN** the calendar loads and no State or Assignee value is present
- **THEN** the weekly grid is not rendered
- **AND** a message instructing the user to select a State or Assignee is displayed in its place

### Requirement: Coverage-shift PRCs only
The calendar SHALL display only PRC records whose shift type is Coverage (`recruiting_schedule.coverage_id` is non-zero). PRC records with any other shift type (e.g. Training) SHALL NOT appear on this calendar.

#### Scenario: Training-shift PRC excluded
- **WHEN** a PRC record has a null or zero `coverage_id` (Training shift type)
- **THEN** it does not appear on the PRC Calendar – Coverage, even if it matches all applied filters

#### Scenario: Coverage-shift PRC included
- **WHEN** a PRC record has a non-zero `coverage_id` (Coverage shift type) and matches all applied filters
- **THEN** it appears on the calendar

### Requirement: PRC positioned by its own scheduled date/time
Each PRC SHALL appear on the calendar using its own scheduled date and time (`recruiting_schedule.appointment_date`/`appointment_time`), never the associated coverage shift's date/time.

#### Scenario: PRC has an associated coverage shift
- **WHEN** a PRC record is linked to a coverage shift with its own date/time
- **THEN** the PRC is displayed on the calendar at its own scheduled date/time, not the coverage shift's date/time

### Requirement: Multiple PRCs in the same time slot
The calendar SHALL display all PRCs scheduled for the same date and time within the corresponding time slot, without hiding or truncating any of them.

#### Scenario: Two PRCs share a time slot
- **WHEN** two or more Coverage-shift PRC records matching the applied filters share the same scheduled date and time
- **THEN** all of them are visible within that calendar time slot

### Requirement: No time-blocking control
The calendar SHALL NOT include a "+" control or any time-blocking functionality.

#### Scenario: Calendar page loads
- **WHEN** the PRC Calendar – Coverage page loads
- **THEN** no "+" time-blocking control is present anywhere on the page

### Requirement: Behavioral parity with PRC Calendar – Recruiting
Filter behavior, assignee defaulting, and scheduling display logic SHALL be identical between PRC Calendar – Coverage and PRC Calendar – Recruiting, except that each displays only PRCs of its respective Shift Type.

#### Scenario: Same assignee resolution across both calendars
- **WHEN** a PRC record's assignee is resolved on both calendars for the same underlying data shape (only differing by Shift Type)
- **THEN** the resolution logic (and resulting assignee) is the same on both calendars
