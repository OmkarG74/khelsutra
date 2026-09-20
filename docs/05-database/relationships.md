# Database Relationships & Foreign Key Reference

## Key Relational Mappings

### 1. User & Organisation Hierarchy
- `organization_users.organization_id` → `organizations.id`
- `organization_users.user_id` → `users.id`
- `organization_users.role_id` → `roles.id`
- `role_permissions.role_id` → `roles.id`
- `role_permissions.permission_id` → `permissions.id`

### 2. Employees & Coaching
- `employees.department_id` → `departments.id`
- `coach_profiles.employee_id` → `employees.id` (1-to-1 extension)
- `team_coaches.team_id` → `teams.id`
- `team_coaches.coach_id` → `employees.id`

### 3. Athletes & Teams
- `athletes.primary_sport_id` → `sports.id`
- `athlete_sport_history.athlete_id` → `athletes.id`
- `team_members.team_id` → `teams.id`
- `team_members.athlete_id` → `athletes.id`

### 4. Venues & Bookings
- `venue_facilities.venue_id` → `venues.id`
- `venue_bookings.facility_id` → `venue_facilities.id`
- `venue_maintenance.facility_id` → `venue_facilities.id`

### 5. Tournaments & Fixtures
- `tournament_teams.tournament_id` → `tournaments.id`
- `tournament_teams.team_id` → `teams.id`
- `fixtures.tournament_id` → `tournaments.id`
- `fixtures.venue_facility_id` → `venue_facilities.id`
- `matches.fixture_id` → `fixtures.id`
