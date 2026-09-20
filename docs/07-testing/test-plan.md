# Test Plan — KhelSutra

## 1. Objective
Verify platform stability, multi-tenant data isolation, RBAC security, API contract compliance, and role-based mobile routing.

## 2. Test Scope
- **Backend Feature Tests**:
  - `AuthenticationTest`: Login, invalid credentials, token revocation.
  - `OrganizationAccessTest`: Multi-tenant boundary checks; verify Tenant B cannot view Tenant A's data.
  - `PermissionTest`: Verify unauthorized roles receive 403 Forbidden.
  - `AthleteTest`, `TeamTest`, `TournamentTest`, `VenueBookingTest`.
- **Backend Unit Tests**:
  - `AthleteServiceTest`, `TeamServiceTest`, `TournamentServiceTest`, `VenueBookingServiceTest`, `PermissionServiceTest`.
- **Mobile Tests**:
  - Role-aware navigation routing (`RoleRouter`).
  - `ApiClient` error serialization and token persistence.

## 3. Execution Commands
```bash
# Backend test suite
cd backend
php artisan test

# Mobile static checks & tests
cd mobile
flutter analyze
flutter test
```
