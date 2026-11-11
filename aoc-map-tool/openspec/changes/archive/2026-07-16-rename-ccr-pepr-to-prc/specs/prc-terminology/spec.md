## ADDED Requirements

### Requirement: UI terminology uses PRC / Patient Review Call
All user-facing and admin-facing UI elements (page titles, section titles, buttons, field labels, column headers, dropdown options, tooltips, modal titles, validation messages, help text) SHALL display "PRC" wherever the legacy abbreviation "CCR" or "PEPR" was previously shown, and SHALL display "Patient Review Call" wherever the legacy full name "Clinical Competency Review" or "Pre-Employment Patient Review" was previously shown.

#### Scenario: Recruiting Dashboard bucket label
- **WHEN** a user views the Recruiting Dashboard bucket previously labeled "CCRS"
- **THEN** the bucket displays "PRCS" (or the PRC-based equivalent label) instead of "CCRS"

#### Scenario: Clinical Care Dashboard section heading
- **WHEN** a user views the Clinical Care Dashboard section previously titled "Clinical Competency Reviews"
- **THEN** the section displays "Patient Review Calls" instead

#### Scenario: Tracker next-step screen title
- **WHEN** a user opens the Recruiter Tracker "Schedule Next Step" screen previously titled "Clinical Competency Review \ {Candidate}"
- **THEN** the title displays "Patient Review Call \ {Candidate}"

### Requirement: Recruiter Tracker status displays Patient Review Call
The Recruiter Tracker status previously displayed as "Clinical Competency Review" SHALL display as "Patient Review Call" for both existing records and newly created records, regardless of how the underlying value is stored.

#### Scenario: Existing record shows updated status text
- **WHEN** a Recruiter Tracker record created before this change has its status rendered
- **THEN** the displayed status text is "Patient Review Call", not "Clinical Competency Review"

#### Scenario: New record shows updated status text
- **WHEN** a new Recruiter Tracker record transitions into this status after this change
- **THEN** the displayed status text is "Patient Review Call"

### Requirement: PRC Calendar naming
The calendar previously named "CCR Calendar" SHALL be displayed as "PRC Calendar" in the navigation sidebar and on the calendar page itself.

#### Scenario: Sidebar navigation link
- **WHEN** a user with calendar access views the sidebar navigation
- **THEN** the link previously labeled "CCR Calendar" is labeled "PRC Calendar"

### Requirement: Reports and exports use PRC terminology
Any report, exported file (e.g. CSV), column header, or report title that previously displayed "CCR"/"Clinical Competency Review" or "PEPR"/"Pre-Employment Patient Review" SHALL display the PRC-based equivalent.

#### Scenario: CSV export column header
- **WHEN** a user exports Recruiter Tracker/PRC data to CSV
- **THEN** the column previously headed "CCR Request Date" is headed "PRC Request Date"

### Requirement: Notifications and emails use PRC terminology
Email and in-app notification text that previously referenced "CCR" SHALL reference "PRC" instead, without changing when or to whom the notification is sent.

#### Scenario: Scheduled reminder email
- **WHEN** the system sends the reminder email previously subjected "Scheduled CCR"
- **THEN** the email is subjected "Scheduled PRC" and its body text uses "PRC" in place of "CCR"

#### Scenario: In-app attempt notification
- **WHEN** the system creates the in-app notification previously titled "Third and final CCR attempt was made for {name}"
- **THEN** the notification is titled "Third and final PRC attempt was made for {name}"

### Requirement: Existing functionality and internal identifiers are preserved
This terminology change SHALL NOT alter route names, permission keys, database table/column names, class names, the `resources/views/ccr/` folder name, external stored-procedure parameter names, workflow behavior, business rules, or scheduling logic. It SHALL NOT alter the unrelated "PEPI"/"WEPI" terminology.

#### Scenario: Route and permission keys unchanged
- **WHEN** the terminology change is deployed
- **THEN** routes such as `ccrCalendar.calendar` and `ccrListing`, and permission keys such as `CCR Calendar`, continue to function and are not renamed at the identifier level

#### Scenario: PEPI/WEPI untouched
- **WHEN** a user views a screen showing "PEPI - Scheduled" or "WEPI - Scheduled"
- **THEN** that text is unchanged by this rename
