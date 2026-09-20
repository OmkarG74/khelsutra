# KhelSutra — Local Development Test Credentials

> [!WARNING]
> **LOCAL DEVELOPMENT / TESTING ONLY**
> **DO NOT USE IN PRODUCTION ENVIRONMENTS**
>
> All credentials listed below are pre-seeded in the local development database (`khelsutra`) exclusively for local manual and automated security verification. Passwords must never be hardcoded in application business logic, and only bcrypt hashes are stored in the database.

---

## 1. Super Admin (Platform Provider)
* **Name**: KhelSutra Platform Admin
* **Email**: `superadmin@khelsutra.local`
* **Role**: Super Admin (Role ID: 1, Slug: `super_admin`)
* **Organisation**: Platform-Level Context (Cross-tenant platform management)
* **Password**: `KhelSutra@123`
* **Access Scope**:
  - Global Platform Dashboard
  - Tenant Organisation Management (`/super-admin/organizations`)
  - Platform User Directory & Role Definitions
  - System Audit Trail & Global Analytics

---

## 2. Sports Administrator (Tenant Lead)
* **Name**: Rajesh Sharma
* **Email**: `sportsadmin@khelsutra.local`
* **Role**: Sports Administrator (Role ID: 2, Slug: `sports_admin`)
* **Organisation**: Apex Sports Academy (ID: 1, Code: `ORG-DEMO`)
* **Password**: `KhelSutra@123`
* **Access Scope**:
  - Academy Operations Dashboard
  - Athletes, Coaches, Teams, Tournaments, Training
  - Venues, Facilities, Inventory Management
  - Academy Staff Directory, Leaves, Attendance, Payroll
  - Academy RBAC & User Management (within Apex Sports Academy only)
  - Strict Tenant Isolation (Cannot access other academies or Super Admin areas)

---

## 3. HR & Finance
* **Name**: Priya Nair
* **Email**: `hrfinance@khelsutra.local`
* **Role**: HR & Finance (Role ID: 3, Slug: `hr_finance`)
* **Organisation**: Apex Sports Academy (ID: 1, Code: `ORG-DEMO`)
* **Password**: `KhelSutra@123`
* **Access Scope**:
  - Staff Directory, Departments, and Employee Categories
  - Leave Requests & Attendance Records (Training & Match)
  - Payroll Operations, Salary Structures, and Payroll Periods
  - Academy Financial Reports

---

## 4. Coach
* **Name**: Amit Kumar
* **Email**: `coach@khelsutra.local`
* **Role**: Coach (Role ID: 4, Slug: `coach`)
* **Organisation**: Apex Sports Academy (ID: 1, Code: `ORG-DEMO`)
* **Password**: `KhelSutra@123`
* **Access Scope**:
  - Assigned Teams & Athletes
  - Training Sessions & Attendance Recording
  - Fixtures & Tournaments
  - Personal Leave Requests & Performance Reports

---

## 5. Athlete
* **Name**: Aarav Patel
* **Email**: `athlete@khelsutra.local`
* **Role**: Athlete (Role ID: 5, Slug: `athlete`)
* **Organisation**: Apex Sports Academy (ID: 1, Code: `ORG-DEMO`)
* **Password**: `KhelSutra@123`
* **Access Scope**:
  - Personal Athlete Profile & Achievements
  - Team Roster & Training Schedules
  - Personal Attendance & Leave Requests
  - Tournament Fixtures

---

## 6. Venue & Tournament Manager
* **Name**: Neha Singh
* **Email**: `venue.tournament@khelsutra.local`
* **Role**: Venue & Tournament Manager (Role ID: 6, Slug: `venue_manager`)
* **Organisation**: Apex Sports Academy (ID: 1, Code: `ORG-DEMO`)
* **Password**: `KhelSutra@123`
* **Access Scope**:
  - Venues, Courts, and Grounds
  - Facility Bookings & Schedule Management
  - Maintenance & Housekeeping Schedules
  - Tournament Fixture Scheduling

---

## 7. Inventory Manager
* **Name**: Vikram Rao
* **Email**: `inventory@khelsutra.local`
* **Role**: Inventory Manager (Role ID: 7, Slug: `inventory_manager`)
* **Organisation**: Apex Sports Academy (ID: 1, Code: `ORG-DEMO`)
* **Password**: `KhelSutra@123`
* **Access Scope**:
  - Stock Inventory (Footballs, Jerseys, Cones, Nets, First-Aid)
  - Equipment Tracking & Allocations
  - Vendors, Suppliers & Purchase Orders
  - Low Stock Alerts

---

## Security Verification Guidelines

1. **Authentication Endpoint**: `POST /api/v1/auth/login`
   ```json
   {
     "email": "sportsadmin@khelsutra.local",
     "password": "KhelSutra@123"
   }
   ```
2. **Current Profile Endpoint**: `GET /api/v1/auth/me` with `Authorization: Bearer <token>`
3. **No Query Parameter Role Simulation**: Supplying `?role=super_admin` or `?role=sports_admin` in URLs has zero effect on permissions.
4. **Tenant Isolation**: Non-super admins attempting to provide `X-Organization-ID: 2` or `organization_id: 2` will be rejected with HTTP 403 Forbidden.
