# Care Coordination Activities

Consolidated from `care-coordination-activities` (archived). This is the single source of truth for the Care Coordination bucket's activity creation/management workflow; the archived change is kept for its `design.md`/decision history and `test-cases.md` but no longer carries live requirements.

Frontend + mock data only (`resources/js/vue/modules/vbc`) — no backend (`app/VBC/**`, routes) changes exist for it yet.

## Requirements

### Requirement: Restrict care coordination functionality by permission
Care coordination creation and editing functionality (the bucket's "+" icon, its Action-column calendar icon, and the "Care Coordination" modal) SHALL only be available to a user with the "Intensive Case Management" permission.

#### Scenario: User lacks the Intensive Case Management permission
- **WHEN** the user does not have the "Intensive Case Management" permission
- **THEN** the "+" icon and the Action-column calendar icon SHALL NOT be displayed in the Care Coordination bucket

### Requirement: Open the Care Coordination modal for creation
Clicking the "+" icon in the Care Coordination bucket SHALL open the "Care Coordination" modal in create mode, displaying a patient selection field.

#### Scenario: Opening the create modal
- **WHEN** the user clicks the "+" icon in the Care Coordination bucket
- **THEN** the "Care Coordination" modal SHALL open
- **AND** a "Patient" selection field SHALL be displayed

### Requirement: Restrict patient selection to In Progress hospitalization trackers
The "Patient" field's selection list SHALL only include clients whose hospitalization tracker has status "In Progress".

#### Scenario: Opening the patient selection list
- **WHEN** the "Patient" field's selection list is opened
- **THEN** only clients with a hospitalization tracker in status "In Progress" SHALL be available for selection

### Requirement: Create a care coordination activity
After selecting a patient with an "In Progress" hospitalization tracker, the modal SHALL display the care coordination fields. Submitting without completing all required fields SHALL be prevented, with missing fields shown with a red border. On a valid submit, the system SHALL create the activity, close the modal, refresh the bucket, and display the new activity.

#### Scenario: Selecting a patient reveals the coordination fields
- **WHEN** the user selects a patient from the "Patient" field
- **THEN** the care coordination fields SHALL be displayed

#### Scenario: Submitting an incomplete new activity
- **WHEN** the user clicks Save without completing all required fields
- **THEN** the system SHALL prevent submission
- **AND** the missing required fields SHALL be shown with a red border

#### Scenario: Successfully creating an activity
- **WHEN** the user completes all required fields and clicks Save
- **THEN** the care coordination activity SHALL be created
- **AND** the modal SHALL close
- **AND** the Care Coordination bucket SHALL refresh
- **AND** the newly added activity SHALL be displayed

### Requirement: Edit an existing care coordination activity
Clicking the Action-column calendar icon for an activity whose linked hospitalization tracker has status "In Progress" SHALL open the "Care Coordination" modal populated with that activity's existing values, with the coordination fields editable. Submitting without completing all required fields SHALL be prevented, with missing fields shown with a red border. On a valid submit, the system SHALL save the changes, close the modal, refresh the bucket, and display the updated values.

#### Scenario: Opening an editable activity
- **WHEN** the user clicks the calendar icon for an activity whose linked tracker status is "In Progress"
- **THEN** the "Care Coordination" modal SHALL open populated with that activity's existing values
- **AND** the coordination fields SHALL be editable

#### Scenario: Submitting an incomplete edit
- **WHEN** the user removes a required value and clicks Save
- **THEN** the system SHALL prevent submission
- **AND** the missing required fields SHALL be shown with a red border

#### Scenario: Successfully editing an activity
- **WHEN** the user edits one or more fields and clicks Save
- **THEN** the changes SHALL be saved
- **AND** the modal SHALL close
- **AND** the Care Coordination bucket SHALL refresh
- **AND** the updated values SHALL be displayed

### Requirement: Open a non-In-Progress-tracker activity in read-only mode
Clicking the Action-column calendar icon for an activity whose linked hospitalization tracker status is not "In Progress" SHALL open the "Care Coordination" modal in read-only mode, with every field disabled and no Save action.

#### Scenario: Opening a read-only activity
- **WHEN** the user clicks the calendar icon for an activity whose linked tracker status is not "In Progress"
- **THEN** the "Care Coordination" modal SHALL open in read-only mode
- **AND** all fields SHALL be disabled
- **AND** no Save action SHALL be available

### Requirement: Persist care coordination data across refresh
Care coordination activities created or edited SHALL remain available, with the same values, when the Care Coordination bucket is refreshed.

#### Scenario: Refreshing the bucket
- **WHEN** the Care Coordination bucket is refreshed after an activity was created or edited
- **THEN** the saved activity data SHALL remain available with its saved values
