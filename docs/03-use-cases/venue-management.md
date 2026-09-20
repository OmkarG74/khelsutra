# Use Case: Venue & Facility Management

## UC-VEN-01: Manage Venues & Facilities
- **Primary Actor**: Venue & Tournament Manager.
- **Permission**: `venue.manage`.
- **Main Flow**:
  1. Actor registers Venue (name, code, address, coordinates: latitude/longitude, status).
  2. Actor defines Facilities under Venue (e.g. Ground A, Court 1, Indoor Pool).
- **Business Rule**: Locations must support latitude and longitude.

## UC-VEN-02: Facility Booking with Conflict Validation
- **Primary Actor**: Sports Administrator / Coach / Manager.
- **Permission**: `booking.create`.
- **Main Flow**:
  1. Actor selects facility, date, start_time, end_time, purpose, and booking_entity.
  2. System checks for existing bookings overlapping `(start_time < end_time_param AND end_time > start_time_param)` for the same facility and date.
  3. If conflict exists, request is rejected with 409 Conflict.
  4. If clear, booking is confirmed.
- **Implementation Status**: Skeleton active; recurring slot automation `TODO: TO BE COMPLETED`.

## UC-VEN-03: Maintenance & Housekeeping Tracking
- **Primary Actor**: Venue & Tournament Manager.
- **Permission**: `housekeeping.manage`.
- **Main Flow**: Schedules maintenance downtime or daily housekeeping inspection tasks.
- **Implementation Status**: `TODO: TO BE COMPLETED`.
