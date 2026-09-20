# Business Requirements Specification (BRS) — KhelSutra

## 1. Executive Summary
KhelSutra is a Multi-Tenant Sports ERP and Operations Management Platform designed to streamline operations for sports academies, educational institutions, federations, and sports complexes.

## 2. Business Objectives
- Centralize sports management across disparate sports, facilities, and entities.
- Ensure strict multi-organisation data isolation.
- Digitize athlete lifecycles, health clearances, and sport performance evaluations.
- Streamline facility scheduling and eliminate double-booking errors.
- Automate multi-format tournament scheduling, brackets, and standings.
- Manage staff, coaches, attendance, leave, and monthly payroll.
- Track serialized equipment and procurement workflows.

## 3. User Roles and Stakeholder Matrix
| Role | Primary Responsibilities |
|---|---|
| **Super Admin** | Platform provisioning, tenant onboarding, global monitoring |
| **Sports Administrator** | Single-tenant executive administration, sports catalog, user access |
| **HR & Finance** | Staff hiring, coach employment, payroll, leave approvals, budgeting |
| **Coach** | Team rosters, training regimens, attendance, tactical setups, performance |
| **Athlete** | Viewing schedules, attendance records, performance metrics, leave requests |
| **Venue & Tournament Manager** | Venue bookings, facility upkeep, housekeeping, tournament operations |
| **Inventory Manager** | Equipment tracking, stock auditing, vendor management, POs |

## 4. Key Business Rules
1. Athletes belong to one primary sport at any given time; reassignment history must be preserved.
2. Coaches are employees; they participate in employee records, leave, and payroll.
3. Venue hierarchy must strictly adhere to: `Organisation → Venue → Facility/Court/Ground`.
4. Overlapping bookings for the same facility during the same time window must be strictly rejected.
5. Tournaments support Knockout, League, Round Robin, and Group+Knockout formats.
6. Inventory items are tracked by quantity; serialized equipment is individually assigned to athletes, coaches, teams, or venues.
7. Procurement must follow: `Purchase Request → Approval → Purchase Order → Goods Receipt → Vendor Invoice → Payment`.
8. Files must be stored as file paths pointing to object storage (AWS S3); MySQL must never store raw binary files.
9. Audit logs must capture actor, action, timestamp, module, record ID, old values, new values, IP, and User-Agent.

## 5. Scope & Status
- **Current Milestone**: Architecture & Skeleton Initialization.
- **Implementation Status**: Skeletons established. Full business module logic marked as `TODO: TO BE COMPLETED` in subsequent milestone sprints.
