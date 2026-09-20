# KhelSutra Architecture: Multi-Tenancy & Tenant Isolation

## Multi-Tenant SaaS Model
Every sports academy, club, or school is an autonomous tenant mapped to an `organization_id`.

## Tenant Isolation Guarantee (Section 15 & 16)
- Authenticated users belong to an organization through `organization_users`.
- Multi-tenancy context is resolved on the backend via `TenantContextService`.
- **Golden Rule**: Never trust `organization_id` directly from frontend requests. The backend derives and validates tenant membership from the authenticated session.
- Every database query for tenant-owned data strictly scopes records by `organization_id`:
  ```sql
  SELECT * FROM employees WHERE id = :id AND organization_id = :org_id AND deleted_at IS NULL;
  ```
- Cross-tenant access between Tenant A and Tenant B yields HTTP 403 Forbidden or 404 Not Found.
