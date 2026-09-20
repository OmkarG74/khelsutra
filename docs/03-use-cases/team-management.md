# Use Case: Team Management

## UC-TM-01: Create Team & Assign Coaches
- **Primary Actor**: Sports Administrator.
- **Permission**: `team.manage`.
- **Main Flow**:
  1. Actor submits team name, sport_id, category_id, gender, and max_players.
  2. System persists team scoped to current `organization_id`.
  3. Actor assigns primary coach (from `employees` having `coach_profiles`).
  4. System records entry in `team_coaches` with `is_primary = TRUE`.
  5. Actor optionally assigns assistant coaches (`is_primary = FALSE`).
- **Business Rules**:
  - A team can have only one primary coach at any given time.
  - Additional coaches may be assigned as assistants.
- **Implementation Status**: Skeleton active; automated roster balancing `TODO: TO BE COMPLETED`.

## UC-TM-02: Manage Team Roster
- **Primary Actor**: Coach / Sports Administrator.
- **Permission**: `team.manage`.
- **Main Flow**: Assigns athletes to team with jersey number, position, and active status.
- **Implementation Status**: `TODO: TO BE COMPLETED`.
