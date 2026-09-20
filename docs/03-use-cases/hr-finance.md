# Use Case: HR & Finance Management

## UC-HRF-01: Leave Request & Approval
- **Primary Actor**: Employee, Coach, or Athlete (Requester); HR & Finance (Approver).
- **Permission**: `leave.request` for requester; `leave.approve` for approver.
- **Main Flow**:
  1. Requester submits leave type, start date, end date, and reason.
  2. HR & Finance reviews pending leave requests.
  3. Approver marks status as `approved` or `rejected` with comments.
  4. System notifies requester.

## UC-HRF-02: Monthly Payroll Processing
- **Primary Actor**: HR & Finance.
- **Permission**: `payroll.manage`.
- **Main Flow**:
  1. HR selects payroll period and employee cohort.
  2. System aggregates:
     - `basic_salary`
     - `allowances`
     - `overtime_pay`
     - `bonus`
     - `deductions`
     - `tax`
     - `other_deductions`
     - Computes `net_salary = (basic + allowances + overtime + bonus) - (deductions + tax + other_deductions)`
  3. HR verifies and confirms payroll run.
- **Business Rule**: Do NOT implement PF, ESI, or statutory schemes.

## UC-HRF-03: Budgeting & Expense Tracking
- **Primary Actor**: HR & Finance.
- **Permission**: `finance.manage`.
- **Main Flow**: Creates fiscal budgets, records expenses against budget categories, and records incoming revenues.
- **Implementation Status**: Skeleton active; bank reconciliation `TODO: TO BE COMPLETED`.
