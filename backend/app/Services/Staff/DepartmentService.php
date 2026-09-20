<?php

namespace App\Services\Staff;

use App\Services\BaseService;
use App\Services\Audit\AuditLogService;
use PDO;

class DepartmentService extends BaseService
{
    protected AuditLogService $auditLog;

    public function __construct(?PDO $pdo = null, ?AuditLogService $auditLog = null)
    {
        parent::__construct($pdo);
        $this->auditLog = $auditLog ?? new AuditLogService($this->pdo);
    }

    public function listDepartments(int $orgId): array
    {
        if (!$this->pdo) return [];
        $stmt = $this->pdo->prepare("
            SELECT d.*, 
                   (SELECT COUNT(*) FROM employees WHERE department_id = d.id AND deleted_at IS NULL) as employee_count
            FROM departments d 
            WHERE d.organization_id = :org_id AND d.deleted_at IS NULL 
            ORDER BY d.name ASC
        ");
        $stmt->execute([':org_id' => $orgId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createDepartment(int $orgId, array $data, ?int $performedBy = null): ?array
    {
        if (!$this->pdo || empty($data['name'])) return null;

        $stmt = $this->pdo->prepare("
            INSERT INTO departments (organization_id, name, description, status, created_at, updated_at)
            VALUES (:org_id, :name, :description, :status, NOW(), NOW())
        ");
        $stmt->execute([
            ':org_id' => $orgId,
            ':name' => trim($data['name']),
            ':description' => $data['description'] ?? null,
            ':status' => $data['status'] ?? 'active',
        ]);
        $newId = (int)$this->pdo->lastInsertId();

        $this->auditLog->log($orgId, $performedBy, 'CREATE', 'Department', 'departments', $newId, null, $data, "Created department {$data['name']}");
        return $this->getDepartment($orgId, $newId);
    }

    public function getDepartment(int $orgId, int $id): ?array
    {
        if (!$this->pdo) return null;
        $stmt = $this->pdo->prepare("SELECT * FROM departments WHERE id = :id AND organization_id = :org_id AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([':id' => $id, ':org_id' => $orgId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function updateDepartment(int $orgId, int $id, array $data, ?int $performedBy = null): ?array
    {
        if (!$this->pdo) return null;
        $existing = $this->getDepartment($orgId, $id);
        if (!$existing) return null;

        $stmt = $this->pdo->prepare("
            UPDATE departments SET 
                name = :name,
                description = :description,
                status = :status,
                updated_at = NOW()
            WHERE id = :id AND organization_id = :org_id AND deleted_at IS NULL
        ");
        $stmt->execute([
            ':name' => trim($data['name'] ?? $existing['name']),
            ':description' => $data['description'] ?? $existing['description'],
            ':status' => $data['status'] ?? $existing['status'],
            ':id' => $id,
            ':org_id' => $orgId,
        ]);

        $updated = $this->getDepartment($orgId, $id);
        $this->auditLog->log($orgId, $performedBy, 'UPDATE', 'Department', 'departments', $id, $existing, $updated, "Updated department #{$id}");
        return $updated;
    }
}
