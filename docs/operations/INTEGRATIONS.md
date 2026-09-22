# Integration Touchpoints for Operations & Logistics (Member 4)

## Venue Availability (For Members 2 and 3)
The `VenueAvailabilityService` in `app/Services/Operations/VenueAvailabilityService.php` exposes the following endpoints/methods for use by Training (Member 2) and Competitions (Member 3) to check availability:

- `checkAvailability(int $orgId, int $venueId, ?int $facilityId, string $date)`

Please ensure you pass your respective IDs (e.g., `training_session_id`, `fixture_id`) to the `VenueBookingService::createBooking` method so that Operations can track the source of the booking and enforce foreign dependencies.

## Finance Integration (For Member 5)
Operations integrates with Finance via the `ExpenseRecorderInterface` and its default implementation `ExpenseRecorder` located in `app/Services/Operations/ExpenseRecorder.php`.

This adapter will:
1. Map maintenance/vehicle/event costs directly to the `expenses` table.
2. Link to existing vendors via `vendor_id`.
3. Set the status as `pending` so the procurement flow can pick it up.

## User & Tenant Verification (For Member 1)
All Operations endpoints extract the `organization_id` from the resolved user tenant context. Operations relies on `organization_users` active access checks before validating operations requests.
