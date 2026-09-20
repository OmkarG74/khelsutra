# Module: Staff & HR Foundation

## Database Tables
- `employees`
- `departments`
- `employee_categories`
- `employee_documents`
- `coach_profiles`

## Features
- **6-Section Employee Form**:
  1. Personal Information (Employee code, name, gender, DOB, blood group)
  2. Contact Information (Phone, email, street address, city, state, postal code)
  3. Employment Details (Department, category, designation, joining date, type, status)
  4. Emergency Contact (Name, phone, relationship)
  5. Bank Information (Bank name, account number, IFSC)
  6. Notes
- **Coach Profile Foundation**:
  - Direct 1-to-1 relationship from `employees` to `coach_profiles`.
  - Member 2 builds detailed tactical management upon this foundation.
- **Employee Documents**:
  - Stores local or S3 URI references only (never binary in MySQL).
