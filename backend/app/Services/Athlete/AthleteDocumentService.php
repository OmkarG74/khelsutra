<?php

namespace App\Services\Athlete;

use App\Services\BaseService;
use App\Services\Audit\AuditLogService;
use PDO;

class AthleteDocumentService extends BaseService
{
    protected AuditLogService $auditLog;

    public const ALLOWED_MIME_TYPES = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

    public const DOCUMENT_TYPES = [
        'id_proof' => 'Government ID / Aadhaar',
        'birth_certificate' => 'Birth Certificate',
        'passport' => 'Passport',
        'medical_certificate' => 'Medical Certificate',
        'sports_certificate' => 'Sports Certificate',
        'consent_form' => 'Consent Form',
        'other' => 'Other Document',
    ];

    public function __construct(?PDO $pdo = null, ?AuditLogService $auditLog = null)
    {
        parent::__construct($pdo);
        $this->auditLog = $auditLog ?? new AuditLogService($this->pdo);
    }

    /**
     * Get storage directory for athlete documents
     */
    public function getStorageDir(int $orgId, int $athleteId): string
    {
        $dir = __DIR__ . '/../../../../storage/app/documents/athletes/' . $orgId . '/' . $athleteId;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return realpath($dir) ?: $dir;
    }

    /**
     * List all documents for an athlete
     */
    public function listDocuments(int $orgId, int $athleteId): array
    {
        if (!$this->pdo) return [];

        $stmt = $this->pdo->prepare("
            SELECT ad.*, u.first_name as uploader_first_name, u.last_name as uploader_last_name
            FROM athlete_documents ad
            LEFT JOIN users u ON ad.uploaded_by = u.id
            WHERE ad.athlete_id = :ath_id AND ad.organization_id = :org_id AND ad.deleted_at IS NULL
            ORDER BY ad.id DESC
        ");
        $stmt->execute([':ath_id' => $athleteId, ':org_id' => $orgId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get a specific document
     */
    public function getDocument(int $orgId, int $athleteId, int $docId): ?array
    {
        if (!$this->pdo) return null;

        $stmt = $this->pdo->prepare("
            SELECT * FROM athlete_documents
            WHERE id = :id AND athlete_id = :ath_id AND organization_id = :org_id AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':id' => $docId, ':ath_id' => $athleteId, ':org_id' => $orgId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Store an uploaded file and create athlete_document record
     */
    public function addDocument(int $orgId, int $athleteId, array $data, array $file, ?int $uploadedBy = null): ?array
    {
        if (!$this->pdo) return null;

        // Validate upload errors
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('File upload failed with error code: ' . ($file['error'] ?? 'unknown'));
        }

        // Validate file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException('File exceeds maximum allowed size of 10MB.');
        }

        // Validate MIME type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!array_key_exists($mime, self::ALLOWED_MIME_TYPES)) {
            throw new \InvalidArgumentException('Invalid file type (' . htmlspecialchars($mime) . '). Only PDF, JPG, PNG, and WebP are allowed.');
        }

        $extension = self::ALLOWED_MIME_TYPES[$mime];
        $storageDir = $this->getStorageDir($orgId, $athleteId);
        $safeFileName = 'doc_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $destination = $storageDir . DIRECTORY_SEPARATOR . $safeFileName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            // In test environment where is_uploaded_file may be false, try copy
            if (!copy($file['tmp_name'], $destination)) {
                throw new \RuntimeException('Failed to move uploaded document to storage.');
            }
        }

        // Relative path stored in database
        $storedPath = 'documents/athletes/' . $orgId . '/' . $athleteId . '/' . $safeFileName;

        $docType = $data['document_type'] ?? 'other';
        if (!array_key_exists($docType, self::DOCUMENT_TYPES)) {
            $docType = 'other';
        }

        $docName = trim($data['document_name'] ?? '');
        if (empty($docName)) {
            $docName = self::DOCUMENT_TYPES[$docType] ?? 'Document';
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO athlete_documents (
                organization_id, athlete_id, document_type, document_name,
                document_number, file_path, issue_date, expiry_date, notes,
                uploaded_by, created_at, updated_at
            ) VALUES (
                :org_id, :ath_id, :doc_type, :doc_name,
                :doc_num, :file_path, :issue, :expiry, :notes,
                :uploaded_by, NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':org_id' => $orgId,
            ':ath_id' => $athleteId,
            ':doc_type' => $docType,
            ':doc_name' => $docName,
            ':doc_num' => !empty($data['document_number']) ? trim($data['document_number']) : null,
            ':file_path' => $storedPath,
            ':issue' => !empty($data['issue_date']) ? $data['issue_date'] : null,
            ':expiry' => !empty($data['expiry_date']) ? $data['expiry_date'] : null,
            ':notes' => !empty($data['notes']) ? trim($data['notes']) : null,
            ':uploaded_by' => $uploadedBy,
        ]);

        $newId = (int)$this->pdo->lastInsertId();

        $this->auditLog->log(
            $orgId,
            $uploadedBy,
            'UPLOAD',
            'AthleteDocument',
            'athlete_documents',
            $newId,
            null,
            ['document_type' => $docType, 'document_name' => $docName, 'file_path' => $storedPath],
            "Uploaded document '{$docName}' for athlete #{$athleteId}"
        );

        return $this->getDocument($orgId, $athleteId, $newId);
    }

    /**
     * Delete an athlete document (soft delete)
     */
    public function deleteDocument(int $orgId, int $athleteId, int $docId, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;

        $doc = $this->getDocument($orgId, $athleteId, $docId);
        if (!$doc) return false;

        $stmt = $this->pdo->prepare("
            UPDATE athlete_documents
            SET deleted_at = NOW()
            WHERE id = :id AND athlete_id = :ath_id AND organization_id = :org_id
        ");
        $ok = $stmt->execute([':id' => $docId, ':ath_id' => $athleteId, ':org_id' => $orgId]);

        if ($ok) {
            $this->auditLog->log(
                $orgId,
                $performedBy,
                'DELETE',
                'AthleteDocument',
                'athlete_documents',
                $docId,
                $doc,
                null,
                "Deleted document '{$doc['document_name']}' for athlete #{$athleteId}"
            );
        }

        return $ok;
    }

    /**
     * Resolve full disk path for a stored document file
     */
    public function getFullFilePath(string $storedPath): ?string
    {
        $fullPath = __DIR__ . '/../../../../storage/app/' . ltrim($storedPath, '/\\');
        $real = realpath($fullPath);
        if ($real && file_exists($real)) {
            return $real;
        }
        return file_exists($fullPath) ? $fullPath : null;
    }
}
