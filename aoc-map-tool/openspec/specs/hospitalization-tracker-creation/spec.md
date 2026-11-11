# Hospitalization Tracker Creation

Consolidated from `hospitalization-tracker-fe`, `hospitalization-tracker-outcome`, `hospitalization-tracker-discharge-lifecycle`, `hospitalization-tracker-contacts`, `hospitalization-tracker-notes-activity-log`, `hospitalization-tracker-diagnosis`, `hospitalization-tracker-sick-plan`, `hospitalization-tracker-education`, and `hospitalization-follow-ups` (archived). This is the single source of truth for the "Hospitalization Record" modal capability; the archived changes are kept for their `design.md`/decision history but no longer carry live requirements.

All of this capability is frontend + mock data only (`resources/js/vue/modules/vbc`) — no backend (`app/VBC/**`, routes) changes exist for it yet.

## Requirements

### Requirement: Gate tracker creation by permission
The system SHALL only display the hospitalization tracker creation entry point ("+" button on the All Hospitalizations list) and the "Hospitalization Record" modal to users holding the "Intensive Case Management" permission, evaluated client-side against existing session/permission data. Every other requirement in this capability (Contacts, Diagnosis, Sick Plan, Education, Notes) inherits this same gate, since it all lives inside this one modal — no additional permission checks exist per-tab.

#### Scenario: User has the permission
- **WHEN** a user with the "Intensive Case Management" permission views the All Hospitalizations list
- **THEN** the "+" action SHALL be visible and enabled

#### Scenario: User lacks the permission
- **WHEN** a user without the "Intensive Case Management" permission views the application
- **THEN** the "+" action and the entire hospitalization tracker creation functionality (including Notes, Contacts, Diagnosis, Sick Plan, and Education) SHALL NOT be visible

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
When the user submits a valid, conflict-free client selection, the system SHALL create a new hospitalization tracker with status "Open". Creating the tracker also ensures the client has a Sick Plan (see "Create or reuse a client-scoped Sick Plan").

#### Scenario: Successful submission
- **WHEN** a user submits the tracker with a validly selected client and no active-tracker conflict
- **THEN** a new tracker SHALL be created
- **AND** its status SHALL become "Open"

### Requirement: Render the Open-state modal title, status, and icons
Once a tracker is saved, the modal title SHALL show the modal title text, patient name, and patient level; the status badge SHALL show the current status on the right; a Person icon SHALL always be displayed and, when clicked, SHALL open the corresponding Client Profile; and a Notes icon SHALL be displayed and clickable whenever the tracker's status is neither "Closed" nor "Completed" (hidden for those two statuses), opening the "Create Notes" modal for that tracker when clicked.

#### Scenario: Title reflects the saved tracker
- **WHEN** a hospitalization tracker has been saved
- **THEN** the title area SHALL display the modal title, the patient's name, and the patient's level
- **AND** the status badge on the right SHALL show the tracker's current status

#### Scenario: Person icon opens the Client Profile
- **WHEN** the user clicks the Person icon
- **THEN** the corresponding Client Profile SHALL open

#### Scenario: Notes icon available in the tracker header
- **WHEN** a hospitalization tracker's status is neither "Closed" nor "Completed"
- **THEN** the Notes icon SHALL be displayed and clickable, opening the "Create Notes" modal for that tracker

#### Scenario: Notes icon hidden once Closed or Completed
- **WHEN** a hospitalization tracker's status is "Closed" or "Completed"
- **THEN** the Notes icon SHALL NOT be displayed in the tracker header

### Requirement: Render the tracker header, Outcome control, and tabs
The Open-state modal body SHALL show a header with patient information, assignee, tracker ID, and basic tracker info; an "Outcome" dropdown whose options depend on the tracker's current status, driving real status transitions through the full lifecycle (Open → In Progress → Discharged → Ready for Review → Ready for Next Steps → Completed, or Open/In Progress → Closed); and a tab structure that depends on whether the tracker has ever been "In Progress". Once real content exists, the "Contacts", "Diagnosis", "Sick Plan", "Education", and "Follow-up" tabs SHALL display it; "Hospitalization History" and "Care Coordination" remain empty placeholders.

#### Scenario: Header displays tracker basics
- **WHEN** the Open-state modal is rendered
- **THEN** the header SHALL display patient information, assignee, tracker ID, and basic tracker info

#### Scenario: Outcome options for an Open tracker
- **WHEN** a hospitalization tracker has status "Open" and the Open-state modal is rendered
- **THEN** the Outcome dropdown SHALL display "In Progress" and "Closed"

#### Scenario: Outcome options for an In Progress tracker
- **WHEN** a hospitalization tracker has status "In Progress" and the Open-state modal is rendered
- **THEN** the Outcome dropdown SHALL display "Discharge" and "Closed"

#### Scenario: Outcome options for a Discharged tracker
- **WHEN** a hospitalization tracker has status "Discharged" and the Open-state modal is rendered
- **THEN** the Outcome dropdown SHALL display "Ready for Review"

#### Scenario: Outcome options for a Ready for Review tracker
- **WHEN** a hospitalization tracker has status "Ready for Review" and the Open-state modal is rendered
- **THEN** the Outcome dropdown SHALL display "Ready for Next Steps"

#### Scenario: Outcome options for a Ready for Next Steps tracker
- **WHEN** a hospitalization tracker has status "Ready for Next Steps" and the Open-state modal is rendered
- **THEN** the Outcome dropdown SHALL display "Completed"

#### Scenario: Outcome control is hidden once Closed
- **WHEN** a hospitalization tracker has status "Closed"
- **THEN** the Outcome dropdown SHALL NOT be displayed

#### Scenario: Outcome control is hidden once Completed
- **WHEN** a hospitalization tracker has status "Completed"
- **THEN** the Outcome dropdown SHALL NOT be displayed

#### Scenario: Flat tabs before any In Progress transition
- **WHEN** a hospitalization tracker has status "Open", or has status "Closed" without ever having been "In Progress"
- **THEN** the tabs SHALL render as: "Current Hospitalization Info", "Hospitalization History", "Sick Plan", "Follow-up"

#### Scenario: Nested tabs once In Progress
- **WHEN** a hospitalization tracker has status "In Progress", or has status "Closed" after having been "In Progress"
- **THEN** the tabs SHALL render in the order: "Hospitalization Info" (containing "Current Hospitalization Info" and "Hospitalization History" as sub-tabs), "Contacts", "Diagnosis", "Sick Plan", "Care Coordination", "Education", "Follow-up"

#### Scenario: Placeholder tabs render empty
- **WHEN** the user opens any tab with no built content ("Hospitalization History" or "Care Coordination")
- **THEN** the tab SHALL render as an empty placeholder

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
When the user selects "Closed" with a supported Reason ("ED Visit" or "Opened by mistake") and submits, the system SHALL update the tracker's status to "Closed", save the selected Reason, add a note recording the transition, disable all patient custom fields, disable "Assigned To", disable all hospitalization information fields, hide the Outcome dropdown, hide the Notes icon, close the Hospitalization Record modal, and refresh the main hospitalization bucket so the closed tracker no longer appears in the active list.

#### Scenario: Submitting Closed with a supported reason
- **WHEN** a hospitalization tracker has status "Open" or "In Progress"
- **AND** the user selects "Closed" and a Reason of "ED Visit" or "Opened by mistake", then submits
- **THEN** the hospitalization tracker status SHALL become "Closed"
- **AND** the Reason field SHALL be saved with the selected value
- **AND** a note SHALL be added with the format: line 1 "Closed by \<username\> at \<date and time\>", line 2 "Status changed to 'Closed' by \<user name\> - Reason: \<reason\>"
- **AND** the Hospitalization Record modal SHALL close
- **AND** the main hospitalization bucket SHALL refresh so the closed tracker no longer appears in the active tracker list

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

### Requirement: Persist and re-render terminal states (Closed/Completed) on reopen
A tracker's terminal-state (Closed or Completed) disabled/hidden state SHALL be fully re-derived from its saved status whenever it is displayed, not only immediately after the transition.

#### Scenario: Reopening a closed tracker
- **WHEN** a hospitalization tracker with status "Closed" is displayed (including on reopen from a historical view)
- **THEN** all patient custom fields SHALL be disabled
- **AND** the "Assigned To" dropdown SHALL be disabled
- **AND** all hospitalization information fields SHALL be disabled
- **AND** the Outcome dropdown SHALL be hidden
- **AND** the Notes icon SHALL be hidden
- **AND** the saved status and Reason SHALL remain available

#### Scenario: Reopening a Completed tracker
- **WHEN** a hospitalization tracker with status "Completed" is displayed (including on reopen from a historical view)
- **THEN** all patient custom fields SHALL be disabled
- **AND** the "Assigned To" dropdown SHALL be disabled
- **AND** all hospitalization information fields SHALL be disabled
- **AND** the Outcome dropdown SHALL be hidden
- **AND** the saved status SHALL remain available

### Requirement: Keep Transfer selectable but not actionable
"Transfer" (a Reason option under the "Closed" outcome) SHALL remain selectable, but submitting the outcome form while it is selected SHALL be prevented, since no transition behavior is defined for it in this capability.

#### Scenario: Transfer is selected as the closure reason
- **WHEN** the user selects "Closed" and then "Transfer" as the Reason
- **THEN** "Transfer" SHALL be selectable
- **AND** the Submit action SHALL be disabled while it is selected
- **AND** the Transfer closure workflow SHALL remain out of scope of this capability (tracked separately as AOC-3482)

### Requirement: Edit Current Hospitalization Info with auto-save
Fields on the "Current Hospitalization Info" tab that are not flagged for logging SHALL save automatically when their value changes, with no additional user action required.

#### Scenario: Editing a non-logged field
- **WHEN** the user changes the value of a field not marked for logging
- **THEN** the new value SHALL be saved automatically without further user action

### Requirement: Edit logged fields via floating Save/Cancel
Fields flagged as requiring logging SHALL NOT save on change alone. Instead, changing such a field SHALL display floating "Save" and "Cancel" buttons; the new value SHALL remain unsaved until the user explicitly chooses one. Saving a logged field also generates an Activity Log entry (see "Generate an Activity Log entry when a logged field is saved").

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
- **AND** an Activity Log entry SHALL be created recording the change

### Requirement: Display the Notes sidebar
The Open-state modal SHALL display a Notes sidebar on the right side. It SHALL render an empty state when there are no entries, or, when entries exist, render status-transition notes, user-created notes, and field-editing Activity Log entries together in a single timeline, sorted with the most recently created entry first; this ordering SHALL be preserved across reload/reopen.

#### Scenario: Notes sidebar is present with no entries
- **WHEN** a hospitalization tracker has been saved and has no notes or Activity Log entries
- **THEN** a Notes sidebar SHALL be displayed on the right side of the modal, rendering an empty state

#### Scenario: A transition note is displayed
- **WHEN** the tracker's notes list contains one or more entries
- **THEN** each entry SHALL be rendered showing both of its configured lines

#### Scenario: Mixed entries sorted newest first
- **WHEN** the right-side panel contains a mix of notes, Activity Log entries, and status-change notes
- **THEN** they SHALL all be displayed together, sorted with the most recently created entry at the top

#### Scenario: Ordering persists across reopen
- **WHEN** a hospitalization tracker with existing notes and Activity Log entries is reopened
- **THEN** all entries SHALL remain available with the same chronological ordering

### Requirement: Display the Notes icon in the hospitalization bucket
A hospitalization tracker displayed in a hospitalization bucket SHALL show a Notes icon in its Action column whenever its status is neither "Closed" nor "Completed".

#### Scenario: Notes icon shown for an eligible tracker row
- **WHEN** a hospitalization tracker row's status is neither "Closed" nor "Completed"
- **THEN** the Notes icon SHALL be displayed in that row's Action column

#### Scenario: Notes icon hidden for a Closed or Completed tracker row
- **WHEN** a hospitalization tracker row's status is "Closed" or "Completed"
- **THEN** the Notes icon SHALL NOT be displayed in that row's Action column

### Requirement: Open the Create Notes modal
Clicking the Notes icon, from either the bucket's Action column or the tracker header, SHALL open the "Create Notes" modal for that tracker. The modal SHALL contain a single Notes field and no user-tagging control.

#### Scenario: Opening from the bucket
- **WHEN** the user clicks the Notes icon in the bucket's Action column for an eligible tracker
- **THEN** the "Create Notes" modal SHALL open for that tracker

#### Scenario: Opening from the tracker header
- **WHEN** the user clicks the Notes icon in the tracker header of an eligible tracker
- **THEN** the "Create Notes" modal SHALL open for that tracker

### Requirement: Validate and submit a note
Submitting the "Create Notes" modal with a blank Notes field SHALL be prevented, and the Notes field SHALL be shown with a red border. Submitting with content SHALL create the note, close the modal, and display the note in the tracker's right-side panel.

#### Scenario: Submitting a blank note
- **WHEN** the "Create Notes" modal is open
- **AND** the user submits without entering any content
- **THEN** the system SHALL prevent submission
- **AND** the Notes field SHALL be shown with a red border
- **AND** the modal SHALL remain open

#### Scenario: Submitting a valid note
- **WHEN** the "Create Notes" modal is open
- **AND** the user enters note content and submits
- **THEN** the note SHALL be saved and associated with the tracker
- **AND** the modal SHALL close
- **AND** the note SHALL appear in the tracker's right-side panel

### Requirement: Generate an Activity Log entry when a logged field is saved
When the user saves a change to a field configured as "logged" (`logUpdates: true`), the system SHALL create one Activity Log entry recording the field name, old value, new value, the user who made the change, and the date/time of the change. Fields not configured as logged SHALL NOT generate an Activity Log entry. Saving the same field again SHALL create an additional entry without altering or removing prior entries.

#### Scenario: Saving a logged field creates one entry
- **WHEN** the user changes a field configured as "logged" and saves it
- **THEN** the system SHALL create exactly one Activity Log entry for that save
- **AND** the entry SHALL record the field name, old value, new value, user name, and date/time

#### Scenario: Saving a non-logged field creates no entry
- **WHEN** the user changes a field not configured as "logged" and it is saved
- **THEN** no Activity Log entry SHALL be created

#### Scenario: Repeated saves preserve history
- **WHEN** a logged field that already has one or more Activity Log entries is saved again
- **THEN** a new Activity Log entry SHALL be created
- **AND** all previously existing Activity Log entries SHALL remain unchanged

### Requirement: Display Activity Log entries using the standard format
Each Activity Log entry displayed in the right-side panel SHALL use the format: "Field edited by \<user name\> - \<date and time\>" on the first line, followed by "Field Name: \<field name\>", "Old Value: \<old value\>", and "New Value: \<new value\>" on subsequent lines.

#### Scenario: Activity Log entry rendering
- **WHEN** an Activity Log entry is displayed in the right-side panel
- **THEN** it SHALL show "Field edited by \<user name\> - \<date and time\>" as its first line
- **AND** "Field Name: \<field name\>", "Old Value: \<old value\>", and "New Value: \<new value\>" as its following lines

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

### Requirement: Sick Plan item editability follows the tracker's current status
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
Clicking the calendar icon in the Follow-up tab's Actions column SHALL open the corresponding Follow-up modal, following the same behavior as opening it from the Communications bucket (editability by status, outcome processing, validation, and read-only behavior for finalized follow-ups). See the `hospitalization-follow-ups` capability for the full Follow-up workflow.

#### Scenario: Opening a follow-up from the tab
- **WHEN** the user clicks the calendar icon for a follow-up in the Follow-up tab
- **THEN** the Follow-up modal SHALL open, displaying the saved follow-up information
- **AND** its editability, outcome processing, and validation SHALL follow the standard Follow-up workflow
