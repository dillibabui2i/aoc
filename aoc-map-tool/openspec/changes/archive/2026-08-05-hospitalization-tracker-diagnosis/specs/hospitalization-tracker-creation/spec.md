## MODIFIED Requirements

### Requirement: Render the tracker header, Outcome control, and tabs
The Open-state modal body SHALL show a header with patient information, assignee, tracker ID, and basic tracker info; an "Outcome" dropdown whose options depend on the tracker's current status, driving real status transitions through the full lifecycle; a tab structure that depends on whether the tracker has ever been "In Progress"; a Notes icon in the header available whenever the tracker's status is neither "Closed" nor "Completed"; the "Contacts" tab SHALL display real contact management content; and the "Diagnosis" tab SHALL display diagnosis management content instead of an empty placeholder.

#### Scenario: Placeholder tabs render empty
- **WHEN** the user opens any tab with no built content ("Hospitalization History", "Sick Plan", "Follow-up", "Care Coordination", or "Education")
- **THEN** the tab SHALL render as an empty placeholder

## ADDED Requirements

### Requirement: Display the diagnosis confirmation toggle
The "Diagnosis" tab SHALL display a "Was the Diagnosis Made?" toggle reflecting the tracker's saved value.

#### Scenario: Diagnosis tab shows the toggle
- **WHEN** the user opens the "Diagnosis" tab
- **THEN** the "Was the Diagnosis Made?" toggle SHALL be displayed

### Requirement: Persist the diagnosis confirmation toggle
Changing the "Was the Diagnosis Made?" toggle SHALL immediately save its new value on the tracker.

#### Scenario: Turning the toggle ON
- **WHEN** the user turns ON the "Was the Diagnosis Made?" toggle
- **THEN** the system SHALL save the toggle value as ON

#### Scenario: Turning the toggle OFF
- **WHEN** the user turns OFF the "Was the Diagnosis Made?" toggle
- **THEN** the system SHALL save the toggle value as OFF

### Requirement: Control diagnosis table and Add Diagnosis visibility
The diagnosis list table SHALL be displayed whenever the toggle is ON, or whenever the toggle is OFF but one or more diagnosis records exist; it SHALL be hidden when the toggle is OFF and no diagnosis records exist. The "Add Diagnosis" button SHALL be displayed only when the toggle is ON and the tracker's status is "In Progress".

#### Scenario: Toggle ON shows the table and Add Diagnosis
- **WHEN** the "Was the Diagnosis Made?" toggle is ON
- **AND** the tracker's status is "In Progress"
- **THEN** the diagnosis list table SHALL be displayed
- **AND** the "Add Diagnosis" button SHALL be displayed

#### Scenario: Toggle OFF with no records hides both
- **WHEN** the "Was the Diagnosis Made?" toggle is OFF
- **AND** no diagnosis records exist
- **THEN** the diagnosis list table SHALL NOT be displayed
- **AND** the "Add Diagnosis" button SHALL NOT be displayed

#### Scenario: Toggle OFF with existing records keeps the table visible
- **WHEN** the "Was the Diagnosis Made?" toggle is OFF
- **AND** one or more diagnosis records exist
- **THEN** the diagnosis list table SHALL remain displayed
- **AND** the "Add Diagnosis" button SHALL NOT be displayed

#### Scenario: Add Diagnosis hidden when the tracker isn't In Progress
- **WHEN** the tracker's status is not "In Progress"
- **THEN** the "Add Diagnosis" button SHALL NOT be displayed regardless of the toggle value

### Requirement: Create a diagnosis record
When the user clicks "Add Diagnosis", the system SHALL display a "Diagnosis Details" modal for a new diagnosis. Submitting without completing all required fields SHALL be prevented, with the missing fields shown with a red border, and the modal SHALL remain open. On a valid submit, the system SHALL create the diagnosis, associate it only with the current hospitalization tracker, close the modal, and display the new diagnosis in the diagnosis list table.

#### Scenario: Add Diagnosis opens the Diagnosis Details modal
- **WHEN** the user clicks "Add Diagnosis"
- **THEN** the "Diagnosis Details" modal SHALL open for a new diagnosis

#### Scenario: Submitting an incomplete new diagnosis
- **WHEN** the "Diagnosis Details" modal is open for a new diagnosis
- **AND** the user submits without completing all required fields
- **THEN** the system SHALL prevent submission
- **AND** the missing required fields SHALL be shown with a red border
- **AND** the modal SHALL remain open

#### Scenario: Successfully creating a diagnosis
- **WHEN** the user completes all required fields and submits
- **THEN** the diagnosis SHALL be created and associated only with the current hospitalization tracker
- **AND** the modal SHALL close
- **AND** the new diagnosis SHALL appear in the diagnosis list table

### Requirement: Edit a diagnosis record
When the user clicks the Action-column calendar icon for an existing diagnosis, the system SHALL display the "Diagnosis Details" modal prefilled with that diagnosis's saved values. If the tracker's status is "In Progress", submitting without completing all required fields SHALL be prevented, with the missing fields shown with a red border, and the modal SHALL remain open; on a valid submit, the system SHALL update the diagnosis, close the modal, and display the updated values in the diagnosis list table.

#### Scenario: Editing opens the modal with existing values
- **WHEN** the user clicks the calendar icon for an existing diagnosis
- **THEN** the "Diagnosis Details" modal SHALL open prefilled with that diagnosis's current saved values

#### Scenario: Submitting an incomplete edit
- **WHEN** the tracker's status is "In Progress"
- **AND** the "Diagnosis Details" modal is open for an existing diagnosis
- **AND** the user clears a required field and submits
- **THEN** the system SHALL prevent submission
- **AND** the missing required fields SHALL be shown with a red border
- **AND** the modal SHALL remain open

#### Scenario: Successfully editing a diagnosis
- **WHEN** the tracker's status is "In Progress"
- **AND** the user changes one or more fields to valid values and submits
- **THEN** the diagnosis SHALL be updated
- **AND** the modal SHALL close
- **AND** the updated values SHALL appear in the diagnosis list table

### Requirement: Display diagnosis records read-only once the tracker leaves In Progress
Once a hospitalization tracker's status is anything other than "In Progress", opening a diagnosis SHALL show its values with no editable fields and no Save/Submit action. Diagnosis records already saved SHALL remain available and unchanged.

#### Scenario: Opening a diagnosis shows it read-only
- **WHEN** a hospitalization tracker's status is not "In Progress"
- **AND** the user clicks the calendar icon for an existing diagnosis
- **THEN** the "Diagnosis Details" modal SHALL open in read-only mode
- **AND** all diagnosis fields SHALL be read-only
- **AND** no Save or Submit action SHALL be available

#### Scenario: Saved diagnoses persist across status changes
- **WHEN** one or more diagnoses were created while the tracker was "In Progress"
- **AND** the tracker's status subsequently changes
- **THEN** all saved diagnoses SHALL remain available in the diagnosis list table with their saved values unchanged

### Requirement: Persist diagnosis toggle and data across reload/reopen
The "Was the Diagnosis Made?" toggle value and diagnosis records created or updated for a tracker SHALL remain available when the tracker is reopened, backed by the mocked service in this change.

#### Scenario: Reopening a tracker with saved diagnosis data
- **WHEN** a hospitalization tracker with a previously saved toggle value and diagnoses is reopened
- **THEN** the same toggle value and diagnoses with their saved values SHALL be displayed
