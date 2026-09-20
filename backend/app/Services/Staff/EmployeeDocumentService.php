<?php

namespace App\Services\Staff;

use App\Services\BaseService;
use App\Services\Audit\AuditLogService;
use PDO;

class EmployeeDocumentService extends BaseService
{
    protected AuditLogService $auditLog;

    public function __construct(?PDO $pdo = null, ?AuditLogService $auditLog = null)
    {
        parent::__construct($pdo);
        $this->auditLog = $auditLog ?? new AuditLogService($this->pdo);
    }

    public function listDocuments(int $orgId, int $employeeId): array
    {
        if (!$this->pdo) return [];
        $stmt = $this->pdo->prepare("
            SELECT ed.*, u.first_name as uploader_first_name, u.last_name as uploader_last_name
            FROM employee_documents ed
            LEFT JOIN users u ON ed.uploaded_by = u.id
            WHERE ed.employee_id = :emp_id AND ed.organization_id = :org_id AND ed.deleted_at IS NULL
            ORDER BY ed.id DESC
        ");
        $stmt->execute([':emp_id' => $employeeId, ':org_id' => $orgId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function addDocument(int $orgId, int $employeeId, array $data, ?int $performedBy = null): ?array
    {
        if (!$this->pdo || empty($data['document_type']) || empty($data['file_path'])) {
            return null;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO employee_documents (
                organization_id, employee_id, document_type, document_number,
                file_path, issue_date, expiry_date, notes, uploaded_by,
                created_at, updated_at
            ) VALUES (
                :org_id, :emp_id, :doc_type, :doc_num,
                :file_path, :issue, :expiry, :notes, :uploaded_by,
                NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':org_id' => $orgId,
            ':emp_id' => $employeeId,
            ':doc_type' => trim($data['document_type']),
            ':doc_num' => $data['document_number'] ?? null,
            ':file_path' => trim($data['file_path']),
            ':issue' => !empty($data['issue_date']) ? $data['issue_date'] : null,
            ':expiry' => !empty($data['expiry_date']) ? $data['expiry_date'] : null,
            ':notes' => $data['notes'] ?? null,
            ':uploaded_by' => $performedBy,
        ]);

        $newId = (int)$this->pdo->lastInsertId();
        $this->auditLog->log($orgId, $performedBy, 'UPLOAD', 'EmployeeDocument', 'employee_documents', $newId, null, $data, "Uploaded document {$data['document_type']} for employee #{$employeeId}");

        return $this->getDocument($orgId, $newId);
    }

    public function getDocument(int $orgId, int $id): ?array
    {
        if (!$this->pdo) return null;
        $stmt = $this->pdo->prepare("SELECT * FROM employee_documents WHERE id = :id AND organization_id = :org_id AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([':id' => $id, ':org_id' => $orgId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
