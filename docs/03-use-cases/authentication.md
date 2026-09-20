# Use Case: Authentication & Session Management

## UC-01: Multi-Tenant User Login
- **Primary Actor**: Any Registered User (Super Admin, Sports Administrator, HR & Finance, Coach, Athlete, Venue & Tournament Manager, Inventory Manager).
- **Preconditions**:
  1. User account exists in `users` table with status = `active`.
  2. For organisation users, active mapping exists in `organization_users` and organisation status = `active`.
- **Main Flow**:
  1. User submits credentials (email/username, password, and optional organization_code).
  2. System verifies user password hash.
  3. System identifies user's active tenant and assigned role.
  4. System loads role permissions and user permission overrides.
  5. System generates personal access Bearer token.
  6. System returns standard response with token, user object, active organization, and permission list.
- **Alternative Flow**:
  - *Invalid Credentials*: System returns 401 Unauthorized with standard error envelope.
  - *Suspended Organization*: System returns 403 Forbidden indicating subscription status.
- **Postconditions**: User receives authenticated token for V1 API operations.
- **Implementation Status**: Skeleton active; biometric and SSO flows `TODO: TO BE COMPLETED`.

## UC-02: User Logout
- **Primary Actor**: Authenticated User.
- **Main Flow**: Current access token is revoked.
- **Postconditions**: Token is deleted and cannot be reused.

## UC-03: Token Refresh
- **Primary Actor**: Authenticated User.
- **Main Flow**: Client sends active token; server validates and issues fresh token.
