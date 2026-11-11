# PRC Scheduling

Consolidated from `schedule-next-step-prc-integration` (archived). This is the single source of truth for PRC (Patient Review Call) scheduling integration into the Schedule Next Step form; the archived change is kept for its `design.md`/decision history but no longer carries live requirements.

## Requirements

### Requirement: No standalone PRC/CCR scheduling option
The Schedule Next Step form (both the new-clinician flow and the existing-tracker
flow) SHALL NOT offer "CCR"/"PEPR"/"PRC" as a selectable Interview Type. PRC
scheduling SHALL only occur as part of scheduling a qualifying next step.

#### Scenario: Interview Type list has no standalone PRC option
- **WHEN** a recruiter opens the Interview Type dropdown on the Schedule Next Step
  form, from either the new-clinician flow or an existing tracker
- **THEN** no option representing PRC/CCR/PEPR as its own interview type is present

### Requirement: PRC scheduling fields shown for qualifying next steps
When the recruiter selects PEPI, WEPI, Training Shift, or Candidate Start as the
next step, the form SHALL display a PRC scheduling block containing a PRC Date and
a PRC Time field, both required.

#### Scenario: Selecting a qualifying next step shows the PRC block
- **WHEN** the recruiter selects "PEPI", "WEPI", "Training Shift", or
  "Candidate Start" as the next step
- **THEN** a PRC block appears with a required PRC Date field and a required PRC
  Time field

#### Scenario: Selecting a non-qualifying next step hides the PRC block
- **WHEN** the recruiter selects a next step other than PEPI, WEPI, Training Shift,
  or Candidate Start (including no selection)
- **THEN** the PRC block is not shown and its fields are not required

### Requirement: IPEA is exempt from PRC scheduling
When the recruiter selects IPEA as the next step, the next step scheduling fields
SHALL remain visible and the PRC block SHALL NOT be shown.

#### Scenario: Selecting IPEA never shows the PRC block
- **WHEN** the recruiter selects "IPEA" as the next step
- **THEN** the Next Step Date and Time fields remain visible and enabled
- **AND** the PRC block is not shown

### Requirement: PRC fields are locked until the next step time is known
The PRC Date and PRC Time fields SHALL be disabled until both the Next Step Date and
Next Step Time have been entered. Clearing either Next Step field SHALL re-disable
and clear the PRC fields.

#### Scenario: PRC fields start disabled
- **WHEN** the recruiter selects a qualifying next step before entering a Next Step
  Date or Time
- **THEN** the PRC Date and PRC Time fields are disabled

#### Scenario: PRC fields unlock once next step date and time are set
- **WHEN** the recruiter has entered both a Next Step Date and a Next Step Time
- **THEN** the PRC Date and PRC Time fields become enabled

#### Scenario: Clearing next step date or time re-locks PRC fields
- **WHEN** the recruiter clears the Next Step Date or Next Step Time after having
  entered a PRC Date/Time
- **THEN** the PRC Date and PRC Time fields become disabled and are cleared

### Requirement: PRC must occur strictly before the next step
The PRC Date/Time SHALL be strictly earlier than the Next Step Date/Time. The same
instant as the next step, or any later instant, SHALL be rejected.

#### Scenario: PRC date/time before next step is accepted
- **WHEN** the recruiter enters a PRC Date/Time earlier than the Next Step Date/Time
- **THEN** the value is accepted

#### Scenario: PRC date/time equal to next step is rejected
- **WHEN** the recruiter enters a PRC Date/Time exactly equal to the Next Step
  Date/Time
- **THEN** the selection is blocked or, if submitted, the save is rejected

#### Scenario: PRC date/time after next step is rejected
- **WHEN** the recruiter enters a PRC Date/Time later than the Next Step Date/Time
- **THEN** the selection is blocked or, if submitted, the save is rejected

### Requirement: PRC Date/Time are required when the PRC block is shown
When the PRC block is shown, the form SHALL NOT save until both PRC Date and PRC
Time have valid values.

#### Scenario: Save blocked when PRC Date is missing
- **WHEN** the recruiter attempts to save with a qualifying next step selected and
  PRC Date blank
- **THEN** the save is prevented and a required-field validation message is shown
  for PRC Date

#### Scenario: Save blocked when PRC Time is missing
- **WHEN** the recruiter attempts to save with a qualifying next step selected and
  PRC Time blank
- **THEN** the save is prevented and a required-field validation message is shown
  for PRC Time

### Requirement: Successful save creates a PRC record and updates tracker status
When a qualifying next step and a valid PRC Date/Time are saved together, the system
SHALL create a PRC schedule record using the submitted PRC Date/Time (not a guessed
date), assign the record to a Clinical Case Manager, and set the Recruiter Tracker
status to display as "Patient Review Call".

#### Scenario: Save creates a PRC record with the submitted date/time
- **WHEN** the recruiter saves a qualifying next step with valid PRC Date and Time
- **THEN** a PRC schedule record is created using the submitted PRC Date and Time
- **AND** the record is assigned to a Clinical Case Manager

#### Scenario: Tracker status reflects Patient Review Call after save
- **WHEN** the save in the previous scenario completes
- **THEN** the Recruiter Tracker's displayed status is "Patient Review Call"

### Requirement: PRC record is visible in existing PRC bucket and calendar
A PRC record created via this flow SHALL appear in the Recruiting PRC bucket and the
PRC Calendar, using the same downstream views that already display PRC records
created any other way.

#### Scenario: New PRC record appears in the PRC bucket
- **WHEN** a PRC record is created via the Schedule Next Step form
- **THEN** the record appears in the Recruiting PRC bucket listing

#### Scenario: New PRC record appears on the PRC Calendar
- **WHEN** a PRC record is created via the Schedule Next Step form
- **THEN** the record appears on the PRC Calendar

### Requirement: Behavior applies from any Recruiting bucket
The PRC scheduling behavior defined above SHALL apply identically regardless of
which Recruiting bucket the Schedule Next Step form was opened from, and regardless
of whether the form is the new-clinician flow or the existing-tracker flow.

#### Scenario: Same rules apply from a different Recruiting bucket
- **WHEN** the recruiter opens the Schedule Next Step form from any Recruiting
  bucket and selects a qualifying next step
- **THEN** the PRC scheduling fields, locking, validation, and save behavior defined
  above apply the same as from any other bucket

### Requirement: Existing next-step scheduling behavior is otherwise unchanged
Aside from the PRC integration described above, all existing Next Step scheduling
behavior (outcomes, non-qualifying schedule types, IPEA-specific fields, proposed-
next-step bridging) SHALL remain unchanged.

#### Scenario: Non-PRC next-step scheduling is unaffected
- **WHEN** the recruiter schedules a next step that does not require PRC (e.g. IPEA,
  School Set Up, Needs to be Scheduled by RA)
- **THEN** the scheduling behavior for that next step is identical to before this
  change
