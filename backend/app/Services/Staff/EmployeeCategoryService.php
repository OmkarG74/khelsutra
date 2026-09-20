<?php

namespace App\Services\Staff;

use App\Services\BaseService;
use App\Services\Audit\AuditLogService;
use PDO;

class EmployeeCategoryService extends BaseService
{
    protected AuditLogService $auditLog;

    public function __construct(?PDO $pdo = null, ?AuditLogService $auditLog = null)
    {
        parent::__construct($pdo);
        $this->auditLog = $auditLog ?? new AuditLogService($this->pdo);
    }

    public function listCategories(int $orgId): array
    {
        if (!$this->pdo) return [];
        $stmt = $this->pdo->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM employees WHERE employee_category_id = c.id AND deleted_at IS NULL) as employee_count
            FROM employee_categories c 
            WHERE c.organization_id = :org_id AND c.deleted_at IS NULL 
            ORDER BY c.name ASC
        ");
        $stmt->execute([':org_id' => $orgId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createCategory(int $orgId, array $data, ?int $performedBy = null): ?array
    {
        if (!$this->pdo || empty($data['name'])) return null;

        $stmt = $this->pdo->prepare("
            INSERT INTO employee_categories (organization_id, name, description, status, created_at, updated_at)
            VALUES (:org_id, :name, :description, :status, NOW(), NOW())
        ");
        $stmt->execute([
            ':org_id' => $orgId,
            ':name' => trim($data['name']),
            ':description' => $data['description'] ?? null,
            ':status' => $data['status'] ?? 'active',
        ]);
        $newId = (int)$this->pdo->lastInsertId();

        $this->auditLog->log($orgId, $performedBy, 'CREATE', 'EmployeeCategory', 'employee_categories', $newId, null, $data, "Created category {$data['name']}");
        return $this->getCategory($orgId, $newId);
    }

    public function getCategory(int $orgId, int $id): ?array
    {
        if (!$this->pdo) return null;
        $stmt = $this->pdo->prepare("SELECT * FROM employee_categories WHERE id = :id AND organization_id = :org_id AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([':id' => $id, ':org_id' => $orgId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function updateCategory(int $orgId, int $id, array $data, ?int $performedBy = null): ?array
    {
        if (!$this->pdo) return null;
        $existing = $this->getCategory($orgId, $id);
        if (!$existing) return null;

        $stmt = $this->pdo->prepare("
            UPDATE employee_categories SET 
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

        $updated = $this->getCategory($orgId, $id);
        $this->auditLog->log($orgId, $performedBy, 'UPDATE', 'EmployeeCategory', 'employee_categories', $id, $existing, $updated, "Updated category #{$id}");
        return $updated;
    }
}
