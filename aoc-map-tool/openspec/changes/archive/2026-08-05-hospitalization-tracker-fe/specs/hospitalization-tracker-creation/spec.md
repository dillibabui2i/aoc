## ADDED Requirements

### Requirement: Gate tracker creation by permission
The system SHALL only display the hospitalization tracker creation entry point ("+" button on the All Hospitalizations list) and the "Hospitalization Record" modal to users holding the "Intensive Case Management" permission, evaluated client-side against existing session/permission data.

#### Scenario: User has the permission
- **WHEN** a user with the "Intensive Case Management" permission views the All Hospitalizations list
- **THEN** the "+" action SHALL be visible and enabled

#### Scenario: User lacks the permission
- **WHEN** a user without the "Intensive Case Management" permission views the application
- **THEN** the "+" action and the entire hospitalization tracker creation functionality SHALL NOT be visible

### Requirement: Open the Hospitalization Record modal
Clicking the "+" action SHALL open a modal titled "Hospitalization Record" in its initial "New" state.

#### Scenario: Modal opens in New state
- **WHEN** an authorized user clicks the "+" action
- **THEN** the "Hospitalization Record" modal SHALL open
- **AND** the status badge SHALL display "New"
- **AND** only the "Client Name" field SHALL be enabled; all other fields SHALL be disabled

### Requirement: Search existing clients by name with ID disambiguation
The Client Name field SHALL provide autocomplete search over existing clients only. Matches SHALL be displayed as "{Full Name}, ID {Client ID}" so that clients sharing the same name are distinguishable, and only a list item may be selected — free text SHALL NOT be accepted as a valid selection.

#### Scenario: Typing shows matching clients
- **WHEN** a user types one or more characters into "Client Name"
- **THEN** the system SHALL display existing clients whose name matches, each shown with its client ID

#### Scenario: Two clients share the same name
- **WHEN** the autocomplete results include two or more clients with an identical full name
- **THEN** each SHALL be listed as a separate, individually selectable entry distinguished by its client ID
- **AND** selecting one SHALL resolve to that specific client's ID, not merely its displayed name

#### Scenario: Free text is rejected
- **WHEN** a user types a value that does not match, or leaves the field without selecting a listed client
- **THEN** the system SHALL prevent proceeding to save or continue with that value

### Requirement: Check for an active tracker on client selection
When a valid client is selected, the system SHALL check for existing hospitalization trackers for that client. A tracker is considered active when its status is any value other than "Completed" or "Closed".

#### Scenario: Active-tracker check runs on selection
- **WHEN** a user selects a valid client from the autocomplete list
- **THEN** the system SHALL evaluate that client's hospitalization trackers to determine whether an active one exists

### Requirement: Auto-fill and enable fields when no active tracker exists
When the selected client has no active tracker, the system SHALL auto-populate the applicable read-only fields from the client record, enable "Assigned To" (pre-filled with a default assignee, user-editable) and the client-profile custom fields (High Utilizer?, Backup G-tube?, Backup trach?, Level, Backup G-tube/trach last updated), and enable the Submit action.

#### Scenario: No active tracker found
- **WHEN** the active-tracker check for the selected client finds no active tracker
- **THEN** Client ID, Date of Birth, Age, Medicaid ID, Client Status KT, Terminal Payer, Branch, Auth. hours per week, and DME Provider SHALL be auto-populated and shown read-only
- **AND** Assigned To and the custom fields SHALL become enabled/interactive, with applicable values pre-filled from the client profile
- **AND** the Submit action SHALL become enabled

### Requirement: Present a conflict resolution modal when an active tracker exists
When the selected client already has an active tracker, the system SHALL display a conflict modal offering exactly two options: "Open the active tracker" and "Search another client". It SHALL NOT allow a second active tracker to be created for that client while the conflict is unresolved.

#### Scenario: Active tracker found
- **WHEN** the active-tracker check for the selected client finds an active tracker
- **THEN** the system SHALL display a modal with the options "Open the active tracker" and "Search another client"
- **AND** the tracker creation form SHALL NOT be submittable for that client while this modal is shown

#### Scenario: User opens the active tracker
- **WHEN** the user selects "Open the active tracker" from the conflict modal
- **THEN** the new-tracker creation flow SHALL be canceled
- **AND** the existing active tracker SHALL be opened by its ID

#### Scenario: User searches another client
- **WHEN** the user selects "Search another client" from the conflict modal
- **THEN** the system SHALL return to the Client Name search step with the previous selection cleared

### Requirement: Create the tracker as Open
When the user submits a valid, conflict-free client selection, the system SHALL create a new hospitalization tracker with status "Open".

#### Scenario: Successful submission
- **WHEN** a user submits the tracker with a validly selected client and no active-tracker conflict
- **THEN** a new tracker SHALL be created
- **AND** its status SHALL become "Open"

### Requirement: Render the Open-state modal title, status, and icons
Once a tracker is saved, the modal title SHALL show the modal title text, patient name, and patient level; the status badge SHALL show the current status on the right; and a Person icon (opens the Client Profile) and a Notes icon (visual placeholder, no wired action) SHALL be displayed alongside the title.

#### Scenario: Title reflects the saved tracker
- **WHEN** a hospitalization tracker has been saved
- **THEN** the title area SHALL display the modal title, the patient's name, and the patient's level
- **AND** the status badge on the right SHALL show the tracker's current status
- **AND** a Person icon and a Notes icon SHALL be displayed next to the title

#### Scenario: Person icon opens the Client Profile
- **WHEN** the user clicks the Person icon
- **THEN** the corresponding Client Profile SHALL open

#### Scenario: Notes icon has no action yet
- **WHEN** the user clicks the Notes icon
- **THEN** no action SHALL occur (implemented by a separate story)

### Requirement: Render the tracker header, Outcome control, and tabs
The Open-state modal body SHALL show a header with patient information, assignee, tracker ID, and basic tracker info; an "Outcome" dropdown (options "In Progress"/"Closed") with a Submit button (no wired logic); and four tabs — "Current Hospitalization Info" (editable), "Hospitalization History", "Sick Plan", and "Follow-up" (the latter three rendered as empty placeholders).

#### Scenario: Header displays tracker basics
- **WHEN** the Open-state modal is rendered
- **THEN** the header SHALL display patient information, assignee, tracker ID, and basic tracker info

#### Scenario: Outcome control is inert
- **WHEN** the Open-state modal is rendered
- **THEN** an Outcome dropdown showing "In Progress" and "Closed" options and a Submit button SHALL be displayed
- **AND** selecting an option or clicking Submit SHALL have no wired business effect

#### Scenario: Placeholder tabs render empty
- **WHEN** the user opens "Hospitalization History", "Sick Plan", or "Follow-up"
- **THEN** the tab SHALL render as an empty placeholder

### Requirement: Edit Current Hospitalization Info with auto-save
Fields on the "Current Hospitalization Info" tab that are not flagged for logging SHALL save automatically when their value changes, with no additional user action required.

#### Scenario: Editing a non-logged field
- **WHEN** the user changes the value of a field not marked for logging
- **THEN** the new value SHALL be saved automatically without further user action

### Requirement: Edit logged fields via floating Save/Cancel
Fields flagged as requiring logging SHALL NOT save on change alone. Instead, changing such a field SHALL display floating "Save" and "Cancel" buttons; the new value SHALL remain unsaved until the user explicitly chooses one.

#### Scenario: Editing a logged field
- **WHEN** the user changes the value of a field marked for logging
- **THEN** floating "Save" and "Cancel" buttons SHALL be displayed
- **AND** the previously saved value SHALL remain in effect until the user chooses Save or Cancel

#### Scenario: Canceling a logged field edit
- **WHEN** the user clicks the floating "Cancel" button
- **THEN** the field SHALL revert to its original value
- **AND** the edit SHALL be discarded

#### Scenario: Saving a logged field edit
- **WHEN** the user clicks the floating "Save" button
- **THEN** the new value SHALL be persisted
- **AND** no Activity Log entry SHALL be created as part of this action (out of scope)

### Requirement: Display the Notes sidebar shell
The Open-state modal SHALL display a Notes sidebar on the right side, built to support future display of status-transition notes, user-created notes, and field-editing activity entries, without itself creating or showing note content in this change.

#### Scenario: Notes sidebar is present
- **WHEN** a hospitalization tracker has been saved
- **THEN** a Notes sidebar SHALL be displayed on the right side of the modal
- **AND** it SHALL render an empty state, ready to display note/activity entries once populated by future stories

### Requirement: Persist tracker data across reload/reopen
Once created, the tracker's client information, status, field values, and interface structure SHALL remain available when the tracker is reopened, backed by the mocked fetch-by-id service in this change.

#### Scenario: Reopening a previously created tracker
- **WHEN** a user reopens a tracker that was previously created (e.g. via "Open the active tracker" or navigating back to it)
- **THEN** the same client information, tracker status, field values, and tab/header/notes structure SHALL be displayed as when it was created or last edited
