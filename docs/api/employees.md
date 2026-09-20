# API Specification: Staff, Attendance, Leave & Payroll

### Staff & HR
- `GET /api/v1/employees`: Filter roster by search, department, category, and status.
- `POST /api/v1/employees`: Create employee using the 6 schema-backed sections.
- `GET /api/v1/employees/{id}`: Detailed employee profile + linked Coach Profile.
- `PUT /api/v1/employees/{id}`: Update employee details.
- `GET/POST /api/v1/departments`: Manage academy departments.
- `GET/POST /api/v1/employee-categories`: Manage employee categories.
- `GET/POST /api/v1/employees/{id}/documents`: Manage employee storage paths.

### Attendance
- `POST /api/v1/attendance/training`: Record training attendance (athlete XOR coach XOR staff).
- `POST /api/v1/attendance/matches`: Record match lineup attendance.

### Leave
- `GET/POST /api/v1/leave/types`: Configure leave policies.
- `GET /api/v1/leave/requests`: List applications.
- `POST /api/v1/leave/requests`: Submit leave request with dates validation.
- `POST /api/v1/leave/requests/{id}/review`: Approve/reject (with separation of duties).

### Payroll
- `GET /api/v1/payroll/dashboard`: KPI metrics (Gross, Deductions, Net, pending payments).
- `GET/POST /api/v1/payroll/salary-structures`: Define employee salary components.
- `GET/POST /api/v1/payroll/periods`: Manage monthly payroll periods.
- `POST /api/v1/payroll/periods/{id}/lock`: Lock period.
- `GET /api/v1/payroll`: Disbursement ledger.
- `PUT /api/v1/payroll/{id}`: Update disbursement status (pending/processed/paid).
