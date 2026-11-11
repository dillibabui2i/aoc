# GP Calculator Payer Defaulting

Consolidated from `gp-calculator-auto-respite-payer` (archived). This is the single source of truth for the GP Calculator's automatic Respite toggle/Payer field defaulting; the archived change is kept for its `design.md`/decision history but no longer carries live requirements.

## Requirements

### Requirement: Evaluate payer configuration on client selection
When a user selects a client in the GP Calculator, the system SHALL evaluate the client's payer configuration to determine whether the client has a standard terminal payer, a respite payer, or both.

#### Scenario: Client data loaded after selection
- **WHEN** a client is selected in the GP Calculator
- **THEN** the system SHALL determine the presence or absence of a standard terminal payer and of one or more respite payers for that client

### Requirement: Auto-select respite payer when only a respite payer exists
When the selected client has one or more respite payers and no standard terminal payer, the system SHALL automatically set the Respite toggle to ON and populate the Payer field with the client's respite payer, without requiring additional user interaction.

#### Scenario: Client has only a respite payer
- **WHEN** a user selects a client who has only a respite payer and no standard terminal payer
- **THEN** the Respite toggle SHALL automatically be set to ON

#### Scenario: Respite payer displayed after auto-enable
- **WHEN** the Respite toggle was automatically enabled because the client has no standard terminal payer
- **THEN** the Payer field SHALL automatically display the client's respite payer
- **AND** no additional user action SHALL be required

### Requirement: Preserve default behavior when a standard terminal payer exists
When the selected client has a standard terminal payer, the system SHALL keep the Respite toggle OFF by default and populate the Payer field with the standard terminal payer, regardless of whether the client also has a respite payer.

#### Scenario: Client has both standard and respite payers
- **WHEN** a user selects a client who has both a standard terminal payer and a respite payer
- **THEN** the Respite toggle SHALL default to OFF
- **AND** the standard terminal payer SHALL populate the Payer field

#### Scenario: Client has only a standard terminal payer
- **WHEN** a user selects a client who has only a standard terminal payer
- **THEN** the Respite toggle SHALL default to OFF
- **AND** the standard terminal payer SHALL populate the Payer field

### Requirement: Re-evaluate payer defaults on client change
When a different client is selected while one is already active, the system SHALL immediately re-evaluate the payer configuration and re-apply the appropriate default Respite toggle state and Payer field value for the newly selected client.

#### Scenario: User selects a different client
- **WHEN** a client is already selected in the GP Calculator and the user selects a different client
- **THEN** the system SHALL immediately re-evaluate the payer configuration for the newly selected client
- **AND** apply the appropriate default Respite toggle and Payer field values for that client

### Requirement: Display resolved payer immediately after client data loads
The system SHALL display the appropriate payer in the Payer field as soon as the selected client's data finishes loading, without requiring further user input.

#### Scenario: Client data finishes loading
- **WHEN** the selected client's data finishes loading
- **THEN** the Payer field SHALL already display the payer determined by the client's payer configuration

### Requirement: Preserve existing GP Calculator functionality
Automatic payer defaulting SHALL NOT change existing GP Calculator calculations, validations, or other business rules.

#### Scenario: User performs GP calculations after auto-selection
- **WHEN** automatic payer selection has been applied for a selected client
- **AND** the user continues using the GP Calculator
- **THEN** all existing calculations, validations, and business rules SHALL continue to work without modification
