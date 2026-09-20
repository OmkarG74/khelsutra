# KhelSutra — Role RBAC Test Matrix & Security Boundaries

This document specifies the authorization rules, permitted/restricted modules, and expected authorization response codes for each of the 7 authenticated platform roles.

---

## Role Isolation & Permission Matrix

| Role | Test Email | Tenant Context | Expected Dashboard | Allowed Modules | Restricted Modules | Direct Super-Admin URL Access (`/super-admin/*`) | Cross-Tenant Access Attempt | Self Role Elevation Attempt |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Super Admin** | `superadmin@khelsutra.local` | Platform-Level (All Tenants) | Platform Dashboard | Organizations, Platform Users, Roles & RBAC, Sports Catalog, Audit Logs, Analytics | Normal Academy Operational Data (unless scoped) | **Allowed (200 OK)** | Allowed (Provider Context) | Allowed (Can configure roles) |
| **Sports Administrator** | `sportsadmin@khelsutra.local` | Apex Sports Academy (`ORG-DEMO`) | Academy Operations Dashboard | Athletes, Coaches, Teams, Tournaments, Training, Venues, Inventory, Users/RBAC (own org), HR/Payroll, Reports | Super Admin Organizations (`/super-admin/*`), Other Academies | **HTTP 403 Forbidden** | **HTTP 403 Forbidden** | **HTTP 403 Forbidden (DENIED)** |
| **HR & Finance** | `hrfinance@khelsutra.local` | Apex Sports Academy (`ORG-DEMO`) | HR & Finance Dashboard | Staff Directory, Attendance, Leaves, Payroll, Salary Structures, Financial Reports | Super Admin Management, Sports Catalog, Other Academies | **HTTP 403 Forbidden** | **HTTP 403 Forbidden** | **HTTP 403 Forbidden (DENIED)** |
| **Coach** | `coach@khelsutra.local` | Apex Sports Academy (`ORG-DEMO`) | Coach Training Dashboard | Assigned Teams, Training Attendance, Athletes, Tournaments, Personal Leaves | Super Admin Management, HR/Payroll Admin, Organization Settings, Other Academies | **HTTP 403 Forbidden** | **HTTP 403 Forbidden** | **HTTP 403 Forbidden (DENIED)** |
| **Athlete** | `athlete@khelsutra.local` | Apex Sports Academy (`ORG-DEMO`) | Athlete Portal Dashboard | Personal Profile, Team Schedule, Personal Attendance, Apply Leave, Fixtures | Super Admin Management, HR/Payroll, Other Athletes' Private Data, Other Academies | **HTTP 403 Forbidden** | **HTTP 403 Forbidden** | **HTTP 403 Forbidden (DENIED)** |
| **Venue & Tournament Manager** | `venue.tournament@khelsutra.local` | Apex Sports Academy (`ORG-DEMO`) | Facilities Dashboard | Venues, Facility Bookings, Maintenance, Housekeeping, Tournaments | Super Admin Management, HR/Payroll Admin, Other Academies | **HTTP 403 Forbidden** | **HTTP 403 Forbidden** | **HTTP 403 Forbidden (DENIED)** |
| **Inventory Manager** | `inventory@khelsutra.local` | Apex Sports Academy (`ORG-DEMO`) | Inventory Dashboard | Stock Inventory, Equipment Tracking, Vendors, Purchase Orders | Super Admin Management, HR/Payroll Admin, Other Academies | **HTTP 403 Forbidden** | **HTTP 403 Forbidden** | **HTTP 403 Forbidden (DENIED)** |

---

## Security Verification Rules

1. **Static Organisation Context in Header**:
   - For all normal organisation users (`sportsadmin`, `hrfinance`, `coach`, `athlete`, `venue.tournament`, `inventory`), the top header displays a static text badge: `Apex Sports Academy (ORG-DEMO)`.
   - The "Switch Organisation" dropdown and menu are completely eliminated from the DOM.
   - For `superadmin`, the top header displays static `KhelSutra Platform`.

2. **Elimination of Role Simulation**:
   - The "Preview Role Navigation" dropdown is completely removed from the header.
   - The query parameter `?role=...` is never processed to determine a user's role.
   - Role state is derived exclusively from the authenticated database user (`users` $\rightarrow$ `organization_users` $\rightarrow$ `roles`).

3. **Direct URL Authorization**:
   - Web routes under `/super-admin/*` strictly check that the session role is `Super Admin` (Role ID 1).
   - Any non-Super Admin role accessing `/super-admin/*` receives an immediate HTTP 403 Forbidden response.

4. **API Endpoint Authorization**:
   - `/api/v1/organizations*` routes are restricted strictly to Super Admin.
   - Attempting to pass `X-Organization-ID: 2` or `organization_id: 2` by a user assigned to Organization 1 returns an immediate HTTP 403 Forbidden response.
   - Attempting to pass `role_id: 1` or change one's own role via user endpoints returns HTTP 403 Forbidden.
