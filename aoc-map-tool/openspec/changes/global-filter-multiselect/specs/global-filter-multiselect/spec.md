## ADDED Requirements

### Requirement: Multi-select dropdowns for State, Region, and Branch
The Global Location Filter SHALL render its State, Region, and Branch dropdowns as multi-select controls using the same `bootstrap-multiselect` widget and interaction pattern (search box, "Select All" option) already used by the datatable filter bars' `multiselect` fields.

#### Scenario: Filter bar renders multi-select controls
- **WHEN** a user views the Global Location Filter on the account dashboard
- **THEN** the State, Region, and Branch dropdowns SHALL allow selecting more than one value
- **AND** SHALL present the same search/"Select All" affordances as `datatable-filters.blade.php`'s multiselect fields

### Requirement: Apply multiple selected values to in-scope buckets
When the user clicks "Apply to all buckets" with one or more values selected in State, Region, and/or Branch, the system SHALL apply all selected values (not just one per field) to every in-scope bucket's data request.

#### Scenario: User selects multiple values and applies
- **WHEN** a user selects more than one value in the State (or Region, or Branch) dropdown and clicks "Apply to all buckets"
- **THEN** each in-scope bucket's data request SHALL include a filter entry for every selected value of that field

### Requirement: Persist and restore multi-value selections
The Global Location Filter SHALL persist the full set of selected values per field (not a single value) across page reloads, and SHALL restore all previously selected values into the dropdowns when the page loads.

#### Scenario: Reloading the page restores multi-value selection
- **WHEN** a user has applied a multi-value selection and then reloads the page
- **THEN** the State, Region, and Branch dropdowns SHALL show the same set of previously selected values

### Requirement: Clear empties all selected values per field
Clicking "Clear" SHALL deselect all values in State, Region, and Branch, and SHALL remove the persisted multi-value selection.

#### Scenario: User clears the filter
- **WHEN** a user clicks "Clear" after selecting multiple values in one or more fields
- **THEN** all dropdowns SHALL show no selected values
- **AND** in-scope buckets SHALL no longer receive any state/region/branch filter values from the Global Location Filter

### Requirement: Local override and pre-population remain compatible with multi-value selections
Buckets that are not overridden SHALL have their own local State/Region/Branch fields pre-populated with the full set of globally selected values (not a single value), and buckets set to "Local override" SHALL remain unaffected by global selection changes, consistent with existing single-value behavior.

#### Scenario: In-scope bucket receives multi-value pre-population
- **WHEN** the Global Location Filter has multiple values selected in a field
- **AND** a bucket is in scope (not overridden)
- **THEN** that bucket's own local field for the same data SHALL reflect all of the globally selected values

#### Scenario: Overridden bucket is unaffected
- **WHEN** a bucket has been set to "Local override"
- **AND** the Global Location Filter's multi-value selection changes
- **THEN** the overridden bucket's own filter values SHALL remain unchanged until the user switches it back to follow the global filter
