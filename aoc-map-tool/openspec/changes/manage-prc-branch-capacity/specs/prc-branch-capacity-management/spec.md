## ADDED Requirements

### Requirement: Weekly capacity template per branch
After a branch is selected, the Manage Schedule modal SHALL show a Monday–Sunday Weekly Capacity Template. Each day allows zero or more segments, each with Start Time, End Time and Capacity per Hour.

#### Scenario: Template shown for selected branch
- **WHEN** a user with the Manage PRC Availability permission selects a branch in the Manage Schedule modal
- **THEN** seven day blocks (Monday–Sunday) show that branch's saved segments

#### Scenario: Day without segments
- **WHEN** a day has no segments
- **THEN** it shows "No segments added — {Day} is fully unavailable for this branch."

### Requirement: Whole-hour segments
Segment Start and End SHALL be whole hours between 8:00am and 5:00pm, End SHALL be later than Start, and segments on the same day SHALL NOT overlap (End exclusive).

#### Scenario: Non-hour or overlapping segment rejected
- **WHEN** a user saves a segment that isn't on the hour, falls outside 8:00am–5:00pm, has End ≤ Start, or overlaps another segment on the same day
- **THEN** the save is rejected with a message naming the day

#### Scenario: Invalid capacity rejected
- **WHEN** a user saves a segment whose capacity isn't a whole number from 1 to 99
- **THEN** the save is rejected

### Requirement: Persistence per branch
Saving SHALL replace the selected branch's full set of segments (together with its blocked availability). The configuration recurs every week until modified.

#### Scenario: Changes retained
- **WHEN** a user adds, edits or removes segments and saves
- **THEN** reopening the modal for that branch shows the updated segments, and other branches are unchanged
