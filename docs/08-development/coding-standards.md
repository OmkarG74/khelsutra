# Coding Standards — KhelSutra

All code contributed to KhelSutra must strictly follow these rules:

## 1. PHP & Laravel Standards
- Strictly adhere to **PSR-12** and Laravel conventions.
- Class names: `PascalCase` (e.g. `AthleteService`, `EnsureOrganizationAccess`).
- Method and variable names: `camelCase` (e.g. `getAthletesByTeam`, `$athleteRepository`).
- Database columns: `snake_case` (e.g. `organization_id`, `date_of_birth`).
- Constants: `UPPER_SNAKE_CASE` (e.g. `STATUS_ACTIVE`).
- **No business logic in controllers**. Controllers must only validate, invoke services, and return resources.
- **No hard-coded values**: Never hard-code organization IDs, role IDs, URLs, or database credentials.

## 2. Flutter & Dart Standards
- Adhere to official Dart style guidelines.
- Widget names: `PascalCase` (e.g. `CoachDashboardScreen`).
- Files and directories: `snake_case` (e.g. `coach_dashboard_screen.dart`).
- Avoid fat widgets: Break complex layouts into focused private or shared widgets.
- No direct database connections from mobile clients. All operations must route via `ApiClient`.

## 3. SQL & Database Standards
- Use explicit column lists in SELECT queries where performance-critical.
- Always include foreign key constraints and relevant indexes for tenant isolation (`organization_id`).
- Never store raw binary documents or images directly in database tables; store only `file_path`.
