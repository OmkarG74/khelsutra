# Use Case: Athlete Management

## UC-ATH-01: Register New Athlete
- **Primary Actor**: Sports Administrator / Coach.
- **Permission**: `athlete.create`.
- **Preconditions**:
  1. Organization exists and is active.
  2. Selected primary sport exists and is active.
- **Main Flow**:
  1. Actor submits athlete details (first_name, last_name, dob, gender, blood_group, identification_type, identification_number, primary_sport_id).
  2. System validates input data via `StoreAthleteRequest`.
  3. System assigns unique athlete code (e.g. `ATH-ORG01-2026-001`).
  4. System persists athlete record within current `organization_id`.
  5. System records initial sport entry in `athlete_sport_history`.
  6. Returns 201 Created with standard athlete payload.
- **Business Rules**:
  - Athlete belongs to exactly one sport at a time.
  - Whenever primary sport changes, an entry in `athlete_sport_history` must record `sport_id`, `start_date`, and previous `end_date`.
- **Implementation Status**: Skeleton active; advanced biometric imports `TODO: TO BE COMPLETED`.

## UC-ATH-02: Record Athlete Medical Information
- **Primary Actor**: Doctor / Sports Administrator / HR.
- **Permission**: `medical.manage`.
- **Main Flow**: Records allergies, chronic ailments, injuries, treatment plans, and return-to-play clearance.
- **Access Rule**: Strict restricted access. Coaches only see clearance status, not raw medical records.
- **Implementation Status**: `TODO: TO BE COMPLETED`.
