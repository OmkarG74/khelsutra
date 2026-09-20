<?php

namespace App\Services\Staff;

use App\Services\BaseService;
use App\Services\Audit\AuditLogService;
use PDO;

class EmployeeService extends BaseService
{
    protected AuditLogService $auditLog;

    public function __construct(?PDO $pdo = null, ?AuditLogService $auditLog = null)
    {
        parent::__construct($pdo);
        $this->auditLog = $auditLog ?? new AuditLogService($this->pdo);
    }

    public function listEmployees(int $orgId, array $filters = [], int $limit = 50, int $offset = 0): array
    {
        if (!$this->pdo) return [];

        $sql = "
            SELECT e.*, d.name as department_name, ec.name as category_name,
                   cp.coach_code, cp.specialization, cp.id as coach_profile_id
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN employee_categories ec ON e.employee_category_id = ec.id
            LEFT JOIN coach_profiles cp ON e.id = cp.employee_id AND cp.deleted_at IS NULL
            WHERE e.organization_id = :org_id AND e.deleted_at IS NULL
        ";
        $params = [':org_id' => $orgId];

        if (!empty($filters['status'])) {
            $sql .= " AND e.employment_status = :status ";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['department_id'])) {
            $sql .= " AND e.department_id = :dept_id ";
            $params[':dept_id'] = (int)$filters['department_id'];
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND e.employee_category_id = :cat_id ";
            $params[':cat_id'] = (int)$filters['category_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (e.first_name LIKE :search OR e.last_name LIKE :search OR e.employee_code LIKE :search OR e.email LIKE :search) ";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        $sql .= " ORDER BY e.id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getEmployee(int $orgId, int $id): ?array
    {
        if (!$this->pdo) return null;
        $stmt = $this->pdo->prepare("
            SELECT e.*, d.name as department_name, ec.name as category_name,
                   cp.id as coach_profile_id, cp.coach_code, cp.specialization, cp.qualification, cp.certifications, cp.experience_years, cp.license_number, cp.license_expiry_date
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN employee_categories ec ON e.employee_category_id = ec.id
            LEFT JOIN coach_profiles cp ON e.id = cp.employee_id AND cp.deleted_at IS NULL
            WHERE e.id = :id AND e.organization_id = :org_id AND e.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':org_id' => $orgId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getEmployeeDetails(int $id, int $orgId): ?array
    {
        $res = $this->getEmployee($orgId, $id);
        if (!$res) {
            throw new \Exception("Employee #{$id} not found in organisation #{$orgId}.");
        }
        if (!empty($res['coach_profile_id'])) {
            $res['coach_profile'] = [
                'id' => $res['coach_profile_id'],
                'coach_code' => $res['coach_code'],
                'specialization' => $res['specialization'],
                'qualification' => $res['qualification'],
                'certifications' => $res['certifications'],
                'experience_years' => $res['experience_years'],
                'license_number' => $res['license_number'],
                'license_expiry_date' => $res['license_expiry_date'],
            ];
        }
        return $res;
    }

    public function createEmployee(int $orgId, array $data, ?int $performedBy = null): ?array
    {
        if (!$this->pdo) return null;

        $firstName = trim($data['first_name'] ?? '');
        if (empty($firstName)) return null;

        $empCode = trim($data['employee_code'] ?? 'EMP-' . strtoupper(bin2hex(random_bytes(3))));

        $stmt = $this->pdo->prepare("
            INSERT INTO employees (
                organization_id, user_id, employee_code, first_name, middle_name, last_name,
                photo_path, date_of_birth, gender, blood_group, phone, email,
                address_line1, address_line2, city, state, country, postal_code,
                department_id, employee_category_id, designation, joining_date,
                employment_type, employment_status,
                emergency_contact_name, emergency_contact_phone, emergency_contact_relationship,
                bank_name, bank_account_number, bank_ifsc, notes,
                created_at, updated_at
            ) VALUES (
                :org_id, :user_id, :emp_code, :fname, :mname, :lname,
                :photo, :dob, :gender, :blood, :phone, :email,
                :addr1, :addr2, :city, :state, :country, :postal,
                :dept_id, :cat_id, :designation, :joining_date,
                :emp_type, :emp_status,
                :em_name, :em_phone, :em_rel,
                :bank_name, :bank_acc, :bank_ifsc, :notes,
                NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':org_id' => $orgId,
            ':user_id' => !empty($data['user_id']) ? (int)$data['user_id'] : null,
            ':emp_code' => $empCode,
            ':fname' => $firstName,
            ':mname' => $data['middle_name'] ?? null,
            ':lname' => $data['last_name'] ?? null,
            ':photo' => $data['photo_path'] ?? null,
            ':dob' => !empty($data['date_of_birth']) ? $data['date_of_birth'] : null,
            ':gender' => $data['gender'] ?? 'not_specified',
            ':blood' => $data['blood_group'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':email' => $data['email'] ?? null,
            ':addr1' => $data['address_line1'] ?? null,
            ':addr2' => $data['address_line2'] ?? null,
            ':city' => $data['city'] ?? null,
            ':state' => $data['state'] ?? null,
            ':country' => $data['country'] ?? 'India',
            ':postal' => $data['postal_code'] ?? null,
            ':dept_id' => !empty($data['department_id']) ? (int)$data['department_id'] : null,
            ':cat_id' => !empty($data['employee_category_id']) ? (int)$data['employee_category_id'] : null,
            ':designation' => $data['designation'] ?? null,
            ':joining_date' => !empty($data['joining_date']) ? $data['joining_date'] : date('Y-m-d'),
            ':emp_type' => $data['employment_type'] ?? 'full_time',
            ':emp_status' => $data['employment_status'] ?? 'active',
            ':em_name' => $data['emergency_contact_name'] ?? null,
            ':em_phone' => $data['emergency_contact_phone'] ?? null,
            ':em_rel' => $data['emergency_contact_relationship'] ?? null,
            ':bank_name' => $data['bank_name'] ?? null,
            ':bank_acc' => $data['bank_account_number'] ?? null,
            ':bank_ifsc' => $data['bank_ifsc'] ?? null,
            ':notes' => $data['notes'] ?? null,
        ]);

        $newId = (int)$this->pdo->lastInsertId();

        $this->auditLog->log($orgId, $performedBy, 'EMPLOYEE_CREATE', 'Employee', 'employees', $newId, null, $data, "Created employee {$firstName} ({$empCode})");

        return $this->getEmployee($orgId, $newId);
    }

    public function updateEmployee(int $orgId, int $id, array $data, ?int $performedBy = null): ?array
    {
        if (!$this->pdo) return null;
        $existing = $this->getEmployee($orgId, $id);
        if (!$existing) return null;

        $stmt = $this->pdo->prepare("
            UPDATE employees SET
                first_name = :fname,
                middle_name = :mname,
                last_name = :lname,
                date_of_birth = :dob,
                gender = :gender,
                blood_group = :blood,
                phone = :phone,
                email = :email,
                address_line1 = :addr1,
                address_line2 = :addr2,
                city = :city,
                state = :state,
                country = :country,
                postal_code = :postal,
                department_id = :dept_id,
                employee_category_id = :cat_id,
                designation = :designation,
                employment_type = :emp_type,
                employment_status = :emp_status,
                emergency_contact_name = :em_name,
                emergency_contact_phone = :em_phone,
                emergency_contact_relationship = :em_rel,
                bank_name = :bank_name,
                bank_account_number = :bank_acc,
                bank_ifsc = :bank_ifsc,
                notes = :notes,
                updated_at = NOW()
            WHERE id = :id AND organization_id = :org_id AND deleted_at IS NULL
        ");

        $stmt->execute([
            ':fname' => $data['first_name'] ?? $existing['first_name'],
            ':mname' => $data['middle_name'] ?? $existing['middle_name'],
            ':lname' => $data['last_name'] ?? $existing['last_name'],
            ':dob' => !empty($data['date_of_birth']) ? $data['date_of_birth'] : $existing['date_of_birth'],
            ':gender' => $data['gender'] ?? $existing['gender'],
            ':blood' => $data['blood_group'] ?? $existing['blood_group'],
            ':phone' => $data['phone'] ?? $existing['phone'],
            ':email' => $data['email'] ?? $existing['email'],
            ':addr1' => $data['address_line1'] ?? $existing['address_line1'],
            ':addr2' => $data['address_line2'] ?? $existing['address_line2'],
            ':city' => $data['city'] ?? $existing['city'],
            ':state' => $data['state'] ?? $existing['state'],
            ':country' => $data['country'] ?? $existing['country'],
            ':postal' => $data['postal_code'] ?? $existing['postal_code'],
            ':dept_id' => !empty($data['department_id']) ? (int)$data['department_id'] : $existing['department_id'],
            ':cat_id' => !empty($data['employee_category_id']) ? (int)$data['employee_category_id'] : $existing['employee_category_id'],
            ':designation' => $data['designation'] ?? $existing['designation'],
            ':emp_type' => $data['employment_type'] ?? $existing['employment_type'],
            ':emp_status' => $data['employment_status'] ?? $existing['employment_status'],
            ':em_name' => $data['emergency_contact_name'] ?? $existing['emergency_contact_name'],
            ':em_phone' => $data['emergency_contact_phone'] ?? $existing['emergency_contact_phone'],
            ':em_rel' => $data['emergency_contact_relationship'] ?? $existing['emergency_contact_relationship'],
            ':bank_name' => $data['bank_name'] ?? $existing['bank_name'],
            ':bank_acc' => $data['bank_account_number'] ?? $existing['bank_account_number'],
            ':bank_ifsc' => $data['bank_ifsc'] ?? $existing['bank_ifsc'],
            ':notes' => $data['notes'] ?? $existing['notes'],
            ':id' => $id,
            ':org_id' => $orgId,
        ]);

        $updated = $this->getEmployee($orgId, $id);
        $this->auditLog->log($orgId, $performedBy, 'EMPLOYEE_UPDATE', 'Employee', 'employees', $id, $existing, $updated, "Updated employee #{$id}");

        return $updated;
    }
}
