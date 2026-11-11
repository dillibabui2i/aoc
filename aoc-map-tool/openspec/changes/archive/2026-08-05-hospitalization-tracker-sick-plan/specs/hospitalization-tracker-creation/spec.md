## MODIFIED Requirements

### Requirement: Render the tracker header, Outcome control, and tabs
The Open-state modal body SHALL show a header with patient information, assignee, tracker ID, and basic tracker info; an "Outcome" dropdown whose options depend on the tracker's current status, driving real status transitions through the full lifecycle; a tab structure that depends on whether the tracker has ever been "In Progress"; a Notes icon in the header; the "Contacts" and "Diagnosis" tabs SHALL display real content; and the "Sick Plan" tab SHALL display the client's Sick Plan instead of an empty placeholder.

#### Scenario: Placeholder tabs render empty
- **WHEN** the user opens any tab with no built content ("Hospitalization History", "Care Coordination", or "Education")
- **THEN** the tab SHALL render as an empty placeholder

## ADDED Requirements

### Requirement: Create or reuse a client-scoped Sick Plan when a tracker is first saved as Open
When a hospitalization tracker is saved with status "Open", the system SHALL ensure the client has a Sick Plan: creating a blank one (with the predefined Sick Plan Type rows) if none exists for that client, or reusing the existing one unchanged if one already exists. No client SHALL ever have more than one Sick Plan.

#### Scenario: First hospitalization for a client
- **WHEN** a hospitalization tracker is saved with status "Open"
- **AND** the client does not yet have a Sick Plan
- **THEN** the system SHALL create a blank Sick Plan for that client using the predefined Sick Plan Type rows

#### Scenario: Subsequent hospitalization for the same client
- **WHEN** a hospitalization tracker is saved with status "Open"
- **AND** the client already has a Sick Plan
- **THEN** the system SHALL display the existing Sick Plan
- **AND** SHALL NOT create a duplicate Sick Plan

### Requirement: Display the Sick Plan tab
The "Sick Plan" tab SHALL display a table of the client's Sick Plan items (Sick Plan Type, Completed?, Completed by, Notes, Action). An "Add Sick Plan" button SHALL be displayed only while the tracker's status is "In Progress".

#### Scenario: Sick Plan table is always shown
- **WHEN** the user opens the "Sick Plan" tab
- **THEN** the Sick Plan items table SHALL be displayed

#### Scenario: Add Sick Plan shown only while In Progress
- **WHEN** the tracker's status is "In Progress"
- **THEN** the "Add Sick Plan" button SHALL be displayed

#### Scenario: Add Sick Plan hidden otherwise
- **WHEN** the tracker's status is not "In Progress"
- **THEN** the "Add Sick Plan" button SHALL NOT be displayed

### Requirement: Editability follows the tracker's current status
Sick Plan item fields SHALL be editable only while the tracker's status is "In Progress", and read-only for any other status, including once the tracker leaves "In Progress" after being edited there.

#### Scenario: Fields become editable when the tracker becomes In Progress
- **WHEN** the tracker's status changes to "In Progress"
- **THEN** the Sick Plan item fields SHALL become editable

#### Scenario: Fields become read-only when the tracker leaves In Progress
- **WHEN** the tracker's status changes away from "In Progress" to any other status
- **THEN** the Sick Plan item fields SHALL become read-only

#### Scenario: Saved values are preserved across a status change
- **WHEN** Sick Plan values were saved while the tracker was "In Progress"
- **AND** the tracker's status subsequently changes
- **THEN** the saved values SHALL remain available and displayed in read-only mode

### Requirement: Create a Sick Plan item
When the user clicks "Add Sick Plan" (only available while the tracker is "In Progress"), the system SHALL display a "Sick Plan Details" modal for a new item. On a valid submit, the system SHALL create the item, close the modal, and display it in the Sick Plan items table.

#### Scenario: Add Sick Plan opens the modal
- **WHEN** the user clicks "Add Sick Plan"
- **THEN** the "Sick Plan Details" modal SHALL open for a new item

#### Scenario: Successfully creating a Sick Plan item
- **WHEN** the user completes the required fields and submits
- **THEN** the Sick Plan item SHALL be created
- **AND** the modal SHALL close
- **AND** the new item SHALL appear in the Sick Plan items table

### Requirement: Edit a Sick Plan item
Clicking the Action-column calendar icon for an existing Sick Plan item SHALL open the "Sick Plan Details" modal populated with that item's saved values. Editable while the tracker is "In Progress"; read-only otherwise.

#### Scenario: Editing while In Progress
- **WHEN** the tracker's status is "In Progress"
- **AND** the user clicks the calendar icon for an existing Sick Plan item
- **THEN** the "Sick Plan Details" modal SHALL open, prefilled and editable
- **AND** a Save action SHALL be available

#### Scenario: Opening read-only outside In Progress
- **WHEN** the tracker's status is not "In Progress"
- **AND** the user clicks the calendar icon for an existing Sick Plan item
- **THEN** the "Sick Plan Details" modal SHALL open in read-only mode
- **AND** no Save action SHALL be available

### Requirement: Persist Sick Plan data across reload/reopen
Sick Plan items created or updated for a client SHALL remain available, with the same values and editability matching the tracker's current status, when the tracker is reopened.

#### Scenario: Reopening a tracker with an existing Sick Plan
- **WHEN** a hospitalization tracker whose client has a Sick Plan is reopened
- **THEN** the same Sick Plan items and their saved values SHALL be displayed
- **AND** their editability SHALL match the tracker's current status
