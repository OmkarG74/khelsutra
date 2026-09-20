# KhelSutra Architecture: Authentication Foundation

## Overview
KhelSutra implements a stateless REST API token authentication foundation. All authentication flows occur over HTTPS against `/api/v1/auth/*`.

## Login Flow (Section 13)
```
User (Credentials)
  ↓
Credentials Validation (Argon2id/Bcrypt Secure Hash Verification)
  ↓
Account Status Validation (status === 'active')
  ↓
Organisation Access Validation (organization_users membership & org status === 'active')
  ↓
Role Identification (7 standard platform roles)
  ↓
Permission Resolution (Resolved from role_permissions + user_permission_overrides)
  ↓
Issue Authenticated Token & Session
  ↓
Return User Context + Organization Context + Permissions Matrix
```

## Security Constraints
- Passwords are never returned in responses or logged in audit trails.
- Cross-tenant user authentication validates active organisation status and expiration dates.
- Flutter and Web clients transmit authentication via `Authorization: Bearer <token>` and `X-Organization-ID: <id>` headers.
