## ADDED Requirements

### Requirement: Manage Schedule entry point on the PRC calendar
The PRC Calendar SHALL display a "+" icon that shows the tooltip "Manage Schedule" on hover and opens the Manage Schedule modal when clicked, visible only to users holding the Manage PRC Availability permission.

#### Scenario: Plus icon visible with permission
- **WHEN** a user holding the Manage PRC Availability permission views the PRC Calendar
- **THEN** a "+" icon is visible on the calendar

#### Scenario: Plus icon hidden without permission
- **WHEN** a user without the Manage PRC Availability permission views the PRC Calendar
- **THEN** no "+" icon or Manage Schedule entry point is visible

#### Scenario: Tooltip on hover
- **WHEN** a user hovers over the "+" icon
- **THEN** a tooltip reading "Manage Schedule" is displayed

#### Scenario: Clicking the icon opens the modal
- **WHEN** a user clicks the "+" icon
- **THEN** the Manage Schedule modal opens showing the Branch field with no branch selected

#### Scenario: Closing without saving makes no changes
- **WHEN** a user opens the Manage Schedule modal and closes it without clicking Save
- **THEN** no changes are persisted and the calendar is unchanged

### Requirement: Branch selection with no default
The Manage Schedule modal SHALL present a Branch dropdown listing all active facilities, with no branch pre-selected. The Blocked Availability section SHALL remain hidden until a branch is selected.

#### Scenario: No branch selected on open
- **WHEN** the Manage Schedule modal is opened
- **THEN** the Branch dropdown shows no selection and the Blocked Availability section is not shown

#### Scenario: Selecting a branch loads its data
- **WHEN** a branch is selected from the dropdown
- **THEN** that branch's saved Blocked Availability entries are loaded and the Blocked Availability section is shown

#### Scenario: Switching branches reloads data
- **WHEN** a different branch is selected after one branch's data has been loaded
- **THEN** the Blocked Availability section refreshes to show the newly selected branch's entries

### Requirement: New assignable permission "Manage PRC Availability"
The system SHALL provide a new, assignable permission named "Manage PRC Availability", visible in Admin > Permissions/Role Management and applicable to a user or role. This permission SHALL gate all access to the Manage Schedule modal (viewing and saving blocked availability for any branch) — there is no unrestricted self-service tier.

#### Scenario: Permission listed in Permissions/Role Management
- **WHEN** an administrator views the available permissions in Admin > Permissions/Role Management
- **THEN** "Manage PRC Availability" is listed and can be applied to a user or role

#### Scenario: Request without permission is rejected server-side
- **WHEN** a user without the Manage PRC Availability permission calls the get or save availability endpoint directly
- **THEN** the request is rejected with a 403 response

### Requirement: Blocked Availability captures repeatable one-off entries per branch
The Manage Schedule modal SHALL provide a Blocked Availability section where a branch's one-off unavailable dates/times can be captured, each with a date, start time, end time, and free-text Notes. An "Add More" action SHALL add an additional blank entry. Existing entries SHALL be editable and removable.

#### Scenario: Entry fields available
- **WHEN** a branch is selected in the Manage Schedule modal
- **THEN** the Blocked Availability section allows selecting a date and a start/end time and entering free-text Notes for an entry

#### Scenario: Add More adds a row
- **WHEN** one blocked availability entry has been entered and "Add More" is clicked
- **THEN** an additional blank blocked availability row (date/time + Notes) is added

#### Scenario: Multiple entries persist on save
- **WHEN** multiple blocked availability entries have been entered and Save is clicked
- **THEN** all entries are persisted for the selected branch

#### Scenario: Removing an entry deletes it
- **WHEN** an existing blocked availability entry is removed and Save is clicked
- **THEN** the entry is deleted and its card no longer appears on the calendar

#### Scenario: Invalid blocked entry is rejected
- **WHEN** a blocked availability entry is missing its date, has a past date, or has an End time earlier than or equal to its Start time, and Save is clicked
- **THEN** a validation error is displayed and the record is not saved

### Requirement: Blocked availability renders as a red card on the PRC calendar
When a blocked availability entry is saved, a card SHALL render on the PRC Calendar at the blocked date/time, in red, labeled "BLOCKED" (with "- [Note Text]" appended when a note was entered), whenever the calendar's branch filter matches that entry's branch. The card SHALL be display-only and SHALL NOT remove or alter any PRC appointment already scheduled in that time slot.

#### Scenario: Card renders with note text
- **WHEN** a blocked availability entry with Notes is saved and the calendar's branch filter is set to that branch
- **THEN** a red card appears at that date/time reading "BLOCKED - [Note Text]"

#### Scenario: Card renders without note text
- **WHEN** a blocked availability entry with no Notes is saved and the calendar's branch filter is set to that branch
- **THEN** a red card still appears reading "BLOCKED"

#### Scenario: Card hidden when branch filter does not match
- **WHEN** the calendar's branch filter is unset or set to a different branch than the blocked entry's branch
- **THEN** that entry's red card is not shown

#### Scenario: Existing appointment is unaffected
- **WHEN** a PRC appointment is already scheduled at a time that is later blocked
- **THEN** the existing appointment still shows in the same time block, in addition to the BLOCKED card

### Requirement: Blocked Availability is persisted per branch
The system SHALL store Blocked Availability entries in the database, retained per branch (`aoc_facility_id`), independent of the calendar the Manage Schedule modal was opened from.

#### Scenario: Existing data displayed on reopen
- **WHEN** a user reopens the Manage Schedule modal and selects a branch with previously saved blocked entries
- **THEN** those existing entries are displayed

#### Scenario: Data persists across sessions
- **WHEN** availability is saved for a branch and the Manage Schedule modal is reopened in a new session
- **THEN** the saved Blocked Availability entries for that branch are retrieved from the database and displayed
