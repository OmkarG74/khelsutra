# KhelSutra Architecture: Role-Based Access Control (RBAC)

## The 7 Standard Roles (Section 20)
1. **Super Admin**: Complete platform stewardship, academy provisioning, and global telemetry.
2. **Sports Administrator**: Academy CEO / Director managing academy roster, venues, and schedules.
3. **HR & Finance**: Staff onboarding, attendance auditing, leave review, and payroll disbursements.
4. **Coach**: Team coaching, session drilling, training attendance, and athlete evaluations.
5. **Athlete**: Squad training participation, attendance tracking, and tournament performance.
6. **Venue & Tournament Manager**: Facilities, turf bookings, fixtures, and equipment allocation.
7. **Inventory Manager**: Sports stock, apparel, gear tracking, and purchase requisitions.

## Dynamic Permission Resolution
Permissions are loaded from `permissions` and mapped to roles in `role_permissions`.
Individual user exceptions can be granted or denied via `user_permission_overrides`.
Resolution priority:
`User Override (Allow/Deny) > Role Permissions > Default Deny`.
Super Admin bypasses granular permission gates across all modules.
