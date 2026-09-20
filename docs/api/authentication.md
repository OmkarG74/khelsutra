# API Specification: Authentication

### `POST /api/v1/auth/login`
- **Body**: `{ "email": "coach@khelsutra.com", "password": "Password123", "organization_code": "ORG-DEMO" }`
- **Response 200 OK**:
  ```json
  {
    "success": true,
    "message": "Authenticated successfully",
    "data": {
      "token": "ks_tok_...",
      "user": { "id": 2, "name": "Rajesh Sharma", "email": "coach@khelsutra.com" },
      "organization": { "id": 1, "code": "ORG-DEMO", "name": "Titans Academy" },
      "role": { "id": 4, "name": "Coach" },
      "permissions": ["training.view", "attendance.view", "attendance.mark"]
    }
  }
  ```

### `GET /api/v1/auth/me`
- **Headers**: `Authorization: Bearer <token>`, `X-Organization-ID: 1`
- **Response 200 OK**: Current authenticated profile, tenant context, and active permissions.

### `POST /api/v1/auth/logout`
- **Headers**: `Authorization: Bearer <token>`
- **Response 200 OK**: Clears active session/token.
