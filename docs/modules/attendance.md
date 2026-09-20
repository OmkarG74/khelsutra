# Module: Training & Match Attendance Integration

## Scope
Attendance is not generic office clock-in. It tracks active sports readiness:
1. **Training Attendance** (`training_attendance`): Integrates with Member 2 sessions.
2. **Match Attendance** (`match_attendance`): Integrates with Member 3 fixtures.

## Strict Single-Participant Constraint
Because `athlete_id`, `coach_id`, and `employee_id` are nullable alternatives in MySQL:
- Application logic enforces **strictly one** subject identifier per attendance entry (`athlete_id` XOR `coach_id` XOR `employee_id`).
- Approved statuses: `present`, `absent`, `late`, `excused`.
