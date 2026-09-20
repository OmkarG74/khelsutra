# System Architecture — KhelSutra

## Architectural Overview
KhelSutra implements a modern layered, multi-tenant architecture designed for high availability, enterprise data isolation, and modular team scalability.

```
+-------------------------------------------------------------+
|                     Client Tier                             |
|  - Web Portal (Blade + Bootstrap 5 + Vanilla JS)            |
|  - Mobile App (Flutter 3.x for Android / iOS / Web)         |
+-------------------------------------------------------------+
                              |
                     HTTPS / TLS 1.3
                              |
+-------------------------------------------------------------+
|                    Application Gateway                      |
|  - AWS CloudFront / ALB / Nginx Reverse Proxy               |
+-------------------------------------------------------------+
                              |
+-------------------------------------------------------------+
|                    Application Tier                         |
|  Laravel 10/11 REST API Service                             |
|  ├── Route Middlewares (EnsureOrganizationAccess, RBAC)     |
|  ├── Thin HTTP Controllers                                  |
|  ├── Form Request Validation Layer                          |
|  ├── Domain Service Layer (Pure Business Logic)             |
|  ├── Repository Layer (Data Query Isolation)                |
|  └── Eloquent ORM + Global Tenant Scopes                    |
+-------------------------------------------------------------+
                              |
               +--------------+--------------+
               |                             |
+---------------------------+   +---------------------------+
|       Data Tier           |   |       Storage Tier        |
|  Amazon Aurora MySQL 8.0  |   |  Amazon S3 Bucket         |
|  - Multi-tenant DB        |   |  - Athlete documents      |
|  - Relational Integrity   |   |  - Medical certificates   |
|  - Foreign Key Constraints|   |  - Vendor invoices        |
+---------------------------+   +---------------------------+
```

## Multi-Tenancy Strategy
- **Shared Database, Shared Schema**: Organization data is partitioned by `organization_id` on all tenant-specific tables.
- **Enforcement Mechanisms**:
  - `EnsureOrganizationAccess` middleware checks tenant subscription status and user access rights.
  - Global Eloquent Scope (`BelongsToOrganization`) automatically appends `organization_id` to SELECT, UPDATE, and DELETE queries.
  - Super Admin users can bypass tenant scopes for platform oversight.

## Clean Layer Separation
1. **Controllers**: Request deserialization and HTTP response formatting. Zero business logic.
2. **Form Requests**: Strict input validation and field type checking.
3. **Policies / Middleware**: Role-based access control and permission enforcement.
4. **Services**: Domain calculations, transaction management, and workflow coordination.
5. **Repositories**: Concrete SQL querying, filtering, and pagination.
6. **Models**: Entity definitions, relationships, and property casts.
