# Core API Endpoints Directory

## System
- `GET /api/v1/health`: System health and MySQL database connectivity check.

## Authentication
- `POST /api/v1/auth/login`: Authenticate and obtain Bearer token.
- `POST /api/v1/auth/logout`: Revoke active session token.
- `GET /api/v1/auth/me`: Fetch current authenticated profile, role, and permissions.
- `POST /api/v1/auth/refresh`: Refresh token.

## Organizations & Tenancy
- `GET /api/v1/organizations`: List organizations (Super Admin only).
- `POST /api/v1/organizations`: Provision a new organization (Super Admin only).
- `GET /api/v1/organizations/{id}`: View organization details.
- `PUT /api/v1/organizations/{id}`: Update organization profile and settings.

## Athletes
- `GET /api/v1/athletes`: List athletes for current tenant (paginated).
- `POST /api/v1/athletes`: Register new athlete.
- `GET /api/v1/athletes/{id}`: View athlete profile and sport history.
- `PUT /api/v1/athletes/{id}`: Update athlete details.
- `DELETE /api/v1/athletes/{id}`: Archive/soft-delete athlete.

## Teams
- `GET /api/v1/teams`: List teams.
- `POST /api/v1/teams`: Create new sports team.
- `GET /api/v1/teams/{id}`: Team details and roster.
- `PUT /api/v1/teams/{id}`: Update team info.
- `DELETE /api/v1/teams/{id}`: Archive team.

## Tournaments
- `GET /api/v1/tournaments`: List tournaments.
- `POST /api/v1/tournaments`: Create tournament.
- `GET /api/v1/tournaments/{id}`: Tournament details and standings.

## Venues
- `GET /api/v1/venues`: List venues and facilities.
- `POST /api/v1/venues`: Register new venue.
- `POST /api/v1/bookings`: Book venue facility (conflict-checked).

*(For exhaustive field specifications and interactive testing, refer to `swagger/openapi.yaml` and `postman/KhelSutra.postman_collection.json`)*
