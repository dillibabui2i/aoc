## ADDED Requirements

### Requirement: Map configuration saves must target the map the form was rendered for
When a user submits the Map Configuration form for a map's primary (non-layer) settings, the system SHALL verify the map being updated is the same map the form was rendered for, and SHALL reject the save if the currently selected map has changed since the form was loaded.

#### Scenario: Session-selected map changes between form load and submit
- **WHEN** a user loads the Map Configuration form for map A
- **AND** the session's selected map changes to map B before the form is submitted (e.g. via another tab or action)
- **THEN** the system SHALL reject the save
- **AND** SHALL NOT modify map B's configuration

#### Scenario: Session-selected map is unchanged
- **WHEN** a user loads the Map Configuration form for map A and submits it without the session's selected map changing
- **THEN** the system SHALL save the submitted configuration to map A
