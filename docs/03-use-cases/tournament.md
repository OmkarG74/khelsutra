# Use Case: Tournament & Fixtures

## UC-TOURN-01: Create & Organize Tournament
- **Primary Actor**: Sports Administrator / Venue & Tournament Manager.
- **Permission**: `tournament.manage`.
- **Main Flow**:
  1. Actor submits tournament name, sport_id, level_id (School, District, State, National, International), and format_id (Knockout, League, Round Robin, Group+Knockout).
  2. Submits start date, end date, entry fee, and rules.
  3. Registers teams in `tournament_teams`.
  4. System generates fixtures and assigns facilities.
- **Business Rules**:
  - Match statistics are high-level team scores for initial phase.
  - Player-level detailed statistics are explicitly out of initial scope.
- **Implementation Status**: Skeleton active; bracket generator engine `TODO: TO BE COMPLETED`.

## UC-TOURN-02: Record Match Result
- **Primary Actor**: Venue & Tournament Manager / Match Official.
- **Permission**: `fixture.manage`.
- **Main Flow**: Records team scores, winner, player of match, and updates tournament standings points table.
- **Implementation Status**: `TODO: TO BE COMPLETED`.
