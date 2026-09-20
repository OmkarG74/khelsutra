# API Authentication & Authorization Guide

## Authentication Mechanism
KhelSutra utilizes token-based authentication via Laravel Sanctum.

### Endpoints
1. `POST /api/v1/auth/login`: Authenticate with credentials and receive Bearer Token.
2. `POST /api/v1/auth/logout`: Invalidate current active Bearer Token.
3. `GET /api/v1/auth/me`: Retrieve active profile, organization, and permissions.
4. `POST /api/v1/auth/refresh`: Refresh active session token.

### Request Headers
Subsequent protected API requests must supply:
```http
Authorization: Bearer <your-sanctum-token>
Accept: application/json
X-Organization-ID: <optional-organization-id-for-super-admin>
```

## Role & Permission Enforcement
Routes are guarded with middleware:
- `auth:sanctum`: Ensures valid authentication token.
- `org.access`: Verifies tenant membership and subscription active state.
- `require.permission:<permission-name>`: Validates that the user's role or explicit permission override grants the requested action.
