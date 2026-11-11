## ADDED Requirements

### Requirement: Single merged PRC Calendar
There SHALL be exactly one PRC Calendar, reachable from one navigation entry, showing PRC records regardless of whether they originated from the Recruiting flow, the Coverage flow, or the CCC "Clinical Competency Review" request flow. The separate Coverage PRC Calendar entry point and view SHALL be removed.

#### Scenario: Sidebar has one PRC Calendar entry
- **WHEN** a user views the sidebar navigation
- **THEN** there is exactly one "PRC Calendar" entry, and no separate "PRC Calendar – Coverage" entry

#### Scenario: Recruiting-originated and Coverage-originated records appear together
- **WHEN** a PRC was scheduled under the former Recruiting flow and another under the former Coverage flow, both within the same visible week
- **THEN** both appear on the same PRC Calendar view

#### Scenario: Blocked Availability card still appears at its saved time
- **WHEN** a Blocked Availability card existed on either former calendar
- **THEN** the card appears on the merged PRC Calendar at its saved date/time

#### Scenario: Manage Schedule entry point still available
- **WHEN** a user views the merged PRC Calendar
- **THEN** the "Manage Schedule" Plus entry point is present and opens the same Manage Schedule modal as before

### Requirement: Shift Type filter
The PRC Calendar SHALL provide a filter control listing Shift Types Training, Coverage, and CCC Request, supporting selection of one, multiple, or all types at once. With no selection made, all Shift Types SHALL be shown. Applying a filter SHALL only hide non-matching PRCs and Blocked Availability cards from the view; it SHALL NOT alter or delete the underlying records.

A record's Shift Type SHALL be determined as:
- **Training**: the record's tracker reference (`tracker_id`) is set.
- **Coverage**: the record's coverage reference (`coverage_id`) is set.
- **CCC Request**: neither `tracker_id` nor `coverage_id` is set, and the record's `source` is `ccc`.

#### Scenario: Filter control visible on load
- **WHEN** the PRC Calendar page loads
- **THEN** a Shift Type filter control is visible with options for Training, Coverage, and CCC Request

#### Scenario: Default state shows all Shift Types
- **WHEN** no Shift Type filter selection has been made
- **THEN** PRCs and Blocked Availability cards of all Shift Types are displayed

#### Scenario: Selecting one or more Shift Types narrows the view
- **WHEN** the user selects one or more Shift Types in the filter
- **THEN** the calendar updates to display only PRCs and Blocked Availability cards matching the selected Shift Type(s)

#### Scenario: Clearing the filter restores all Shift Types
- **WHEN** the user clears the Shift Type filter after having a selection applied
- **THEN** the calendar updates to display all Shift Types again

#### Scenario: CCC-requested record is classified as CCC Request, not Training
- **WHEN** a PRC record was created via the Clinical Competency Review request flow (`source = ccc`, no `tracker_id`, no `coverage_id`)
- **THEN** the record is classified as Shift Type "CCC Request" and is hidden when the filter excludes CCC Request, even though it has no `coverage_id`

### Requirement: Grid display restricted to 8:00 AM–5:00 PM with edge clamping
The PRC Calendar grid SHALL display only the 8:00 AM–5:00 PM time window, regardless of which Shift Type filter is applied. A PRC, Blocked Availability card, or availability window whose time falls before 8:00 AM SHALL be shown in the 8:00 AM row; one falling at or after 5:00 PM SHALL be shown in the 5:00 PM row.

#### Scenario: Grid shows only 8am-5pm rows
- **WHEN** the PRC Calendar grid renders
- **THEN** only hourly rows from 8:00 AM through 5:00 PM are shown

#### Scenario: Early time clamps into the 8am row
- **WHEN** a PRC is scheduled for 7:30 AM
- **THEN** it appears in the 8:00 AM row of the grid

#### Scenario: Late time clamps into the 5pm row
- **WHEN** a PRC or Blocked Availability entry falls at or after 5:00 PM
- **THEN** it appears in the 5:00 PM row of the grid

#### Scenario: Clamping is independent of the active Shift Type filter
- **WHEN** any combination of Shift Types is selected in the filter
- **THEN** the 8:00 AM–5:00 PM grid window and edge-clamping behavior are unchanged
