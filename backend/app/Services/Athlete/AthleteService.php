<?php

namespace App\Services\Athlete;

use App\Repositories\Contracts\AthleteRepositoryInterface;
use App\Repositories\Eloquent\AthleteRepository;
use App\Services\Audit\AuditLogService;
use App\Services\BaseService;
use PDO;

class AthleteService
{
    protected AthleteRepositoryInterface $repository;
    protected AuditLogService $auditLog;
    protected AthleteDocumentService $documentService;
    protected ?PDO $pdo;

    public function __construct(?AthleteRepositoryInterface $repository = null, ?AuditLogService $auditLog = null, ?AthleteDocumentService $documentService = null)
    {
        $this->repository = $repository ?? new AthleteRepository();
        $this->pdo = BaseService::getDatabaseConnection();
        $this->auditLog = $auditLog ?? new AuditLogService($this->pdo);
        $this->documentService = $documentService ?? new AthleteDocumentService($this->pdo, $this->auditLog);
    }

    public function getDocumentService(): AthleteDocumentService
    {
        return $this->documentService;
    }

    public function listAthletes(int $organizationId, int $page = 1, int $limit = 15, ?string $search = null, ?int $sportId = null, ?string $status = null): array
    {
        return $this->repository->getPaginated($organizationId, $page, $limit, $search, $sportId, $status);
    }

    public function getAthlete(int $organizationId, int $id): ?array
    {
        return $this->repository->findById($organizationId, $id);
    }

    /**
     * Register an athlete with optional user login account and uploaded documents in a safe transaction
     */
    public function registerAthlete(int $organizationId, array $data, ?int $performedBy = null, ?array $files = null): array
    {
        // Enforce required fields
        if (empty(trim($data['first_name'] ?? ''))) {
            throw new \InvalidArgumentException('First name is required.');
        }
        if (empty(trim($data['last_name'] ?? ''))) {
            throw new \InvalidArgumentException('Last name is required.');
        }
        if (empty($data['date_of_birth'])) {
            throw new \InvalidArgumentException('Date of birth is required.');
        }
        if (empty($data['gender'])) {
            throw new \InvalidArgumentException('Gender is required.');
        }
        $sportId = (int)($data['current_sport_id'] ?? ($data['primary_sport_id'] ?? 0));
        if ($sportId <= 0) {
            throw new \InvalidArgumentException('Primary sport selection is required.');
        }
        if (empty($data['status']) || !in_array($data['status'], ['active', 'inactive', 'injured', 'suspended'], true)) {
            throw new \InvalidArgumentException('Valid athlete status is required.');
        }

        $data['organization_id'] = $organizationId;
        $createdUserId = null;
        $tempPassword = null;
        $createAccount = !empty($data['create_account']) && ($data['create_account'] == 1 || $data['create_account'] === true || $data['create_account'] === 'on');

        if ($this->pdo) {
            $this->pdo->beginTransaction();
        }

        try {
            // 1. If login account requested, validate and create user
            if ($createAccount) {
                $loginEmail = trim($data['login_email'] ?? ($data['email'] ?? ''));
                if (empty($loginEmail) || !filter_var($loginEmail, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException('A valid email address is required to create an athlete login account.');
                }

                // Check uniqueness in users table
                $checkStmt = $this->pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
                $checkStmt->execute([':email' => $loginEmail]);
                if ($checkStmt->fetchColumn()) {
                    throw new \InvalidArgumentException("A user account with email '{$loginEmail}' already exists in the system.");
                }

                // Generate secure password if not manually provided
                if (!empty($data['auth_method']) && $data['auth_method'] === 'manual' && !empty($data['password'])) {
                    $tempPassword = $data['password'];
                } else {
                    $tempPassword = 'Khel@' . date('Y') . '!' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
                }

                $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);
                $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                $username = !empty($data['username']) ? trim($data['username']) : strtolower(explode('@', $loginEmail)[0] . '_' . rand(100, 999));
                $userStatus = !empty($data['account_status']) && in_array($data['account_status'], ['active', 'pending', 'inactive'], true) ? $data['account_status'] : 'active';

                $uStmt = $this->pdo->prepare("
                    INSERT INTO users (uuid, username, email, password, first_name, last_name, phone, status, created_at, updated_at)
                    VALUES (:uuid, :username, :email, :password, :fname, :lname, :phone, :status, NOW(), NOW())
                ");
                $uStmt->execute([
                    ':uuid' => $uuid,
                    ':username' => $username,
                    ':email' => $loginEmail,
                    ':password' => $hashedPassword,
                    ':fname' => $data['first_name'] ?? '',
                    ':lname' => $data['last_name'] ?? '',
                    ':phone' => $data['phone'] ?? null,
                    ':status' => $userStatus,
                ]);
                $createdUserId = (int)$this->pdo->lastInsertId();

                // Assign to organization with Athlete Role (Role ID 5)
                $ouStmt = $this->pdo->prepare("
                    INSERT INTO organization_users (organization_id, user_id, role_id, athlete_id, access_status, assigned_by, assigned_at, created_at, updated_at)
                    VALUES (:org_id, :user_id, 5, NULL, :status, :by, NOW(), NOW(), NOW())
                ");
                $ouStmt->execute([
                    ':org_id' => $organizationId,
                    ':user_id' => $createdUserId,
                    ':status' => $userStatus === 'pending' ? 'pending' : 'active',
                    ':by' => $performedBy,
                ]);

                $data['user_id'] = $createdUserId;
            }

            // 2. Create Athlete record
            $athlete = $this->repository->create($data);
            $athleteId = (int)($athlete['id'] ?? 0);

            // 3. Link athlete_id back to organization_users if user was created
            if ($createdUserId && $athleteId) {
                $updOu = $this->pdo->prepare("
                    UPDATE organization_users
                    SET athlete_id = :ath_id
                    WHERE organization_id = :org_id AND user_id = :uid
                ");
                $updOu->execute([
                    ':ath_id' => $athleteId,
                    ':org_id' => $organizationId,
                    ':uid' => $createdUserId,
                ]);
            }

            // 4. Process document uploads if provided
            $uploadedDocsCount = 0;
            if (!empty($files) && $athleteId) {
                $uploadedDocsCount = $this->processUploadedDocuments($organizationId, $athleteId, $data, $files, $performedBy);
            }

            if ($this->pdo) {
                $this->pdo->commit();
            }

            // Audit log
            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'ATHLETE_CREATE',
                'Athletes',
                'athletes',
                $athleteId,
                null,
                [
                    'athlete_code' => $athlete['athlete_code'] ?? '',
                    'name' => ($athlete['first_name'] ?? '') . ' ' . ($athlete['last_name'] ?? ''),
                    'has_account' => (bool)$createdUserId,
                    'documents_count' => $uploadedDocsCount,
                ],
                "Registered athlete {$athlete['first_name']} {$athlete['last_name']}" . ($createdUserId ? " with user account ({$loginEmail})" : "")
            );

            // Attach user creation data for one-time display to administrator
            if ($createdUserId) {
                $athlete['created_user'] = true;
                $athlete['login_email'] = $loginEmail;
                $athlete['login_username'] = $username;
                $athlete['temp_password'] = $tempPassword;
                $athlete['account_status'] = $userStatus;
            } else {
                $athlete['created_user'] = false;
            }
            $athlete['uploaded_docs_count'] = $uploadedDocsCount;

            return $athlete;
        } catch (\Throwable $e) {
            if ($this->pdo && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Process normalized uploaded documents
     */
    protected function processUploadedDocuments(int $orgId, int $athleteId, array $postData, array $files, ?int $performedBy = null): int
    {
        $count = 0;

        // Check if documents are passed in standard array format
        // $_FILES['doc_file'] as array of files, or $_FILES['documents']
        if (isset($files['doc_files']) && is_array($files['doc_files']['name'])) {
            $names = $files['doc_files']['name'];
            $types = $files['doc_files']['type'];
            $tmpNames = $files['doc_files']['tmp_name'];
            $errors = $files['doc_files']['error'];
            $sizes = $files['doc_files']['size'];

            $docTypes = $postData['doc_type'] ?? [];
            $docNames = $postData['doc_name'] ?? [];
            $docNumbers = $postData['doc_number'] ?? [];
            $issueDates = $postData['doc_issue_date'] ?? [];
            $expiryDates = $postData['doc_expiry_date'] ?? [];
            $notesArr = $postData['doc_notes'] ?? [];

            foreach ($names as $idx => $origName) {
                if (empty($origName) || ($errors[$idx] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $fileItem = [
                    'name' => $origName,
                    'type' => $types[$idx] ?? '',
                    'tmp_name' => $tmpNames[$idx] ?? '',
                    'error' => $errors[$idx] ?? UPLOAD_ERR_OK,
                    'size' => $sizes[$idx] ?? 0,
                ];

                $docData = [
                    'document_type' => $docTypes[$idx] ?? 'other',
                    'document_name' => !empty($docNames[$idx]) ? $docNames[$idx] : $origName,
                    'document_number' => $docNumbers[$idx] ?? null,
                    'issue_date' => $issueDates[$idx] ?? null,
                    'expiry_date' => $expiryDates[$idx] ?? null,
                    'notes' => $notesArr[$idx] ?? null,
                ];

                $this->documentService->addDocument($orgId, $athleteId, $docData, $fileItem, $performedBy);
                $count++;
            }
        } elseif (isset($files['document_file']) && ($files['document_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            // Single document upload
            $this->documentService->addDocument($orgId, $athleteId, $postData, $files['document_file'], $performedBy);
            $count++;
        }

        return $count;
    }

    /**
     * Provision login account for an existing athlete
     */
    public function createAthleteAccount(int $organizationId, int $athleteId, array $data, ?int $performedBy = null): array
    {
        $athlete = $this->repository->findById($organizationId, $athleteId);
        if (!$athlete) {
            throw new \InvalidArgumentException('Athlete not found or does not belong to your organisation.');
        }

        if (!empty($athlete['user_id'])) {
            throw new \InvalidArgumentException('This athlete already has a linked user login account.');
        }

        $loginEmail = trim($data['login_email'] ?? ($athlete['email'] ?? ''));
        if (empty($loginEmail) || !filter_var($loginEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('A valid email address is required to create an athlete login account.');
        }

        $checkStmt = $this->pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $checkStmt->execute([':email' => $loginEmail]);
        if ($checkStmt->fetchColumn()) {
            throw new \InvalidArgumentException("A user account with email '{$loginEmail}' already exists in the system.");
        }

        if (!empty($data['password'])) {
            $tempPassword = $data['password'];
        } else {
            $tempPassword = 'Khel@' . date('Y') . '!' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        }

        $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $username = !empty($data['username']) ? trim($data['username']) : strtolower(explode('@', $loginEmail)[0] . '_' . rand(100, 999));

        $this->pdo->beginTransaction();
        try {
            $uStmt = $this->pdo->prepare("
                INSERT INTO users (uuid, username, email, password, first_name, last_name, phone, status, created_at, updated_at)
                VALUES (:uuid, :username, :email, :password, :fname, :lname, :phone, 'active', NOW(), NOW())
            ");
            $uStmt->execute([
                ':uuid' => $uuid,
                ':username' => $username,
                ':email' => $loginEmail,
                ':password' => $hashedPassword,
                ':fname' => $athlete['first_name'] ?? '',
                ':lname' => $athlete['last_name'] ?? '',
                ':phone' => $athlete['phone'] ?? null,
            ]);
            $newUserId = (int)$this->pdo->lastInsertId();

            $ouStmt = $this->pdo->prepare("
                INSERT INTO organization_users (organization_id, user_id, role_id, athlete_id, access_status, assigned_by, assigned_at, created_at, updated_at)
                VALUES (:org_id, :user_id, 5, :ath_id, 'active', :by, NOW(), NOW(), NOW())
            ");
            $ouStmt->execute([
                ':org_id' => $organizationId,
                ':user_id' => $newUserId,
                ':ath_id' => $athleteId,
                ':by' => $performedBy,
            ]);

            $this->pdo->prepare("UPDATE athletes SET user_id = :uid WHERE id = :aid AND organization_id = :oid")->execute([
                ':uid' => $newUserId,
                ':aid' => $athleteId,
                ':oid' => $organizationId,
            ]);

            $this->pdo->commit();

            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'USER_CREATE',
                'AthleteAccount',
                'users',
                $newUserId,
                null,
                ['email' => $loginEmail, 'athlete_id' => $athleteId],
                "Provisioned login account for athlete #{$athleteId} ({$loginEmail})"
            );

            return [
                'user_id' => $newUserId,
                'email' => $loginEmail,
                'username' => $username,
                'temp_password' => $tempPassword,
            ];
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Reset password for an athlete's user account
     */
    public function resetAthletePassword(int $organizationId, int $athleteId, ?string $newPassword = null, ?int $performedBy = null): string
    {
        $athlete = $this->repository->findById($organizationId, $athleteId);
        if (!$athlete || empty($athlete['user_id'])) {
            throw new \InvalidArgumentException('Athlete has no linked user account.');
        }

        $passwordToSet = $newPassword ?: ('Khel@' . date('Y') . '!' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6)));
        $hashed = password_hash($passwordToSet, PASSWORD_BCRYPT);

        $stmt = $this->pdo->prepare("UPDATE users SET password = :pwd, updated_at = NOW() WHERE id = :uid");
        $stmt->execute([':pwd' => $hashed, ':uid' => $athlete['user_id']]);

        $this->auditLog->log(
            $organizationId,
            $performedBy,
            'PASSWORD_RESET',
            'AthleteAccount',
            'users',
            $athlete['user_id'],
            null,
            ['athlete_id' => $athleteId],
            "Reset password for athlete #{$athleteId} user account"
        );

        return $passwordToSet;
    }

    public function updateAthlete(int $organizationId, int $id, array $data, ?int $performedBy = null): bool
    {
        $existing = $this->repository->findById($organizationId, $id);
        if (!$existing) return false;

        // Ensure resulting athlete record preserves all required fields
        $finalFirstName = isset($data['first_name']) ? trim($data['first_name']) : trim($existing['first_name'] ?? '');
        $finalLastName = isset($data['last_name']) ? trim($data['last_name']) : trim($existing['last_name'] ?? '');
        $finalDob = isset($data['date_of_birth']) ? trim($data['date_of_birth']) : trim($existing['date_of_birth'] ?? '');
        $finalGender = isset($data['gender']) ? trim($data['gender']) : trim($existing['gender'] ?? '');
        $finalSportId = isset($data['current_sport_id']) ? (int)$data['current_sport_id'] : (int)($existing['current_sport_id'] ?? 0);
        $finalStatus = isset($data['status']) ? trim($data['status']) : trim($existing['status'] ?? '');

        if (empty($finalFirstName)) {
            throw new \InvalidArgumentException('First name is required.');
        }
        if (empty($finalLastName)) {
            throw new \InvalidArgumentException('Last name is required.');
        }
        if (empty($finalDob)) {
            throw new \InvalidArgumentException('Date of birth is required.');
        }
        if (empty($finalGender)) {
            throw new \InvalidArgumentException('Gender is required.');
        }
        if ($finalSportId <= 0) {
            throw new \InvalidArgumentException('Primary sport selection is required.');
        }
        if (empty($finalStatus) || !in_array($finalStatus, ['active', 'inactive', 'injured', 'suspended'], true)) {
            throw new \InvalidArgumentException('Valid athlete status is required.');
        }

        $updated = $this->repository->update($organizationId, $id, $data);
        if ($updated) {
            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'ATHLETE_UPDATE',
                'Athletes',
                'athletes',
                $id,
                $existing,
                $data,
                "Updated athlete #{$id} ({$existing['first_name']} {$existing['last_name']})"
            );
        }
        return $updated;
    }

    public function deleteAthlete(int $organizationId, int $id, ?int $performedBy = null): bool
    {
        $existing = $this->repository->findById($organizationId, $id);
        if (!$existing) return false;

        $deleted = $this->repository->delete($organizationId, $id);
        if ($deleted) {
            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'ATHLETE_DELETE',
                'Athletes',
                'athletes',
                $id,
                $existing,
                null,
                "Soft deleted athlete #{$id}"
            );
        }
        return $deleted;
    }

    /**
     * Update athlete status between active and inactive non-destructively
     */
    public function updateStatus(int $organizationId, int $id, string $status, ?int $performedBy = null): bool
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new \InvalidArgumentException("Invalid athlete status: {$status}");
        }

        $existing = $this->repository->findById($organizationId, $id);
        if (!$existing || !empty($existing['deleted_at'])) {
            return false;
        }

        $updated = $this->repository->update($organizationId, $id, ['status' => $status]);
        if ($updated) {
            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'ATHLETE_UPDATE',
                'Athletes',
                'athletes',
                $id,
                ['status' => $existing['status']],
                ['status' => $status],
                "Changed athlete #{$id} ({$existing['first_name']} {$existing['last_name']}) status to {$status}"
            );
        }
        return $updated;
    }
}
