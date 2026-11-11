## MODIFIED Requirements

### Requirement: Render the tracker header, Outcome control, and tabs
The Open-state modal body SHALL show a header with patient information, assignee, tracker ID, and basic tracker info; an "Outcome" dropdown whose options depend on the tracker's current status, driving real status transitions; and a tab structure that depends on whether the tracker has ever been "In Progress".

#### Scenario: Header displays tracker basics
- **WHEN** the Open-state modal is rendered
- **THEN** the header SHALL display patient information, assignee, tracker ID, and basic tracker info

#### Scenario: Outcome options for an Open tracker
- **WHEN** a hospitalization tracker has status "Open" and the Open-state modal is rendered
- **THEN** the Outcome dropdown SHALL display "In Progress" and "Closed"

#### Scenario: Outcome options for an In Progress tracker
- **WHEN** a hospitalization tracker has status "In Progress" and the Open-state modal is rendered
- **THEN** the Outcome dropdown SHALL display "Discharge" and "Closed"

#### Scenario: Outcome control is hidden once Closed
- **WHEN** a hospitalization tracker has status "Closed"
- **THEN** the Outcome dropdown SHALL NOT be displayed

#### Scenario: Flat tabs before any In Progress transition
- **WHEN** a hospitalization tracker has status "Open", or has status "Closed" without ever having been "In Progress"
- **THEN** the tabs SHALL render as: "Current Hospitalization Info", "Hospitalization History", "Sick Plan", "Follow-up"

#### Scenario: Nested tabs once In Progress
- **WHEN** a hospitalization tracker has status "In Progress", or has status "Closed" after having been "In Progress"
- **THEN** the tabs SHALL render in the order: "Hospitalization Info" (containing "Current Hospitalization Info" and "Hospitalization History" as sub-tabs), "Contacts", "Diagnosis", "Sick Plan", "Care Coordination", "Education", "Follow-up"

#### Scenario: Placeholder tabs render empty
- **WHEN** the user opens any tab with no built content ("Hospitalization History", "Sick Plan", "Follow-up", "Contacts", "Diagnosis", "Care Coordination", or "Education")
- **THEN** the tab SHALL render as an empty placeholder

## ADDED Requirements

### Requirement: Display an admission-age informational banner
When a hospitalization tracker's status is not "Closed" and its Admission Date/Time is more than 24 hours before the current date and time, a blue informational banner SHALL be displayed above the Outcome dropdown.

#### Scenario: Admission Date is more than 24 hours old
- **WHEN** a hospitalization tracker has status "Open" or "In Progress"
- **AND** the Admission Date is more than 24 hours before the current date and time
- **THEN** a blue informational banner SHALL be displayed above the Outcome dropdown

#### Scenario: Admission Date is within 24 hours
- **WHEN** a hospitalization tracker has status "Open" or "In Progress"
- **AND** the Admission Date is 24 hours or less before the current date and time
- **THEN** the blue informational banner SHALL NOT be displayed

### Requirement: Transition a tracker to In Progress
When the user selects "In Progress" from the Outcome dropdown and submits, the system SHALL update the tracker's status to "In Progress", add a note recording the transition, and keep "Assigned To" enabled.

#### Scenario: Submitting In Progress
- **WHEN** a hospitalization tracker has status "Open"
- **AND** the user selects "In Progress" from the Outcome dropdown and submits
- **THEN** the hospitalization tracker status SHALL become "In Progress"
- **AND** a note SHALL be added with the format: line 1 "In Progress by \<username\> at \<date and time\>", line 2 "Status changed to 'In Progress' by \<user name\>"
- **AND** the "Assigned To" dropdown SHALL remain enabled

### Requirement: Require and validate a Reason when closing
When the user selects "Closed" from the Outcome dropdown, a required "Reason" dropdown SHALL be displayed with the options "ED Visit", "Opened by mistake", and "Transfer". Submitting "Closed" without a selected Reason SHALL be prevented, with the standard required-field validation shown on the Reason field.

#### Scenario: Reason field appears for Closed
- **WHEN** a hospitalization tracker has status "Open" or "In Progress"
- **AND** the user selects "Closed" from the Outcome dropdown
- **THEN** a required "Reason" dropdown SHALL be displayed with "ED Visit", "Opened by mistake", and "Transfer"

#### Scenario: Submitting Closed without a Reason
- **WHEN** the user has selected "Closed" and left the Reason field empty
- **AND** the user submits
- **THEN** the system SHALL prevent submission
- **AND** the Reason field SHALL display the standard required-field validation

### Requirement: Transition a tracker to Closed
When the user selects "Closed" with a supported Reason ("ED Visit" or "Opened by mistake") and submits, the system SHALL update the tracker's status to "Closed", save the selected Reason, add a note recording the transition, disable all patient custom fields, disable "Assigned To", disable all hospitalization information fields, hide the Outcome dropdown, hide the Add Notes icon, close the Hospitalization Record modal, and refresh the main hospitalization bucket so the closed tracker no longer appears in the active list.

#### Scenario: Submitting Closed with a supported reason
- **WHEN** a hospitalization tracker has status "Open" or "In Progress"
- **AND** the user selects "Closed" and a Reason of "ED Visit" or "Opened by mistake", then submits
- **THEN** the hospitalization tracker status SHALL become "Closed"
- **AND** the Reason field SHALL be saved with the selected value
- **AND** a note SHALL be added with the format: line 1 "Closed by \<username\> at \<date and time\>", line 2 "Status changed to 'Closed' by \<user name\> - Reason: \<reason\>"
- **AND** the Hospitalization Record modal SHALL close
- **AND** the main hospitalization bucket SHALL refresh so the closed tracker no longer appears in the active tracker list

### Requirement: Persist and re-render the Closed state on reopen
A closed tracker's disabled/hidden state SHALL be fully re-derived from its saved status whenever it is displayed, not only immediately after the Closed transition.

#### Scenario: Reopening a closed tracker
- **WHEN** a hospitalization tracker with status "Closed" is displayed (including on reopen from a historical view)
- **THEN** all patient custom fields SHALL be disabled
- **AND** the "Assigned To" dropdown SHALL be disabled
- **AND** all hospitalization information fields SHALL be disabled
- **AND** the Outcome dropdown SHALL be hidden
- **AND** the Add Notes icon SHALL be hidden
- **AND** the saved status and Reason SHALL remain available

### Requirement: Display status-transition notes in the Notes sidebar
The Notes sidebar SHALL render each status-transition note as its two configured lines, in the order notes were added.

#### Scenario: A transition note is displayed
- **WHEN** the tracker's notes list contains one or more entries
- **THEN** each entry SHALL be rendered showing both of its configured lines

### Requirement: Keep Discharge and Transfer selectable but not actionable
"Discharge" (an Outcome option) and "Transfer" (a Reason option) SHALL remain selectable in their respective dropdowns, but submitting the outcome form while either is selected SHALL be prevented, since no transition behavior is defined for them in this capability.

#### Scenario: Discharge is selected
- **WHEN** the user selects "Discharge" from the Outcome dropdown
- **THEN** "Discharge" SHALL be selectable
- **AND** the Submit action SHALL be disabled while it is selected

#### Scenario: Transfer is selected as the closure reason
- **WHEN** the user selects "Closed" and then "Transfer" as the Reason
- **THEN** "Transfer" SHALL be selectable
- **AND** the Submit action SHALL be disabled while it is selected
- **AND** the Transfer closure workflow SHALL remain out of scope of this capability (tracked separately as AOC-3482)
