# System Components — KhelSutra

## Component Catalog

### 1. Identity & Access Management (IAM)
- **Token Manager**: Laravel Sanctum generating cryptographically signed API tokens.
- **Tenant Context Provider**: Middleware deriving `organization_id` from token or header.
- **RBAC Engine**: Enforces role hierarchy and permission overrides.

### 2. Athletic Performance & Health Subsystem
- **Athlete Manager**: Manages registration, guardians, and documents.
- **Sport History Registry**: Tracks sport reassignments over time.
- **Performance Evaluation Engine**: Metric-based assessment calculations.
- **Medical Registry**: Tracks medical conditions, clinic visits, injuries, and clearances.

### 3. Competitions & Fixtures Subsystem
- **Tournament Planner**: Initializes brackets, groups, and tournament teams.
- **Scheduler Engine**: Generates fixtures avoiding venue and team scheduling clashes.
- **Match Engine**: Records live and final scores, cautions, and standings.

### 4. Operations & Facilities Subsystem
- **Venue Registry**: Hierarchical premises, ground, court, and facility directory.
- **Booking Engine**: Overlap-preventing scheduler for facility reservations.
- **Maintenance & Housekeeping Tracker**: Work orders and sanitization schedules.

### 5. HR & Finance Subsystem
- **Staff & Coach HR**: Employment lifecycle, contracts, and assignments.
- **Attendance & Leave Engine**: Training attendance, match attendance, leave workflows.
- **Payroll Processor**: Basic salary, allowances, overtime, deductions, and net salary.
- **Financial Ledger**: Budgets, income, expenses, and disbursement tracking.

### 6. Supply Chain & Assets Subsystem
- **Inventory Controller**: Stock levels and batch movements.
- **Equipment Allocator**: Tracks serialized equipment assigned to individuals or venues.
- **Procurement Engine**: PR → PO → GRN → Invoice lifecycle.
