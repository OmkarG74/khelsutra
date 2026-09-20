# Use Case: Events, School Activities & Logistics

## UC-EVT-01: Organize School Sports Activities & Events
- **Primary Actor**: Sports Administrator / Events Coordinator.
- **Permission**: `event.manage`.
- **Main Flow**:
  1. Creates sports day, inter-house gala, or regional sports festival in `events`.
  2. For educational institutions, configures grade/section activities in `school_activities`.
  3. Registers participants, teams, and schedules.

## UC-EVT-02: Transport & Accommodation Management
- **Primary Actor**: Logistics Coordinator / Sports Administrator.
- **Permission**: `event.manage`.
- **Main Flow**:
  1. Registers fleet vehicles and drivers in `vehicles`.
  2. Schedules trips, routes, and passenger rosters in `transport_trips` and `transport_passengers`.
  3. Manages hostels, hotel blocks, and room allocations in `accommodations`, `accommodation_rooms`, and `accommodation_allocations`.
- **Implementation Status**: Skeleton active; GPS fleet tracking `TODO: TO BE COMPLETED`.
