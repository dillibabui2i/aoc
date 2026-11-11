## MODIFIED Requirements

### Requirement: Render the tracker header, Outcome control, and tabs
The Open-state modal body SHALL show a header with patient information, assignee, tracker ID, and basic tracker info; an "Outcome" dropdown whose options depend on the tracker's current status, driving real status transitions through the full lifecycle; a tab structure that depends on whether the tracker has ever been "In Progress"; and, in the header, a Notes icon available whenever the tracker's status is neither "Closed" nor "Completed".

#### Scenario: Notes icon available in the tracker header
- **WHEN** a hospitalization tracker's status is neither "Closed" nor "Completed"
- **AND** the tracker header is displayed
- **THEN** the Notes icon SHALL be displayed and clickable

#### Scenario: Notes icon hidden once Closed or Completed
- **WHEN** a hospitalization tracker's status is "Closed" or "Completed"
- **THEN** the Notes icon SHALL NOT be displayed in the tracker header

## ADDED Requirements

### Requirement: Restrict notes functionality by permission
The Notes icon (in the bucket's Action column and in the tracker header) SHALL only be displayed to a user with the "Intensive Case Management" permission.

#### Scenario: User lacks the Intensive Case Management permission
- **WHEN** the user does not have the "Intensive Case Management" permission
- **THEN** the Notes icon SHALL NOT be displayed in the bucket's Action column or in the tracker header

### Requirement: Display the Notes icon in the hospitalization bucket
A hospitalization tracker displayed in a hospitalization bucket SHALL show a Notes icon in its Action column whenever its status is neither "Closed" nor "Completed".

#### Scenario: Notes icon shown for an eligible tracker row
- **WHEN** a hospitalization tracker row's status is neither "Closed" nor "Completed"
- **THEN** the Notes icon SHALL be displayed in that row's Action column

#### Scenario: Notes icon hidden for a Closed or Completed tracker row
- **WHEN** a hospitalization tracker row's status is "Closed" or "Completed"
- **THEN** the Notes icon SHALL NOT be displayed in that row's Action column

### Requirement: Open the Create Notes modal
Clicking the Notes icon, from either the bucket's Action column or the tracker header, SHALL open the "Create Notes" modal for that tracker. The modal SHALL contain a single Notes field and no user-tagging control.

#### Scenario: Opening from the bucket
- **WHEN** the user clicks the Notes icon in the bucket's Action column for an eligible tracker
- **THEN** the "Create Notes" modal SHALL open for that tracker

#### Scenario: Opening from the tracker header
- **WHEN** the user clicks the Notes icon in the tracker header of an eligible tracker
- **THEN** the "Create Notes" modal SHALL open for that tracker

### Requirement: Validate and submit a note
Submitting the "Create Notes" modal with a blank Notes field SHALL be prevented, and the Notes field SHALL be shown with a red border. Submitting with content SHALL create the note, close the modal, and display the note in the tracker's right-side panel.

#### Scenario: Submitting a blank note
- **WHEN** the "Create Notes" modal is open
- **AND** the user submits without entering any content
- **THEN** the system SHALL prevent submission
- **AND** the Notes field SHALL be shown with a red border
- **AND** the modal SHALL remain open

#### Scenario: Submitting a valid note
- **WHEN** the "Create Notes" modal is open
- **AND** the user enters note content and submits
- **THEN** the note SHALL be saved and associated with the tracker
- **AND** the modal SHALL close
- **AND** the note SHALL appear in the tracker's right-side panel

### Requirement: Generate an Activity Log entry when a logged field is saved
When the user saves a change to a field configured as "logged" (`logUpdates: true`), the system SHALL create one Activity Log entry recording the field name, old value, new value, the user who made the change, and the date/time of the change. Fields not configured as logged SHALL NOT generate an Activity Log entry. Saving the same field again SHALL create an additional entry without altering or removing prior entries.

#### Scenario: Saving a logged field creates one entry
- **WHEN** the user changes a field configured as "logged" and saves it
- **THEN** the system SHALL create exactly one Activity Log entry for that save
- **AND** the entry SHALL record the field name, old value, new value, user name, and date/time

#### Scenario: Saving a non-logged field creates no entry
- **WHEN** the user changes a field not configured as "logged" and it is saved
- **THEN** no Activity Log entry SHALL be created

#### Scenario: Repeated saves preserve history
- **WHEN** a logged field that already has one or more Activity Log entries is saved again
- **THEN** a new Activity Log entry SHALL be created
- **AND** all previously existing Activity Log entries SHALL remain unchanged

### Requirement: Display Activity Log entries using the standard format
Each Activity Log entry displayed in the right-side panel SHALL use the format: "Field edited by \<user name\> - \<date and time\>" on the first line, followed by "Field Name: \<field name\>", "Old Value: \<old value\>", and "New Value: \<new value\>" on subsequent lines.

#### Scenario: Activity Log entry rendering
- **WHEN** an Activity Log entry is displayed in the right-side panel
- **THEN** it SHALL show "Field edited by \<user name\> - \<date and time\>" as its first line
- **AND** "Field Name: \<field name\>", "Old Value: \<old value\>", and "New Value: \<new value\>" as its following lines

### Requirement: Display notes and Activity Log entries together in chronological order
The right-side panel SHALL display user-created notes, Activity Log entries, and existing status-change notes together in a single timeline, ordered by creation date/time with the most recent entry first, and this ordering SHALL be preserved across reload/reopen.

#### Scenario: Mixed entries sorted newest first
- **WHEN** the right-side panel contains a mix of notes, Activity Log entries, and status-change notes
- **THEN** they SHALL all be displayed together, sorted with the most recently created entry at the top

#### Scenario: Ordering persists across reopen
- **WHEN** a hospitalization tracker with existing notes and Activity Log entries is reopened
- **THEN** all entries SHALL remain available with the same chronological ordering
