# Database Design & Architecture — KhelSutra

## 1. Schema Ground Truth
The database schema defined in `database/sports_management_database.sql` serves as the authoritative source of truth. It accommodates 60+ tables across 10 functional clusters.

## 2. Table Classification
- **Core Tenancy**: `organizations`, `users`, `roles`, `permissions`, `role_permissions`, `organization_users`, `user_permission_overrides`, `organization_access_logs`.
- **Sports & Metrics**: `sports`, `sport_categories`, `performance_metrics`, `tournament_levels`, `tournament_formats`.
- **Employees & Coaching**: `departments`, `employee_categories`, `employees`, `coach_profiles`, `employee_documents`.
- **Athletes**: `athletes`, `athlete_guardians`, `athlete_documents`, `athlete_sport_history`.
- **Teams**: `teams`, `team_coaches`, `team_members`.
- **Venues**: `venues`, `venue_facilities`, `venue_bookings`, `venue_maintenance`, `housekeeping_tasks`.
- **Training**: `training_sessions`, `training_attendance`, `training_camps`, `training_camp_participants`.
- **Performance & Health**: `athlete_performance`, `athlete_performance_values`, `athlete_medical_profiles`, `medical_visits`, `athlete_injuries`, `athlete_medical_clearances`.
- **Competitions**: `tournaments`, `tournament_teams`, `tournament_venues`, `fixtures`, `matches`, `tournament_standings`.
- **Procurement & HR Operations**: `leave_types`, `leave_requests`, `payroll_periods`, `salary_structures`, `payroll`, `inventory_categories`, `inventory_items`, `equipment`, `stock_transactions`, `vendors`, `purchase_orders`, `goods_receipts`, `vendor_invoices`.

## 3. Design Conventions
- Primary keys: `BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`.
- Foreign keys: Explicitly constrained with cascade or restrict rules.
- Timestamps: `created_at` and `updated_at`.
- Soft Deletes: `deleted_at TIMESTAMP NULL` on major entities.
