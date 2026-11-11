## MODIFIED Requirements

### Requirement: Render the tracker header, Outcome control, and tabs
The Open-state modal body SHALL show a header with patient information, assignee, tracker ID, and basic tracker info; an "Outcome" dropdown whose options depend on the tracker's current status, driving real status transitions through the full lifecycle (Open → In Progress → Discharged → Ready for Review → Ready for Next Steps → Completed, or Open/In Progress → Closed); and a tab structure that depends on whether the tracker has ever been "In Progress".

#### Scenario: Outcome options for a Discharged tracker
- **WHEN** a hospitalization tracker has status "Discharged" and the Open-state modal is rendered
- **THEN** the Outcome dropdown SHALL display "Ready for Review"

#### Scenario: Outcome options for a Ready for Review tracker
- **WHEN** a hospitalization tracker has status "Ready for Review" and the Open-state modal is rendered
- **THEN** the Outcome dropdown SHALL display "Ready for Next Steps"

#### Scenario: Outcome options for a Ready for Next Steps tracker
- **WHEN** a hospitalization tracker has status "Ready for Next Steps" and the Open-state modal is rendered
- **THEN** the Outcome dropdown SHALL display "Completed"

#### Scenario: Outcome control is hidden once Completed
- **WHEN** a hospitalization tracker has status "Completed"
- **THEN** the Outcome dropdown SHALL NOT be displayed

### Requirement: Persist and re-render the Closed state on reopen
A tracker's terminal-state (Closed or Completed) disabled/hidden state SHALL be fully re-derived from its saved status whenever it is displayed, not only immediately after the transition.

#### Scenario: Reopening a Completed tracker
- **WHEN** a hospitalization tracker with status "Completed" is displayed (including on reopen from a historical view)
- **THEN** all patient custom fields SHALL be disabled
- **AND** the "Assigned To" dropdown SHALL be disabled
- **AND** all hospitalization information fields SHALL be disabled
- **AND** the Outcome dropdown SHALL be hidden
- **AND** the saved status SHALL remain available

## ADDED Requirements

### Requirement: Discharge a hospitalization tracker
When the user selects "Discharge" from the Outcome dropdown, the system SHALL display discharge fields (Expected Discharge Date, Discharge Date, a toggle for whether the first billable shift completed within 48 hours of discharge, a conditional First Billable Shift Date shown only when that toggle is on, and Discharge Notes). Submitting without completing all required fields SHALL be prevented, with the missing fields shown with a red border. On a valid submit, the system SHALL update the tracker's status to "Discharged", add a note, persist the entered discharge fields onto the tracker (visible thereafter as ongoing editable fields on the Current Hospitalization Info tab), and keep "Assigned To" enabled.

#### Scenario: Discharge fields appear
- **WHEN** a hospitalization tracker has status "In Progress"
- **AND** the user selects "Discharge" from the Outcome dropdown
- **THEN** the discharge fields SHALL be displayed

#### Scenario: Submitting an incomplete discharge
- **WHEN** the discharge fields are displayed
- **AND** the user submits without completing all required fields
- **THEN** the system SHALL prevent submission
- **AND** the missing required fields SHALL be shown with a red border

#### Scenario: Successful discharge
- **WHEN** the user completes all required discharge fields and submits
- **THEN** the hospitalization tracker status SHALL become "Discharged"
- **AND** a note SHALL be added with the format: line 1 "Discharged by \<username\> at \<date and time\>", line 2 "Status changed to 'Discharged' by \<user name\>"
- **AND** the entered discharge field values SHALL be saved and remain editable on the Current Hospitalization Info tab
- **AND** the "Assigned To" dropdown SHALL remain enabled

### Requirement: Send a discharged tracker for review
When the user selects "Ready for Review" from the Outcome dropdown, the system SHALL display a required Reviewer field and a required Notes field. Submitting without completing all required fields SHALL be prevented, with the missing fields shown with a red border. On a valid submit, the system SHALL update the tracker's status to "Ready for Review", add a note using the entered notes as its second line, store the tracker's current assignee into an internal "Who Sent to Review" field, reassign the tracker to the selected reviewer, and keep "Assigned To" enabled.

#### Scenario: Ready for Review fields appear
- **WHEN** a hospitalization tracker has status "Discharged"
- **AND** the user selects "Ready for Review" from the Outcome dropdown
- **THEN** a Reviewer field and a Notes field SHALL be displayed

#### Scenario: Submitting an incomplete review request
- **WHEN** the Ready for Review fields are displayed
- **AND** the user submits without completing all required fields
- **THEN** the system SHALL prevent submission
- **AND** the missing required fields SHALL be shown with a red border

#### Scenario: Successful review request
- **WHEN** the user completes the Reviewer and Notes fields and submits
- **THEN** the hospitalization tracker status SHALL become "Ready for Review"
- **AND** a note SHALL be added with the format: line 1 "Ready for Review by \<username\> at \<date and time\>", line 2 \<the entered notes\>
- **AND** the internal "Who Sent to Review" field SHALL store the assignee that was current before this submission
- **AND** the tracker's assignee SHALL become the selected reviewer
- **AND** the "Assigned To" dropdown SHALL remain enabled

### Requirement: Move a reviewed tracker to next steps
When the user selects "Ready for Next Steps" from the Outcome dropdown, the system SHALL display a required "Next Steps to be Made By" field (defaulting to the value stored in "Who Sent to Review") and a required Notes field. Submitting without completing all required fields SHALL be prevented, with the missing fields shown with a red border. On a valid submit, the system SHALL update the tracker's status to "Ready for Next Steps", add a note using the entered notes as its second line, reassign the tracker to the selected user, and keep "Assigned To" enabled.

#### Scenario: Ready for Next Steps fields appear with a default assignee
- **WHEN** a hospitalization tracker has status "Ready for Review"
- **AND** the user selects "Ready for Next Steps" from the Outcome dropdown
- **THEN** a "Next Steps to be Made By" field and a Notes field SHALL be displayed
- **AND** "Next Steps to be Made By" SHALL default to the value stored in "Who Sent to Review"

#### Scenario: Submitting incomplete next-steps information
- **WHEN** the Ready for Next Steps fields are displayed
- **AND** the user submits without completing all required fields
- **THEN** the system SHALL prevent submission
- **AND** the missing required fields SHALL be shown with a red border

#### Scenario: Successful next-steps submission
- **WHEN** the user completes the required fields and submits
- **THEN** the hospitalization tracker status SHALL become "Ready for Next Steps"
- **AND** a note SHALL be added with the format: line 1 "Ready for Next Steps by \<username\> at \<date and time\>", line 2 \<the entered notes\>
- **AND** the tracker's assignee SHALL become the selected "Next Steps to be Made By" user
- **AND** the "Assigned To" dropdown SHALL remain enabled

### Requirement: Complete a hospitalization tracker
When the user selects "Completed" from the Outcome dropdown, the system SHALL display a required "Completed By" field (defaulting to the tracker's current assignee) and a required Notes field. Submitting without completing all required fields SHALL be prevented, with the missing fields shown with a red border. On a valid submit, the system SHALL update the tracker's status to "Completed", add a note, disable the "Assigned To" control, hide the Outcome control, and disable all hospitalization tracker fields.

#### Scenario: Completed fields appear with a default value
- **WHEN** a hospitalization tracker has status "Ready for Next Steps"
- **AND** the user selects "Completed" from the Outcome dropdown
- **THEN** a "Completed By" field and a Notes field SHALL be displayed
- **AND** "Completed By" SHALL default to the tracker's current assignee

#### Scenario: Submitting incomplete completion information
- **WHEN** the Completed fields are displayed
- **AND** the user submits without completing all required fields
- **THEN** the system SHALL prevent submission
- **AND** the missing required fields SHALL be shown with a red border

#### Scenario: Successful completion
- **WHEN** the user completes the required fields and submits
- **THEN** the hospitalization tracker status SHALL become "Completed"
- **AND** a note SHALL be added with the format: line 1 "Completed by \<username\> at \<date and time\>", line 2 \<the entered notes\>
- **AND** the "Assigned To" control SHALL become disabled
- **AND** the Outcome control SHALL be hidden
- **AND** all hospitalization tracker fields SHALL be disabled
