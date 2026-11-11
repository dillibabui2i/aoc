# Hospitalization Follow-ups

Consolidated from `hospitalization-follow-ups` (archived). This is the single source of truth for the Communications bucket's Follow-up creation/management workflow; the archived change is kept for its `design.md`/decision history but no longer carries live requirements. See the `hospitalization-tracker-creation` capability for how a tracker's own "Follow-up" tab displays and opens these same records.

Frontend + mock data only (`resources/js/vue/modules/vbc`) — no backend (`app/VBC/**`, routes) changes exist for it yet.

## Requirements

### Requirement: Restrict follow-up functionality by permission
Follow-up creation and management functionality (the bucket's "+" icon, its Action-column calendar icon, and the Follow-up modal) SHALL only be available to a user with the "Intensive Case Management" permission.

#### Scenario: User lacks the Intensive Case Management permission
- **WHEN** the user does not have the "Intensive Case Management" permission
- **THEN** the "+" icon and the Action-column calendar icon SHALL NOT be displayed in the Communications bucket

### Requirement: Open the Follow-up modal for manual creation
Clicking the "+" icon in the Communications bucket SHALL open the "Follow-up" modal with status "New", displaying a "Hospitalized Client Name" field.

#### Scenario: Opening the create modal
- **WHEN** the user clicks the "+" icon in the Communications bucket
- **THEN** the "Follow-up" modal SHALL open
- **AND** the follow-up status SHALL be "New"
- **AND** the "Hospitalized Client Name" field SHALL be displayed

### Requirement: Search for hospitalized patients
The "Hospitalized Client Name" field SHALL support autocomplete search, returning only patients with at least one hospitalization tracker whose status is not "Closed".

#### Scenario: Searching returns only currently-hospitalized patients
- **WHEN** the user enters one or more characters into "Hospitalized Client Name"
- **THEN** the system SHALL display matching patients
- **AND** every result SHALL have a hospitalization tracker with a status other than "Closed"

### Requirement: Auto-populate hospitalization information on patient selection
Selecting a patient SHALL automatically populate Client ID, Hospitalization ID, Facility Name, and Admission Date from that patient's non-Closed hospitalization tracker.

#### Scenario: Selecting a patient populates the header fields
- **WHEN** the user selects a patient from the search results
- **THEN** Client ID, Hospitalization ID, Facility Name, and Admission Date SHALL be automatically populated

### Requirement: Display an optional-follow-up informational message based on patient level and admission age
When a patient is selected during follow-up creation, the system SHALL evaluate their most recent non-Closed hospitalization's Patient Level and Admission Date and, when applicable, display a non-blocking informational message: Level "I/III" with Admission Date more than 30 days old shows "More than 30 days have passed since the admission date, and based on this patient's level, this follow-up will be optional."; Level "II/III" with Admission Date more than 60 days old shows the equivalent 60-day message; Level "III" with Admission Date more than 90 days old shows the equivalent 90-day message. No other level/age combination displays a message.

#### Scenario: Level I/III past 30 days
- **WHEN** the selected patient's hospitalization Level is "I/III"
- **AND** the Admission Date is more than 30 days before the current date
- **THEN** the system SHALL display "More than 30 days have passed since the admission date, and based on this patient's level, this follow-up will be optional."

#### Scenario: Level II/III past 60 days
- **WHEN** the selected patient's hospitalization Level is "II/III"
- **AND** the Admission Date is more than 60 days before the current date
- **THEN** the system SHALL display "More than 60 days have passed since the admission date, and based on this patient's level, this follow-up will be optional."

#### Scenario: Level III past 90 days
- **WHEN** the selected patient's hospitalization Level is "III"
- **AND** the Admission Date is more than 90 days before the current date
- **THEN** the system SHALL display "More than 90 days have passed since the admission date, and based on this patient's level, this follow-up will be optional."

#### Scenario: Non-qualifying level or age shows no message
- **WHEN** the selected patient's hospitalization Level and Admission Date age do not match any of the above combinations
- **THEN** no informational message SHALL be displayed

### Requirement: Save a new follow-up as Open
Submitting the Follow-up modal for the first time SHALL create the follow-up, associated with the selected patient and their hospitalization tracker, and set its status to "Open". The same modal SHALL remain open, now showing the Follow-up Outcome dropdown.

#### Scenario: Successful first save
- **WHEN** the user has selected a hospitalized patient and completes all required fields
- **AND** the user clicks Save
- **THEN** the follow-up SHALL be created and associated with the selected patient's hospitalization tracker
- **AND** the follow-up status SHALL become "Open"

### Requirement: Display Follow-up Outcome options for an Open or Scheduled follow-up
When a follow-up has status "Open", the Follow-up Outcome dropdown SHALL offer Scheduled, Completed, Attempted, and Canceled. When a follow-up has status "Scheduled", the dropdown SHALL offer Completed, Attempted, and Canceled.

#### Scenario: Outcome options for an Open follow-up
- **WHEN** a follow-up has status "Open"
- **THEN** the Follow-up Outcome dropdown SHALL display Scheduled, Completed, Attempted, and Canceled

#### Scenario: Outcome options for a Scheduled follow-up
- **WHEN** a follow-up has status "Scheduled"
- **THEN** the Follow-up Outcome dropdown SHALL display Completed, Attempted, and Canceled

### Requirement: Schedule a follow-up
Selecting "Scheduled" SHALL display a required Follow-up Date field and a required Follow-up Assigned To field. Submitting without completing both SHALL be prevented, with the missing fields shown with a red border. On a valid submit, the follow-up's status SHALL become "Scheduled", the entered values SHALL be saved, and the modal SHALL remain open, now allowing a further Completed/Attempted/Canceled transition.

#### Scenario: Scheduling fields appear
- **WHEN** the user selects "Scheduled" from the Follow-up Outcome dropdown
- **THEN** the Follow-up Date and Follow-up Assigned To fields SHALL be displayed

#### Scenario: Submitting incomplete scheduling information
- **WHEN** the user submits without completing the Follow-up Date or Follow-up Assigned To
- **THEN** the system SHALL prevent submission
- **AND** the missing required fields SHALL be shown with a red border

#### Scenario: Successfully scheduling
- **WHEN** the user completes the Follow-up Date and Follow-up Assigned To and submits
- **THEN** the follow-up's status SHALL become "Scheduled"
- **AND** the entered values SHALL be saved

### Requirement: Complete a follow-up
Selecting "Completed" SHALL display required Status, Follow-Ups/Updates, and Needs fields, an optional Additional Notes field, system-set Completion Date and Completed By fields, and a "Next Follow-up Due" toggle. Submitting without completing the required fields SHALL be prevented, with the missing fields shown with a red border. On a valid submit, the follow-up's status SHALL become "Completed" and its fields SHALL become read-only.

#### Scenario: Completion fields appear
- **WHEN** the user selects "Completed" from the Follow-up Outcome dropdown
- **THEN** the Status, Follow-Ups/Updates, Needs, and Additional Notes fields SHALL be displayed
- **AND** the "Next Follow-up Due" toggle SHALL be displayed
- **AND** Completion Date and Completed By SHALL be system-set

#### Scenario: Submitting incomplete completion information
- **WHEN** the user submits without completing Status, Follow-Ups/Updates, or Needs
- **THEN** the system SHALL prevent submission
- **AND** the missing required fields SHALL be shown with a red border

#### Scenario: Completing without a next follow-up
- **WHEN** the user completes all required completion fields with "Next Follow-up Due" not activated and submits
- **THEN** the follow-up's status SHALL become "Completed"
- **AND** its fields SHALL become read-only
- **AND** no additional follow-up SHALL be created

### Requirement: Attempt a follow-up
Selecting "Attempted" SHALL display a required Attempt Notes field, system-set Attempt Date and Attempted By fields, and a "Next Follow-up Due" toggle. Submitting without completing Attempt Notes SHALL be prevented, with the field shown with a red border. On a valid submit, the follow-up's status SHALL become "Attempted" and its fields SHALL become read-only.

#### Scenario: Attempt fields appear
- **WHEN** the user selects "Attempted" from the Follow-up Outcome dropdown
- **THEN** the Attempt Notes field SHALL be displayed
- **AND** the "Next Follow-up Due" toggle SHALL be displayed
- **AND** Attempt Date and Attempted By SHALL be system-set

#### Scenario: Submitting incomplete attempt information
- **WHEN** the user submits without completing Attempt Notes
- **THEN** the system SHALL prevent submission
- **AND** the field SHALL be shown with a red border

#### Scenario: Attempting without a next follow-up
- **WHEN** the user completes Attempt Notes with "Next Follow-up Due" not activated and submits
- **THEN** the follow-up's status SHALL become "Attempted"
- **AND** its fields SHALL become read-only
- **AND** no additional follow-up SHALL be created

### Requirement: Default and validate the Next Follow-up Due fields
When "Next Follow-up Due" is activated (for Completed or Attempted), a required Next Follow-up Due Date and a required Next Follow-up Assigned To SHALL be displayed. If the follow-up's hospitalization Level is "I/III", "II/III", or "III", "Next Follow-up Due" SHALL default to ON and the Due Date SHALL default to the current follow-up's own scheduled date/time plus one week (both remaining editable); for any other level, it SHALL default to OFF. Submitting with "Next Follow-up Due" on but the date or assignee incomplete SHALL be prevented, with the missing fields shown with a red border.

#### Scenario: Activating Next Follow-up Due reveals its fields
- **WHEN** the user activates "Next Follow-up Due" for a Completed or Attempted outcome
- **THEN** the Next Follow-up Due Date and Next Follow-up Assigned To fields SHALL be displayed

#### Scenario: Default ON for a qualifying level
- **WHEN** the outcome fields are displayed for "Completed" or "Attempted"
- **AND** the follow-up's hospitalization Level is "I/III", "II/III", or "III"
- **THEN** "Next Follow-up Due" SHALL default to ON
- **AND** the Next Follow-up Due Date SHALL default to one week after the follow-up's own date/time
- **AND** both fields SHALL remain editable

#### Scenario: Default OFF for a non-qualifying level
- **WHEN** the outcome fields are displayed for "Completed" or "Attempted"
- **AND** the follow-up's hospitalization Level is not "I/III", "II/III", or "III"
- **THEN** "Next Follow-up Due" SHALL default to OFF

#### Scenario: Submitting with Next Follow-up Due on but incomplete
- **WHEN** "Next Follow-up Due" is activated
- **AND** the user submits without completing the Next Follow-up Due Date or Next Follow-up Assigned To
- **THEN** the system SHALL prevent submission
- **AND** the missing required fields SHALL be shown with a red border

### Requirement: Create a chained Scheduled follow-up on completion or attempt
When the user submits a Completed or Attempted outcome with "Next Follow-up Due" activated and its fields completed, the system SHALL create a new, separate follow-up record with status "Scheduled" using the entered date and assignee, associated with the same hospitalization tracker, in addition to finalizing the current follow-up.

#### Scenario: Completing with a next follow-up
- **WHEN** the user submits a "Completed" outcome with "Next Follow-up Due" activated and its fields completed
- **THEN** the current follow-up's status SHALL become "Completed" and its fields SHALL become read-only
- **AND** a new follow-up record SHALL be created with status "Scheduled", using the entered date and assignee

#### Scenario: Attempting with a next follow-up
- **WHEN** the user submits an "Attempted" outcome with "Next Follow-up Due" activated and its fields completed
- **THEN** the current follow-up's status SHALL become "Attempted" and its fields SHALL become read-only
- **AND** a new follow-up record SHALL be created with status "Scheduled", using the entered date and assignee

### Requirement: Cancel a follow-up
Selecting "Canceled" SHALL display no additional fields. On submit, the follow-up's status SHALL become "Canceled" and its fields SHALL become read-only.

#### Scenario: No additional fields for Canceled
- **WHEN** the user selects "Canceled" from the Follow-up Outcome dropdown
- **THEN** no additional outcome fields SHALL be displayed

#### Scenario: Canceling a follow-up
- **WHEN** the user submits the "Canceled" outcome
- **THEN** the follow-up's status SHALL become "Canceled"
- **AND** its fields SHALL become read-only

### Requirement: Editability follows the follow-up's own status
A follow-up's fields SHALL be editable while its status is "New", "Open", or "Scheduled", and read-only while its status is "Completed", "Attempted", or "Canceled" — independent of the associated hospitalization tracker's own status.

#### Scenario: Editable statuses
- **WHEN** a follow-up has status "New", "Open", or "Scheduled"
- **THEN** its applicable fields SHALL be editable

#### Scenario: Read-only statuses
- **WHEN** a follow-up has status "Completed", "Attempted", or "Canceled"
- **THEN** all of its saved fields SHALL be displayed read-only, with no Save/Submit action available

### Requirement: Open an existing follow-up from the Communications bucket
Clicking the Action-column calendar icon for a follow-up in the Communications bucket SHALL open the Follow-up modal populated with its saved values and current status.

#### Scenario: Opening from the bucket
- **WHEN** the user clicks the calendar icon for a follow-up in the Communications bucket
- **THEN** the Follow-up modal SHALL open, displaying the saved data and reflecting the current status

### Requirement: Associate a follow-up with the selected hospitalization
A follow-up, once saved, SHALL remain associated only with the hospitalization tracker it was created against.

#### Scenario: Follow-up stays associated with its tracker
- **WHEN** a follow-up is created or updated
- **THEN** it SHALL remain associated only with the hospitalization tracker selected at creation

### Requirement: Persist follow-up data across reopen
A follow-up's saved status, patient information, scheduling information, assignment, and documentation SHALL remain available when it is reopened.

#### Scenario: Reopening a saved follow-up
- **WHEN** the user reopens a previously created or updated follow-up
- **THEN** its saved status, patient information, scheduling information, assignment, and documentation SHALL remain available

### Requirement: Display eligible follow-ups in the Communications bucket
The Communications bucket SHALL display all follow-ups associated with a non-"Closed" hospitalization tracker, except those with status "Completed" or "Attempted".

#### Scenario: Eligible follow-ups are listed
- **WHEN** a follow-up's hospitalization tracker status is not "Closed"
- **AND** the follow-up's own status is not "Completed" or "Attempted"
- **THEN** the follow-up SHALL be displayed in the Communications bucket

#### Scenario: Completed and Attempted follow-ups are excluded
- **WHEN** a follow-up's status is "Completed" or "Attempted"
- **THEN** it SHALL NOT be displayed in the Communications bucket
