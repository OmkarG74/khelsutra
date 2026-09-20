# Changelog

All notable changes to the KhelSutra Sports Management Platform will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2026-09-20

### Added
- Complete project root directory structure (`backend`, `web`, `mobile`, `database`, `docs`, `tests`, `postman`, `swagger`, `scripts`, `deployment`).
- Relocated and verified master schema `database/sports_management_database.sql` with ER diagrams and domain documentation.
- Database seeds for initial system roles (7 roles), permissions (81 permissions), and baseline sports reference data.
- Full Laravel clean architecture skeleton in `backend/`:
  - Layered pattern: Controller -> Form Request -> Policy/Middleware -> Service -> Repository -> Model -> Database.
  - Multi-tenancy isolation middleware (`EnsureOrganizationAccess`) and global Eloquent scope trait (`BelongsToOrganization`).
  - RBAC middleware (`RequirePermission`) and role-permission assignment mapping.
  - Authentication endpoints: login, logout, me, refresh.
  - Health check endpoint `/api/v1/health` with live MySQL ping.
  - Standardized JSON API response and centralized exception handling.
  - Resource controllers and API route groups for all 32 functional domains.
  - Skeletons and models for Athletes, Teams, Tournaments, Venues, Users, Organizations.
- Web application structure in `web/` with Bootstrap layout, responsive dashboard, stat cards, data tables, and authentication view.
- Flutter mobile application structure in `mobile/`:
  - Clean modular architecture (UI -> State -> Repository -> ApiService -> REST API).
  - Production `ApiClient` supporting GET, POST, PUT, PATCH, DELETE with Bearer auth and organization context.
  - Role-aware dynamic routing (`RoleRouter`) providing initial Coach and Athlete navigation, extensible to all future roles.
  - Skeletons for Coach and Athlete dashboards and auth login screen.
- OpenAPI 3.0 specification (`swagger/openapi.yaml`).
- Postman collection (`postman/KhelSutra.postman_collection.json`) with environments (`local.json`, `staging.json`, `production.json`).
- Complete 10-section documentation suite in `docs/`.
- Automated scripts and AWS deployment architecture blueprints.
