# Operations & Logistics API

## Base URL
`/api/v1`

## Authentication & Headers
All requests must pass standard authentication tokens (managed by Member 1).
The context is verified through the `organization_id` of the user making the request.

## Endpoints

### Venues
- `GET /venues`: List venues
- `POST /venues`: Create a new venue
- `GET /venues/{id}/availability`: Get availability given date bounds

### Bookings
- `POST /bookings`: Create a venue/facility booking. Supports overlap validation.

### Transport
- `GET /vehicles`: List vehicles
- `POST /vehicles`: Add vehicle
- `GET /trips`: List trips
- `POST /trips`: Create a trip (verifies driver and vehicle dates)
- `POST /trips/{id}/passengers`: Assign a passenger (verifies capacity)

### Accommodation
- `GET /accommodations`: List accommodations
- `POST /accommodations`: Create accommodation
- `POST /accommodations/{id}/rooms`: Create room within accommodation
- `GET /room-allocations`: List allocations
- `POST /room-allocations`: Allocate a room to a user (verifies room capacity)

### Events & School Activities
- `GET /events`: List events
- `POST /events`: Create event
- `POST /events/{id}/participants`: Add participant to event
- `GET /school-activities`: List activities
- `POST /school-activities`: Add activity

### Maintenance & Housekeeping
- `GET /maintenance`: List maintenance
- `POST /maintenance`: Create maintenance ticket
- `GET /housekeeping`: List tasks
- `POST /housekeeping`: Create task

*All POST payloads mirror the database column names directly.*
