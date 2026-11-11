## MODIFIED Requirements

### Requirement: Render the tracker header, Outcome control, and tabs
The Open-state modal body SHALL show a header with patient information, assignee, tracker ID, and basic tracker info; an "Outcome" dropdown whose options depend on the tracker's current status, driving real status transitions through the full lifecycle; and a tab structure that depends on whether the tracker has ever been "In Progress". The "Contacts" tab SHALL display real contact management content instead of an empty placeholder.

#### Scenario: Placeholder tabs render empty
- **WHEN** the user opens any tab with no built content ("Hospitalization History", "Sick Plan", "Follow-up", "Diagnosis", "Care Coordination", or "Education")
- **THEN** the tab SHALL render as an empty placeholder

## ADDED Requirements

### Requirement: Display the Contacts tab for an In Progress tracker
When a hospitalization tracker has status "In Progress", the "Contacts" tab SHALL display a table of the tracker's contacts and an "Add Contact" action.

#### Scenario: Contacts tab shows the table and Add Contact action
- **WHEN** a hospitalization tracker has status "In Progress"
- **AND** the user opens the "Contacts" tab
- **THEN** a Contacts table SHALL be displayed
- **AND** an "Add Contact" button SHALL be displayed

### Requirement: Create a contact record
When the user clicks "Add Contact" on an In Progress tracker, the system SHALL display a "Contact Details" modal for a new contact. Submitting without completing all required fields SHALL be prevented, with the missing fields shown with a red border, and the modal SHALL remain open. On a valid submit, the system SHALL create the contact, associate it only with the current hospitalization tracker, close the modal, and display the new contact in the Contacts table.

#### Scenario: Add Contact opens the Contact Details modal
- **WHEN** the user clicks "Add Contact" on an In Progress tracker
- **THEN** the "Contact Details" modal SHALL open for a new contact

#### Scenario: Submitting an incomplete new contact
- **WHEN** the "Contact Details" modal is open for a new contact
- **AND** the user submits without completing all required fields
- **THEN** the system SHALL prevent submission
- **AND** the missing required fields SHALL be shown with a red border
- **AND** the modal SHALL remain open

#### Scenario: Successfully creating a contact
- **WHEN** the user completes all required fields and submits
- **THEN** the contact SHALL be created and associated only with the current hospitalization tracker
- **AND** the modal SHALL close
- **AND** the new contact SHALL appear in the Contacts table

### Requirement: Edit a contact record
When the user clicks the Action-column calendar icon for an existing contact on an In Progress tracker, the system SHALL display the "Contact Details" modal prefilled with that contact's saved values. Submitting without completing all required fields SHALL be prevented, with the missing fields shown with a red border, and the modal SHALL remain open. On a valid submit, the system SHALL update the contact, close the modal, and display the updated values in the Contacts table.

#### Scenario: Editing opens the modal with existing values
- **WHEN** a hospitalization tracker has status "In Progress"
- **AND** the user clicks the calendar icon for an existing contact
- **THEN** the "Contact Details" modal SHALL open prefilled with that contact's current saved values

#### Scenario: Submitting an incomplete edit
- **WHEN** the "Contact Details" modal is open for an existing contact
- **AND** the user clears a required field and submits
- **THEN** the system SHALL prevent submission
- **AND** the missing required fields SHALL be shown with a red border
- **AND** the modal SHALL remain open

#### Scenario: Successfully editing a contact
- **WHEN** the user changes one or more fields to valid values and submits
- **THEN** the contact SHALL be updated
- **AND** the modal SHALL close
- **AND** the updated values SHALL appear in the Contacts table

### Requirement: Display contacts read-only once the tracker leaves In Progress
Once a hospitalization tracker's status is anything other than "In Progress", the Contacts tab SHALL display saved contacts in read-only mode: the "Add Contact" action SHALL NOT be available, and opening an existing contact SHALL show its values with no editable fields and no Save/Submit action. Contacts already saved SHALL remain available and unchanged.

#### Scenario: Add Contact is hidden
- **WHEN** a hospitalization tracker's status is not "In Progress"
- **AND** the user opens the "Contacts" tab
- **THEN** the "Add Contact" button SHALL NOT be available

#### Scenario: Opening a contact shows it read-only
- **WHEN** a hospitalization tracker's status is not "In Progress"
- **AND** the user clicks the calendar icon for an existing contact
- **THEN** the "Contact Details" modal SHALL open in read-only mode
- **AND** all contact fields SHALL be read-only
- **AND** no Save or Submit action SHALL be available

#### Scenario: Saved contacts persist across status changes
- **WHEN** one or more contacts were created while the tracker was "In Progress"
- **AND** the tracker's status subsequently changes
- **THEN** all saved contacts SHALL remain available in the Contacts table with their saved values unchanged

### Requirement: Persist contact data across reload/reopen
Contact records created or updated for a tracker SHALL remain available when the tracker is reopened, backed by the mocked service in this change.

#### Scenario: Reopening a tracker with saved contacts
- **WHEN** a hospitalization tracker with previously saved contacts is reopened
- **THEN** the same contacts and their saved values SHALL be displayed in the Contacts table
