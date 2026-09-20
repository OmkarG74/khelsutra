# Module: Decimal-Safe Payroll Engine

## Database Tables
- `salary_structures`
- `payroll_periods`
- `payroll`

## Decimal-Safe Math (Section 37 & 62)
```
Gross Salary = Basic Salary + Allowances + Overtime Amount + Bonus
Total Deductions = Tax + Deductions + Other Deductions
Net Salary = Gross Salary - Total Deductions
```
- Implemented with BCMath / round precision (never floating-point approximations).
- Period Statuses: `draft`, `processing`, `processed`, `locked`.
- Locked periods cannot be modified by payroll runs.
- Payment Statuses: `pending`, `processed`, `paid`, `cancelled`.
