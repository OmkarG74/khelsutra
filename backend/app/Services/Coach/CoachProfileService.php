<?php

namespace App\Services\Coach;

use App\Services\BaseService;
use App\Services\Audit\AuditLogService;
use PDO;

class CoachProfileService extends BaseService
{
    protected AuditLogService $auditLog;

    public function __construct(?PDO $pdo = null, ?AuditLogService $auditLog = null)
    {
        parent::__construct($pdo);
        $this->auditLog = $auditLog ?? new AuditLogService($this->pdo);
    }

    public function getByEmployeeId(int $orgId, int $employeeId): ?array
    {
        if (!$this->pdo) return null;
        $stmt = $this->pdo->prepare("SELECT * FROM coach_profiles WHERE employee_id = :emp_id AND organization_id = :org_id AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([':emp_id' => $employeeId, ':org_id' => $orgId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createOrUpdateProfile(int $orgId, int $employeeId, array $data, ?int $performedBy = null): ?array
    {
        if (!$this->pdo) return null;

        $existing = $this->getByEmployeeId($orgId, $employeeId);
        $coachCode = trim($data['coach_code'] ?? ($existing['coach_code'] ?? 'COACH-' . strtoupper(bin2hex(random_bytes(2)))));

        if ($existing) {
            $stmt = $this->pdo->prepare("
                UPDATE coach_profiles SET
                    specialization = :spec,
                    qualification = :qual,
                    certifications = :cert,
                    experience_years = :exp,
                    license_number = :lic_num,
                    license_expiry_date = :lic_exp,
                    status = :status,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :id AND organization_id = :org_id
            ");
            $stmt->execute([
                ':spec' => $data['specialization'] ?? $existing['specialization'],
                ':qual' => $data['qualification'] ?? $existing['qualification'],
                ':cert' => $data['certifications'] ?? $existing['certifications'],
                ':exp' => isset($data['experience_years']) ? (float)$data['experience_years'] : $existing['experience_years'],
                ':lic_num' => $data['license_number'] ?? $existing['license_number'],
                ':lic_exp' => !empty($data['license_expiry_date']) ? $data['license_expiry_date'] : $existing['license_expiry_date'],
                ':status' => $data['status'] ?? $existing['status'],
                ':notes' => $data['notes'] ?? $existing['notes'],
                ':id' => $existing['id'],
                ':org_id' => $orgId,
            ]);
            $profileId = (int)$existing['id'];
        } else {
            $stmt = $this->pdo->prepare("
                INSERT INTO coach_profiles (
                    organization_id, employee_id, coach_code, specialization,
                    qualification, certifications, experience_years, joining_date,
                    license_number, license_expiry_date, status, notes,
                    created_at, updated_at
                ) VALUES (
                    :org_id, :emp_id, :code, :spec,
                    :qual, :cert, :exp, :joining,
                    :lic_num, :lic_exp, :status, :notes,
                    NOW(), NOW()
                )
            ");
            $stmt->execute([
                ':org_id' => $orgId,
                ':emp_id' => $employeeId,
                ':code' => $coachCode,
                ':spec' => $data['specialization'] ?? null,
                ':qual' => $data['qualification'] ?? null,
                ':cert' => $data['certifications'] ?? null,
                ':exp' => isset($data['experience_years']) ? (float)$data['experience_years'] : null,
                ':joining' => !empty($data['joining_date']) ? $data['joining_date'] : date('Y-m-d'),
                ':lic_num' => $data['license_number'] ?? null,
                ':lic_exp' => !empty($data['license_expiry_date']) ? $data['license_expiry_date'] : null,
                ':status' => $data['status'] ?? 'active',
                ':notes' => $data['notes'] ?? null,
            ]);
            $profileId = (int)$this->pdo->lastInsertId();
        }

        $this->auditLog->log($orgId, $performedBy, 'COACH_PROFILE_SYNC', 'Coach', 'coach_profiles', $profileId, $existing, $data, "Synchronized coach profile for employee #{$employeeId}");

        return $this->getByEmployeeId($orgId, $employeeId);
    }
}
