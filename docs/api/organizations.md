# API Specification: Organisations & Users

### Organisations
- `GET /api/v1/organizations`: List all academies.
- `POST /api/v1/organizations`: Create new academy.
- `GET /api/v1/organizations/{id}`: Detailed academy record.
- `PUT /api/v1/organizations/{id}`: Update academy profile.
- `POST /api/v1/organizations/{id}/status`: Activate, suspend, or terminate access.
- `POST /api/v1/organizations/{id}/users`: Provision initial Sports Administrator.

### Users & RBAC
- `GET /api/v1/users`: List users in tenant context.
- `POST /api/v1/users`: Provision user, assign role and org.
- `GET /api/v1/users/{id}`: User profile and effective permissions.
- `PUT /api/v1/users/{id}`: Update user attributes and role.
- `GET /api/v1/roles`: List 7 standard roles.
- `GET /api/v1/permissions`: Platform permission directory (81 permissions).
