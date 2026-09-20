# Module: Leave Management

## Database Tables
- `leave_types`
- `leave_requests`

## Key Validation Rules
- **Applicant Types**: `employee`, `athlete`. (Coaches apply via their `employee_id`).
- **Dates Integrity**: `end_date >= start_date` and `total_days > 0`.
- **Overlap Prevention**: Existing pending or approved leaves in conflicting windows are blocked.
- **Separation of Duties**: Applicants cannot approve or reject their own leave requests.
- **Statuses**: `pending`, `approved`, `rejected`, `cancelled`.
