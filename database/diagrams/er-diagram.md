# KhelSutra Entity-Relationship Overview

The KhelSutra database consists of 10 primary architectural domains:

## 1. Tenancy, Authentication & RBAC
- **organizations**: Core tenant entity holding code, name, subscription status, and geographic coordinates.
- **users**: Global user credentials, email, password hash, status.
- **roles**: Fixed system roles (`Super Admin`, `Sports Administrator`, `HR & Finance`, `Coach`, `Athlete`, `Venue & Tournament Manager`, `Inventory Manager`).
- **permissions**: Module-action permission matrix (`athlete.view`, `team.create`, etc.).
- **role_permissions**: Many-to-many mapping between roles and permissions.
- **organization_users**: Tenant membership pivot mapping `organization_id`, `user_id`, `role_id`, and optional `employee_id` or `athlete_id`.
- **user_permission_overrides**: Per-tenant grant/deny permission exceptions for a specific user.
- **organization_access_logs**: Audit trail for organization status transitions.

## 2. Sports & Reference Data
- **sports**: Organization-specific or global sports catalog.
- **sport_categories**: Age and gender classifications (e.g. U-14 Boys, Open Women).
- **performance_metrics**: Sport-configurable metrics (e.g. 100m sprint time, VO2 max).
- **tournament_levels**: Reference levels (`School`, `District`, `State`, `National`, `International`).
- **tournament_formats**: Formats (`Knockout`, `League`, `Round Robin`, `Group + Knockout`).

## 3. HR, Staff & Coaches
- **departments**: Organizational departments (e.g. Coaching, Medical, Logistics).
- **employee_categories**: Employment classification.
- **employees**: Core staff/coach employment record, salary info, joining date.
- **coach_profiles**: Linked to `employees` for coaches, tracking specialization and licensing.
- **employee_documents**: Storage metadata for employee credentials.

## 4. Athletes
- **athletes**: Comprehensive athlete profile with biometric, identification, and emergency contact details.
- **athlete_guardians**: Guardian/parent contact details.
- **athlete_documents**: File paths for identity, medical, and age proof.
- **athlete_sport_history**: Audit history of sports assigned to the athlete.

## 5. Teams
- **teams**: Sports teams scoped to an organization, category, and gender.
- **team_coaches**: Coaches assigned to teams, designating the primary coach.
- **team_members**: Athlete roster assignments with jersey numbers and active status.

## 6. Venues & Facilities
- **venues**: Physical premises with location and contact information.
- **venue_facilities**: Grounds, courts, indoor halls, or tracks within a venue.
- **venue_bookings**: Scheduled facility bookings with overlap validation.
- **venue_maintenance**: Planned and ad-hoc facility maintenance schedules.
- **housekeeping_tasks**: Facility cleanliness and sanitation task tracking.

## 7. Training & Attendance
- **training_sessions**: Scheduled training routines assigned to a team or facility.
- **training_attendance**: Attendance records per athlete for a training session.
- **training_camps**: Extended residential or multi-day intensive camps.
- **training_camp_participants**: Athletes and coaches registered for camps.

## 8. Performance & Medical
- **athlete_performance**: Performance evaluation events.
- **athlete_performance_values**: Metric values recorded during evaluations.
- **athlete_medical_profiles**: Blood group, allergies, ongoing medical conditions.
- **medical_visits**: Doctor or physio consultations.
- **athlete_injuries**: Injury tracking, diagnosis, recovery plan.
- **athlete_medical_clearances**: Return-to-play clearances.

## 9. Tournaments, Fixtures & Matches
- **tournaments**: Tournaments managed or hosted by the organization.
- **tournament_teams**: Registered teams in a tournament.
- **tournament_venues**: Venues allocated to a tournament.
- **fixtures**: Scheduled encounters between teams.
- **matches**: Live/completed match details, scores, and outcomes.
- **tournament_standings**: Points table and rankings.

## 10. Inventory, Purchasing & Finance
- **inventory_categories** & **inventory_items**: Physical goods catalog.
- **equipment**: Serialized assets assignable to athletes, coaches, teams, or venues.
- **stock_transactions**: Inward/outward movement tracking.
- **vendors**, **purchase_requests**, **purchase_orders**, **goods_receipts**, **vendor_invoices**: Full procurement workflow.
- **finance_categories**, **budgets**, **budget_items**, **expenses**, **income_transactions**, **finance_payments**: Financial accounting.
