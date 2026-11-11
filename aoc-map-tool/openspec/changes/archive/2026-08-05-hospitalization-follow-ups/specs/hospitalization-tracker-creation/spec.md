## MODIFIED Requirements

### Requirement: Render the tracker header, Outcome control, and tabs
The Open-state modal body SHALL show a header with patient information, assignee, tracker ID, and basic tracker info; an "Outcome" dropdown whose options depend on the tracker's current status, driving real status transitions through the full lifecycle; a tab structure that depends on whether the tracker has ever been "In Progress"; a Notes icon in the header; the "Contacts", "Diagnosis", "Sick Plan", and "Education" tabs SHALL display real content; and the "Follow-up" tab SHALL display the tracker's follow-up records instead of an empty placeholder.

#### Scenario: Placeholder tabs render empty
- **WHEN** the user opens any tab with no built content ("Hospitalization History" or "Care Coordination")
- **THEN** the tab SHALL render as an empty placeholder

## ADDED Requirements

### Requirement: Display follow-ups in the Hospitalization Tracker
The "Follow-up" tab SHALL display a table of every follow-up record associated with the current hospitalization tracker, including those with status "Completed" or "Attempted" (unlike the Communications bucket, which excludes them), sorted by creation date in descending order with the most recently created follow-up first. The table SHALL contain the columns Follow-up Date, Assigned To, Status, and Actions, with the Actions column showing a calendar icon.

#### Scenario: All of the tracker's follow-ups are shown
- **WHEN** a hospitalization tracker contains one or more follow-up records
- **AND** the user opens the "Follow-up" tab
- **THEN** every follow-up associated with that tracker SHALL be displayed, including any "Completed" or "Attempted" ones

#### Scenario: Follow-ups are ordered newest-created-first
- **WHEN** a hospitalization tracker contains multiple follow-up records
- **THEN** they SHALL be displayed ordered by creation date descending, with the most recently created one first

### Requirement: Open a follow-up from the Hospitalization Tracker
Clicking the calendar icon in the Follow-up tab's Actions column SHALL open the corresponding Follow-up modal, following the same behavior as opening it from the Communications bucket (editability by status, outcome processing, validation, and read-only behavior for finalized follow-ups).

#### Scenario: Opening a follow-up from the tab
- **WHEN** the user clicks the calendar icon for a follow-up in the Follow-up tab
- **THEN** the Follow-up modal SHALL open, displaying the saved follow-up information
- **AND** its editability, outcome processing, and validation SHALL follow the standard Follow-up workflow
