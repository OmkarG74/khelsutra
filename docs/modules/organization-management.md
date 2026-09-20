# Module: Organisation Management

## Database Tables
- `organizations`
- `organization_users`
- `organization_access_logs`
- `organization_settings`

## Core Responsibilities
- Academy lifecycle: `draft`, `active`, `suspended`, `expired`.
- Subscription access start date and end date validation.
- Initial Sports Administrator provisioning: Super Admin exclusive capability.
- Tenant access audit logging via `organization_access_logs`.
- Typed key-value configuration via `organization_settings`.
