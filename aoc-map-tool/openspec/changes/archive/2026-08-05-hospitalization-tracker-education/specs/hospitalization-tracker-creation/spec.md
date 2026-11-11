## MODIFIED Requirements

### Requirement: Render the tracker header, Outcome control, and tabs
The Open-state modal body SHALL show a header with patient information, assignee, tracker ID, and basic tracker info; an "Outcome" dropdown whose options depend on the tracker's current status, driving real status transitions through the full lifecycle; a tab structure that depends on whether the tracker has ever been "In Progress"; a Notes icon in the header; the "Contacts", "Diagnosis", and "Sick Plan" tabs SHALL display real content; and the "Education" tab SHALL display Training/Education content instead of an empty placeholder.

#### Scenario: Placeholder tabs render empty
- **WHEN** the user opens any tab with no built content ("Hospitalization History" or "Care Coordination")
- **THEN** the tab SHALL render as an empty placeholder

## ADDED Requirements

### Requirement: Display the Staff/Family Training/Education toggle
The "Education" tab SHALL display a "Staff / Family Training/Education required?" toggle reflecting the tracker's saved value.

#### Scenario: Education tab shows the toggle
- **WHEN** the user opens the "Education" tab
- **THEN** the "Staff / Family Training/Education required?" toggle SHALL be displayed

### Requirement: Persist the Training/Education toggle
Changing the "Staff / Family Training/Education required?" toggle SHALL immediately save its new value on the tracker.

#### Scenario: Turning the toggle ON
- **WHEN** the user turns ON the "Staff / Family Training/Education required?" toggle
- **THEN** the system SHALL save the toggle value as ON

#### Scenario: Turning the toggle OFF
- **WHEN** the user turns OFF the "Staff / Family Training/Education required?" toggle
- **THEN** the system SHALL save the toggle value as OFF

### Requirement: Control Education table and Add Education visibility
The Education table SHALL be displayed whenever the toggle is ON. The "Add Education" button SHALL be displayed only when the toggle is ON and the tracker's status is "In Progress".

#### Scenario: Toggle ON shows the table and Add Education
- **WHEN** the "Staff / Family Training/Education required?" toggle is ON
- **AND** the tracker's status is "In Progress"
- **THEN** the Education table SHALL be displayed
- **AND** the "Add Education" button SHALL be displayed

#### Scenario: Add Education hidden when the tracker isn't In Progress
- **WHEN** the tracker's status is not "In Progress"
- **THEN** the "Add Education" button SHALL NOT be displayed regardless of the toggle value

### Requirement: Create a Training/Education record
When the user clicks "Add Education", the system SHALL display a "Training/Education" modal for a new record. Submitting without completing all required fields SHALL be prevented, with the missing fields shown with a red border, and the modal SHALL remain open. On a valid submit, the system SHALL create the record, associate it only with the current hospitalization tracker, close the modal, and display the new record in the Education table.

#### Scenario: Add Education opens the Training/Education modal
- **WHEN** the user clicks "Add Education"
- **THEN** the "Training/Education" modal SHALL open for a new record

#### Scenario: Submitting an incomplete new record
- **WHEN** the "Training/Education" modal is open for a new record
- **AND** the user submits without completing all required fields
- **THEN** the system SHALL prevent submission
- **AND** the missing required fields SHALL be shown with a red border
- **AND** the modal SHALL remain open

#### Scenario: Successfully creating a record
- **WHEN** the user completes all required fields and submits
- **THEN** the Training/Education record SHALL be created and associated only with the current hospitalization tracker
- **AND** the modal SHALL close
- **AND** the new record SHALL appear in the Education table

### Requirement: Edit a Training/Education record
When the user clicks the Action-column calendar icon for an existing Education record, the system SHALL display the "Training/Education" modal prefilled with that record's saved values. If the tracker's status is "In Progress", submitting without completing all required fields SHALL be prevented, with the missing fields shown with a red border, and the modal SHALL remain open; on a valid submit, the system SHALL update the record, close the modal, and display the updated values in the Education table.

#### Scenario: Editing opens the modal with existing values
- **WHEN** the user clicks the calendar icon for an existing Education record
- **THEN** the "Training/Education" modal SHALL open prefilled with that record's current saved values

#### Scenario: Submitting an incomplete edit
- **WHEN** the tracker's status is "In Progress"
- **AND** the "Training/Education" modal is open for an existing record
- **AND** the user clears a required field and submits
- **THEN** the system SHALL prevent submission
- **AND** the missing required fields SHALL be shown with a red border
- **AND** the modal SHALL remain open

#### Scenario: Successfully editing a record
- **WHEN** the tracker's status is "In Progress"
- **AND** the user changes one or more fields to valid values and submits
- **THEN** the record SHALL be updated
- **AND** the modal SHALL close
- **AND** the updated values SHALL appear in the Education table

### Requirement: Display Education records read-only once the tracker leaves In Progress
Once a hospitalization tracker's status is anything other than "In Progress", opening an Education record SHALL show its values with no editable fields and no Save/Submit action. Creating new records SHALL NOT be allowed. Records already saved SHALL remain available and unchanged.

#### Scenario: Opening a record shows it read-only
- **WHEN** a hospitalization tracker's status is not "In Progress"
- **AND** the user clicks the calendar icon for an existing Education record
- **THEN** the "Training/Education" modal SHALL open in read-only mode
- **AND** all fields SHALL be read-only
- **AND** no Save or Submit action SHALL be available

#### Scenario: Add Education unavailable once the tracker leaves In Progress
- **WHEN** a hospitalization tracker's status is not "In Progress"
- **THEN** the "Add Education" button SHALL NOT be displayed

#### Scenario: Saved records persist across status changes
- **WHEN** one or more Education records were created while the tracker was "In Progress"
- **AND** the tracker's status subsequently changes
- **THEN** all saved records SHALL remain available in the Education table with their saved values unchanged

### Requirement: Persist Training/Education data across reload/reopen
The Training/Education toggle value and Education records created or updated for a tracker SHALL remain available when the tracker is reopened.

#### Scenario: Reopening a tracker with saved Education data
- **WHEN** a hospitalization tracker with a previously saved toggle value and Education records is reopened
- **THEN** the same toggle value and records with their saved values SHALL be displayed
