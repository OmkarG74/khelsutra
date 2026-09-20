# Contributing to KhelSutra

Thank you for contributing to KhelSutra Sports Management Platform. We operate as a team of 5 developers building a multi-tenant Sports ERP. To maintain high code quality, data isolation, and architectural consistency, all team members must adhere to these guidelines.

---

## 1. Branching Strategy

We follow the Git Flow branching model:

```
main (Production releases only)
  ↑
develop (Shared integration branch)
  ↑
feature/*, bugfix/*, hotfix/*
```

### Branch Naming Conventions
- Features: `feature/<module-name>-<short-description>`
  - Examples: `feature/auth-sanctum`, `feature/athlete-onboarding`, `feature/tournament-fixtures`
- Bug Fixes: `bugfix/<ticket-id>-<short-description>`
  - Examples: `bugfix/KS-104-booking-conflict`
- Hotfixes: `hotfix/<version>-<short-description>`
  - Examples: `hotfix/v0.1.1-tenant-leak`

---

## 2. Commit Message Conventions

We follow Conventional Commits:

```
<type>(<scope>): <short summary>

[optional body]

[optional footer(s)]
```

### Allowed Types
- `feat`: A new feature
- `fix`: A bug fix
- `docs`: Documentation only changes
- `style`: Changes that do not affect the meaning of the code (formatting, linting)
- `refactor`: A code change that neither fixes a bug nor adds a feature
- `perf`: A code change that improves performance
- `test`: Adding missing tests or correcting existing tests
- `chore`: Changes to the build process or auxiliary tools

### Examples
- `feat(auth): implement token refresh endpoint`
- `fix(tenancy): prevent cross-tenant queries in AthleteRepository`
- `docs(api): document tournament standings endpoint in openapi.yaml`

---

## 3. Strict Database Rules

> [!CAUTION]
> The database schema in `database/sports_management_database.sql` is the **INITIAL SOURCE OF TRUTH**.

1. **No Unapproved Schema Changes**: Developers must NOT unilaterally alter table names, column types, or relationships.
2. **Schema Change Request Process**:
   - Any proposed change must be documented in an RFC issue.
   - It must be reviewed and approved by the team lead and database architect.
   - Schema modifications must be reflected in versioned migration files and `database/sports_management_database.sql`.
3. **Multi-Tenancy Guard**: Every table storing organization-specific data must include `organization_id` foreign key.

---

## 4. API Design Standards

1. All endpoints must be versioned: `/api/v1/...`
2. Follow standard REST HTTP methods:
   - `GET`: Read resource(s)
   - `POST`: Create resource
   - `PUT`: Full resource update
   - `PATCH`: Partial resource update
   - `DELETE`: Remove / archive resource
3. Response Format: All responses must use the `ApiResponse` helper:
   ```json
   {
     "success": true,
     "message": "Resource retrieved successfully",
     "data": {}
   }
   ```
4. Error Format:
   ```json
   {
     "success": false,
     "message": "Validation failed",
     "errors": {}
   }
   ```
5. Update `swagger/openapi.yaml` and `postman/KhelSutra.postman_collection.json` with every API modification.

---

## 5. Architectural Standards

- **Controllers must remain thin**. All business logic lives in `app/Services/`.
- Validation must be in dedicated `FormRequest` classes under `app/Http/Requests/`.
- Enforce tenancy via `EnsureOrganizationAccess` middleware and the `BelongsToOrganization` trait.
- Enforce permissions via `RequirePermission` middleware.
- Data access should route through Repositories under `app/Repositories/`.

---

## 6. Pre-PR Checklist

Before opening a Pull Request:
- [ ] Code follows PSR-12 formatting (`composer lint` / `php-cs-fixer`).
- [ ] No PHP syntax errors (`php -l`).
- [ ] Unit and Feature tests pass (`php artisan test`).
- [ ] Flutter code passes static analysis (`flutter analyze` inside `mobile/`).
- [ ] No hard-coded IDs, organization keys, credentials, or production URLs.
- [ ] Documentation updated in `docs/` and API specifications updated.

---

## 7. Pull Requests & Code Review

1. Create PRs targeting `develop`.
2. PR descriptions must explain:
   - Summary of change
   - Motivation and context
   - How this was tested
   - Breaking changes or migration requirements
3. Minimum 1 approving review from another team member required before merge.
4. Squashing and merging is preferred for clean commit history on `develop`.
