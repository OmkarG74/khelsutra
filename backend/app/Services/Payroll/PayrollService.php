<?php

namespace App\Services\Payroll;

use App\Services\BaseService;
use App\Services\Audit\AuditLogService;
use PDO;
use Exception;

class PayrollService extends BaseService
{
    protected PayrollCalculationService $calculator;
    protected AuditLogService $auditLog;

    public function __construct(?PDO $pdo = null, ?PayrollCalculationService $calculator = null, ?AuditLogService $auditLog = null)
    {
        parent::__construct($pdo);
        $this->calculator = $calculator ?? new PayrollCalculationService();
        $this->auditLog = $auditLog ?? new AuditLogService($this->pdo);
    }

    // --- 1. Salary Structures ---

    public function getSalaryStructure(int $orgId, int $employeeId): ?array
    {
        if (!$this->pdo) return null;
        $stmt = $this->pdo->prepare("SELECT * FROM salary_structures WHERE employee_id = :emp_id AND organization_id = :org_id AND status = 'active' ORDER BY effective_from DESC LIMIT 1");
        $stmt->execute([':emp_id' => $employeeId, ':org_id' => $orgId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function setSalaryStructure(int $orgId, int $employeeId, array $data, ?int $performedBy = null): ?array
    {
        if (!$this->pdo) return null;

        $effectiveFrom = $data['effective_from'] ?? date('Y-m-d');
        $basic = max(0.0, (float)($data['basic_salary'] ?? 0));
        $allow = max(0.0, (float)($data['allowances'] ?? 0));
        $deduc = max(0.0, (float)($data['deduction'] ?? 0));
        $otRate = max(0.0, (float)($data['overtime_rate'] ?? 0));
        $bonus = max(0.0, (float)($data['bonus_default'] ?? 0));
        $tax = max(0.0, (float)($data['tax_default'] ?? 0));
        $other = max(0.0, (float)($data['other_deductions_default'] ?? 0));

        $stmt = $this->pdo->prepare("
            INSERT INTO salary_structures (
                organization_id, employee_id, effective_from, basic_salary,
                allowances, deduction, overtime_rate, bonus_default,
                tax_default, other_deductions_default, status,
                created_at, updated_at
            ) VALUES (
                :org_id, :emp_id, :effective, :basic,
                :allow, :deduc, :ot_rate, :bonus,
                :tax, :other, 'active',
                NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':org_id' => $orgId,
            ':emp_id' => $employeeId,
            ':effective' => $effectiveFrom,
            ':basic' => $basic,
            ':allow' => $allow,
            ':deduc' => $deduc,
            ':ot_rate' => $otRate,
            ':bonus' => $bonus,
            ':tax' => $tax,
            ':other' => $other,
        ]);
        $newId = (int)$this->pdo->lastInsertId();

        $this->auditLog->log($orgId, $performedBy, 'PAYROLL_CREATE', 'Payroll', 'salary_structures', $newId, null, $data, "Configured salary structure for employee #{$employeeId}");

        return $this->getSalaryStructure($orgId, $employeeId);
    }

    // --- 2. Payroll Periods ---

    public function listPeriods(int $orgId): array
    {
        if (!$this->pdo) return [];
        $stmt = $this->pdo->prepare("
            SELECT pp.*, 
                   (SELECT COUNT(*) FROM payroll WHERE payroll_period_id = pp.id) as payroll_records_count,
                   (SELECT COALESCE(SUM(net_salary), 0) FROM payroll WHERE payroll_period_id = pp.id) as total_net_disbursement
            FROM payroll_periods pp 
            WHERE pp.organization_id = :org_id 
            ORDER BY pp.start_date DESC
        ");
        $stmt->execute([':org_id' => $orgId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getPeriod(int $orgId, int $periodId): ?array
    {
        if (!$this->pdo) return null;
        $stmt = $this->pdo->prepare("SELECT * FROM payroll_periods WHERE id = :id AND organization_id = :org_id LIMIT 1");
        $stmt->execute([':id' => $periodId, ':org_id' => $orgId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createPeriod(int $orgId, array $data, ?int $performedBy = null): ?array
    {
        if (!$this->pdo || empty($data['period_name']) || empty($data['start_date']) || empty($data['end_date'])) {
            return null;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO payroll_periods (organization_id, period_name, start_date, end_date, status, created_at, updated_at)
            VALUES (:org_id, :name, :start, :end, 'draft', NOW(), NOW())
        ");
        $stmt->execute([
            ':org_id' => $orgId,
            ':name' => trim($data['period_name']),
            ':start' => $data['start_date'],
            ':end' => $data['end_date'],
        ]);
        $newId = (int)$this->pdo->lastInsertId();

        $this->auditLog->log($orgId, $performedBy, 'PAYROLL_CREATE', 'Payroll', 'payroll_periods', $newId, null, $data, "Created payroll period {$data['period_name']}");

        return $this->getPeriod($orgId, $newId);
    }

    public function updatePeriodStatus(int $orgId, int $periodId, string $status, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;
        $allowed = ['draft', 'processing', 'processed', 'locked'];
        if (!in_array($status, $allowed, true)) return false;

        $period = $this->getPeriod($orgId, $periodId);
        if (!$period) return false;

        // If currently locked, prevent unlocking or modifying without authority
        if ($period['status'] === 'locked') {
            throw new Exception("Payroll period #{$periodId} is locked and cannot be modified.");
        }

        $stmt = $this->pdo->prepare("
            UPDATE payroll_periods SET
                status = :status,
                processed_by = :by,
                processed_at = IF(:status2 = 'processed' OR :status3 = 'locked', NOW(), processed_at),
                updated_at = NOW()
            WHERE id = :id AND organization_id = :org_id
        ");

        $ok = $stmt->execute([
            ':status' => $status,
            ':by' => $performedBy,
            ':status2' => $status,
            ':status3' => $status,
            ':id' => $periodId,
            ':org_id' => $orgId,
        ]);

        if ($ok) {
            $this->auditLog->log($orgId, $performedBy, 'PAYROLL_PROCESS', 'Payroll', 'payroll_periods', $periodId, ['status' => $period['status']], ['status' => $status], "Updated payroll period status to {$status}");
        }

        return $ok;
    }

    // --- 3. Payroll Records & Processing ---

    public function listPayrollRecords(int $orgId, ?int $periodId = null, int $limit = 50, int $offset = 0): array
    {
        if (!$this->pdo) return [];

        $sql = "
            SELECT p.*, pp.period_name, pp.status as period_status,
                   CONCAT(e.first_name, ' ', e.last_name) as employee_name, e.employee_code, e.designation,
                   d.name as department_name
            FROM payroll p
            JOIN payroll_periods pp ON p.payroll_period_id = pp.id
            JOIN employees e ON p.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE p.organization_id = :org_id
        ";
        $params = [':org_id' => $orgId];

        if ($periodId) {
            $sql .= " AND p.payroll_period_id = :pid ";
            $params[':pid'] = $periodId;
        }

        $sql .= " ORDER BY p.id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function processEmployeePayroll(int $orgId, int $periodId, int $employeeId, array $overrides = [], ?int $performedBy = null): ?array
    {
        if (!$this->pdo) return null;

        $period = $this->getPeriod($orgId, $periodId);
        if (!$period || $period['status'] === 'locked') {
            throw new Exception("Cannot process payroll for a missing or locked period.");
        }

        // Get salary structure
        $struct = $this->getSalaryStructure($orgId, $employeeId);
        if (!$struct) {
            throw new Exception("No active salary structure found for employee #{$employeeId}.");
        }

        $basic = (float)($overrides['basic_salary'] ?? $struct['basic_salary']);
        $allow = (float)($overrides['allowances'] ?? $struct['allowances']);
        $otHours = max(0.0, (float)($overrides['overtime_hours'] ?? 0));
        $otRate = (float)($struct['overtime_rate'] ?? 0);
        $otAmt = (float)($overrides['overtime_amount'] ?? ($otHours * $otRate));
        $bonus = (float)($overrides['bonus'] ?? $struct['bonus_default']);

        $tax = (float)($overrides['tax'] ?? $struct['tax_default']);
        $deduc = (float)($overrides['deductions'] ?? $struct['deduction']);
        $otherDeduc = (float)($overrides['other_deductions'] ?? $struct['other_deductions_default']);

        // Calculate using decimal-safe service
        $computed = $this->calculator->calculate($basic, $allow, $otAmt, $bonus, $tax, $deduc, $otherDeduc);

        $stmt = $this->pdo->prepare("
            INSERT INTO payroll (
                organization_id, payroll_period_id, employee_id,
                basic_salary, allowances, overtime_hours, overtime_amount,
                bonus, tax, deductions, other_deductions,
                gross_salary, net_salary, payment_status, remarks,
                created_at, updated_at
            ) VALUES (
                :org_id, :pid, :emp_id,
                :basic, :allow, :ot_h, :ot_a,
                :bonus, :tax, :deduc, :other,
                :gross, :net, 'pending', :remarks,
                NOW(), NOW()
            )
            ON DUPLICATE KEY UPDATE
                basic_salary = :basic2,
                allowances = :allow2,
                overtime_hours = :ot_h2,
                overtime_amount = :ot_a2,
                bonus = :bonus2,
                tax = :tax2,
                deductions = :deduc2,
                other_deductions = :other2,
                gross_salary = :gross2,
                net_salary = :net2,
                remarks = :remarks2,
                updated_at = NOW()
        ");

        $remarks = $overrides['remarks'] ?? 'Calculated from salary structure';

        $stmt->execute([
            ':org_id' => $orgId,
            ':pid' => $periodId,
            ':emp_id' => $employeeId,
            ':basic' => $computed['basic_salary'],
            ':allow' => $computed['allowances'],
            ':ot_h' => $otHours,
            ':ot_a' => $computed['overtime_amount'],
            ':bonus' => $computed['bonus'],
            ':tax' => $computed['tax'],
            ':deduc' => $computed['deductions'],
            ':other' => $computed['other_deductions'],
            ':gross' => $computed['gross_salary'],
            ':net' => $computed['net_salary'],
            ':remarks' => $remarks,
            ':basic2' => $computed['basic_salary'],
            ':allow2' => $computed['allowances'],
            ':ot_h2' => $otHours,
            ':ot_a2' => $computed['overtime_amount'],
            ':bonus2' => $computed['bonus'],
            ':tax2' => $computed['tax'],
            ':deduc2' => $computed['deductions'],
            ':other2' => $computed['other_deductions'],
            ':gross2' => $computed['gross_salary'],
            ':net2' => $computed['net_salary'],
            ':remarks2' => $remarks,
        ]);

        $this->auditLog->log($orgId, $performedBy, 'PAYROLL_PROCESS', 'Payroll', 'payroll', null, null, $computed, "Generated payroll for employee #{$employeeId} in period #{$periodId}");

        return $computed;
    }

    public function updatePaymentStatus(int $orgId, int $payrollId, string $status, ?string $date, ?string $ref, ?string $remarks, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;
        $allowed = ['pending', 'processed', 'paid', 'cancelled'];
        if (!in_array($status, $allowed, true)) return false;

        $stmt = $this->pdo->prepare("
            UPDATE payroll SET
                payment_status = :status,
                payment_date = :pdate,
                payment_reference = :pref,
                remarks = COALESCE(:remarks, remarks),
                updated_at = NOW()
            WHERE id = :id AND organization_id = :org_id
        ");

        $ok = $stmt->execute([
            ':status' => $status,
            ':pdate' => ($status === 'paid' ? ($date ?? date('Y-m-d')) : null),
            ':pref' => $ref,
            ':remarks' => $remarks,
            ':id' => $payrollId,
            ':org_id' => $orgId,
        ]);

        if ($ok) {
            $this->auditLog->log($orgId, $performedBy, 'PAYROLL_PAYMENT', 'Payroll', 'payroll', $payrollId, null, ['status' => $status, 'reference' => $ref], "Marked payroll #{$payrollId} payment as {$status}");
        }

        return $ok;
    }

    public function generatePayrollRecord(int $organizationId, int $payrollPeriodId, int $employeeId, float|string $overtimeHours = 0, float|string $bonus = 0): ?array
    {
        return $this->processEmployeePayroll($organizationId, $payrollPeriodId, $employeeId, [
            'overtime_hours' => $overtimeHours,
            'bonus' => $bonus
        ]);
    }

    public function getPayrollDashboardSummary(int $orgId): array
    {
        if (!$this->pdo) return [];

        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(DISTINCT employee_id) as employee_count,
                COALESCE(SUM(gross_salary), 0) as total_gross,
                COALESCE(SUM(tax + deductions + other_deductions), 0) as total_deductions,
                COALESCE(SUM(net_salary), 0) as total_net,
                COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_payments
            FROM payroll
            WHERE organization_id = :org_id
        ");
        $stmt->execute([':org_id' => $orgId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Latest period name
        $stmtPeriod = $this->pdo->prepare("SELECT period_name FROM payroll_periods WHERE organization_id = :org_id ORDER BY id DESC LIMIT 1");
        $stmtPeriod->execute([':org_id' => $orgId]);
        $res['current_period_name'] = $stmtPeriod->fetchColumn() ?: 'Active Period';

        return $res;
    }

    public function getPayrollDetails(int $id, int $orgId): ?array
    {
        if (!$this->pdo) return null;
        $stmt = $this->pdo->prepare("
            SELECT p.*, pp.period_name, pp.status as period_status,
                   e.first_name, e.last_name, e.employee_code, e.designation, e.bank_name, e.bank_account_number as bank_account_no,
                   d.name as department_name
            FROM payroll p
            JOIN payroll_periods pp ON p.payroll_period_id = pp.id
            JOIN employees e ON p.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE p.id = :id AND p.organization_id = :org_id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':org_id' => $orgId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function listSalaryStructures(int $orgId): array
    {
        if (!$this->pdo) return [];
        $stmt = $this->pdo->prepare("
            SELECT ss.*, e.first_name, e.last_name, e.employee_code, e.designation
            FROM salary_structures ss
            JOIN employees e ON ss.employee_id = e.id
            WHERE ss.organization_id = :org_id
            ORDER BY ss.id DESC
        ");
        $stmt->execute([':org_id' => $orgId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
