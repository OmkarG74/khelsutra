<?php

namespace App\Services\Organization;

use App\Services\BaseService;
use App\Services\Audit\AuditLogService;
use PDO;
use Exception;

class OrganizationManagementService extends BaseService
{
    protected AuditLogService $auditLog;

    public function __construct(?PDO $pdo = null, ?AuditLogService $auditLog = null)
    {
        parent::__construct($pdo);
        $this->auditLog = $auditLog ?? new AuditLogService($this->pdo);
    }

    public function listOrganizations(int $limit = 50, int $offset = 0, ?string $search = null, ?string $status = null, ?string $plan = null): array
    {
        if (!$this->pdo) return [];

        $sql = "
            SELECT o.*, 
                   (SELECT COUNT(*) FROM organization_users WHERE organization_id = o.id AND access_status = 'active') as active_users_count,
                   (SELECT COUNT(*) FROM employees WHERE organization_id = o.id AND employment_status = 'active' AND deleted_at IS NULL) as active_employees_count
            FROM organizations o 
            WHERE o.deleted_at IS NULL
        ";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (o.name LIKE :search OR o.organization_code LIKE :search OR o.legal_name LIKE :search OR o.email LIKE :search OR o.phone LIKE :search) ";
            $params[':search'] = '%' . trim($search) . '%';
        }

        if (!empty($status) && $status !== 'all') {
            $sql .= " AND o.status = :status ";
            $params[':status'] = trim($status);
        }

        if (!empty($plan) && $plan !== 'all') {
            $sql .= " AND (o.plan_name = :plan_exact OR o.plan_name LIKE :plan_like) ";
            $params[':plan_exact'] = trim($plan);
            $params[':plan_like'] = '%' . trim($plan) . '%';
        }

        $sql .= " ORDER BY o.id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countOrganizations(?string $search = null, ?string $status = null, ?string $plan = null): int
    {
        if (!$this->pdo) return 0;

        $sql = "SELECT COUNT(*) FROM organizations o WHERE o.deleted_at IS NULL";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (o.name LIKE :search OR o.organization_code LIKE :search OR o.legal_name LIKE :search OR o.email LIKE :search OR o.phone LIKE :search) ";
            $params[':search'] = '%' . trim($search) . '%';
        }

        if (!empty($status) && $status !== 'all') {
            $sql .= " AND o.status = :status ";
            $params[':status'] = trim($status);
        }

        if (!empty($plan) && $plan !== 'all') {
            $sql .= " AND (o.plan_name = :plan_exact OR o.plan_name LIKE :plan_like) ";
            $params[':plan_exact'] = trim($plan);
            $params[':plan_like'] = '%' . trim($plan) . '%';
        }

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getDistinctPlans(): array
    {
        if (!$this->pdo) return ['Enterprise', 'Standard Sports ERP', 'Enterprise Academy', 'High Performance Elite'];
        $stmt = $this->pdo->query("SELECT DISTINCT plan_name FROM organizations WHERE deleted_at IS NULL AND plan_name IS NOT NULL AND TRIM(plan_name) != '' ORDER BY plan_name ASC");
        $plans = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        $defaults = ['Enterprise', 'Standard Sports ERP', 'Enterprise Academy', 'High Performance Elite'];
        foreach ($defaults as $d) {
            if (!in_array($d, $plans, true)) {
                $plans[] = $d;
            }
        }
        return array_values(array_unique(array_filter($plans)));
    }

    public function getOrganization(int $id): ?array
    {
        if (!$this->pdo) return null;
        $stmt = $this->pdo->prepare("SELECT * FROM organizations WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([':id' => $id]);
        $org = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($org) {
            $org['admins'] = $this->getOrganizationAdmins($id);
            try {
                $settingsService = new OrganizationSettingsService($this->pdo, $this->auditLog);
                $org['settings'] = $settingsService->getAll($id);
            } catch (\Throwable $e) {
                $org['settings'] = [];
            }
        }
        return $org;
    }

    public function createOrganization(array $data, ?int $performedBy = null): ?array
    {
        if (!$this->pdo) return null;

        $orgCode = strtoupper(trim($data['organization_code'] ?? 'ORG-' . strtoupper(bin2hex(random_bytes(3)))));
        $name = trim($data['name'] ?? '');
        if (empty($name)) return null;

        $stmt = $this->pdo->prepare("
            INSERT INTO organizations (
                organization_code, name, legal_name, email, phone, website,
                address_line1, address_line2, city, state, country, postal_code,
                status, access_start_date, access_end_date, plan_name, notes,
                created_by, updated_by, created_at, updated_at
            ) VALUES (
                :code, :name, :legal_name, :email, :phone, :website,
                :addr1, :addr2, :city, :state, :country, :postal,
                :status, :start_date, :end_date, :plan, :notes,
                :created_by, :updated_by, NOW(), NOW()
            )
        ");

        $status = $data['status'] ?? 'active';
        $startDate = $data['access_start_date'] ?? date('Y-m-d');
        $endDate = $data['access_end_date'] ?? date('Y-m-d', strtotime('+1 year'));

        $stmt->execute([
            ':code' => $orgCode,
            ':name' => $name,
            ':legal_name' => $data['legal_name'] ?? null,
            ':email' => $data['email'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':website' => $data['website'] ?? null,
            ':addr1' => $data['address_line1'] ?? null,
            ':addr2' => $data['address_line2'] ?? null,
            ':city' => $data['city'] ?? null,
            ':state' => $data['state'] ?? null,
            ':country' => $data['country'] ?? 'India',
            ':postal' => $data['postal_code'] ?? null,
            ':status' => $status,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':plan' => $data['plan_name'] ?? 'Standard Sports ERP',
            ':notes' => $data['notes'] ?? null,
            ':created_by' => $performedBy,
            ':updated_by' => $performedBy,
        ]);

        $newOrgId = (int)$this->pdo->lastInsertId();

        // Log access record
        $this->logAccess($newOrgId, 'created', null, $status, $startDate, $endDate, $performedBy, 'Initial organization setup');

        // Audit log
        $this->auditLog->log($newOrgId, $performedBy, 'ORGANIZATION_CREATE', 'Organization', 'organizations', $newOrgId, null, $data, "Created organization {$name} ({$orgCode})");

        return $this->getOrganization($newOrgId);
    }

    public function updateOrganization(int $id, array $data, ?int $performedBy = null): ?array
    {
        if (!$this->pdo) return null;
        $existing = $this->getOrganization($id);
        if (!$existing) return null;

        // Resolve organization_code if provided
        $orgCode = isset($data['organization_code']) ? strtoupper(trim($data['organization_code'])) : ($existing['organization_code'] ?? '');
        if (!empty($orgCode) && $orgCode !== ($existing['organization_code'] ?? '')) {
            // Check uniqueness across other non-deleted organizations
            $checkStmt = $this->pdo->prepare("SELECT id FROM organizations WHERE organization_code = :code AND id != :id AND deleted_at IS NULL LIMIT 1");
            $checkStmt->execute([':code' => $orgCode, ':id' => $id]);
            if ($checkStmt->fetch()) {
                throw new \InvalidArgumentException("Organisation code '{$orgCode}' is already taken by another organisation.");
            }
        } else {
            $orgCode = $existing['organization_code'];
        }

        $stmt = $this->pdo->prepare("
            UPDATE organizations SET 
                organization_code = :code,
                name = :name,
                legal_name = :legal_name,
                email = :email,
                phone = :phone,
                website = :website,
                address_line1 = :addr1,
                address_line2 = :addr2,
                city = :city,
                state = :state,
                country = :country,
                postal_code = :postal,
                plan_name = :plan,
                access_start_date = :start_date,
                access_end_date = :end_date,
                notes = :notes,
                updated_by = :updated_by,
                updated_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
        ");

        $stmt->execute([
            ':id' => $id,
            ':code' => $orgCode,
            ':name' => $data['name'] ?? $existing['name'],
            ':legal_name' => $data['legal_name'] ?? $existing['legal_name'],
            ':email' => $data['email'] ?? $existing['email'],
            ':phone' => $data['phone'] ?? $existing['phone'],
            ':website' => $data['website'] ?? $existing['website'],
            ':addr1' => $data['address_line1'] ?? $existing['address_line1'],
            ':addr2' => $data['address_line2'] ?? $existing['address_line2'],
            ':city' => $data['city'] ?? $existing['city'],
            ':state' => $data['state'] ?? $existing['state'],
            ':country' => $data['country'] ?? ($existing['country'] ?? 'India'),
            ':postal' => $data['postal_code'] ?? $existing['postal_code'],
            ':plan' => $data['plan_name'] ?? $existing['plan_name'],
            ':start_date' => $data['access_start_date'] ?? $existing['access_start_date'],
            ':end_date' => $data['access_end_date'] ?? $existing['access_end_date'],
            ':notes' => $data['notes'] ?? $existing['notes'],
            ':updated_by' => $performedBy,
        ]);

        // If settings were provided in payload, persist them
        if (isset($data['settings']) && is_array($data['settings'])) {
            $settingsService = new OrganizationSettingsService($this->pdo, $this->auditLog);
            foreach ($data['settings'] as $k => $v) {
                $type = is_bool($v) ? 'boolean' : (is_int($v) || (is_numeric($v) && floor((float)$v) == (float)$v && !str_contains((string)$v, '.')) ? 'integer' : (is_numeric($v) ? 'decimal' : 'string'));
                $settingsService->set($id, $k, $v, $type, $performedBy);
            }
        }

        $updated = $this->getOrganization($id);
        $this->auditLog->log($id, $performedBy, 'ORGANIZATION_UPDATE', 'Organization', 'organizations', $id, $existing, $updated, "Updated organization details");

        return $updated;
    }

    public function updateStatus(int $id, string $newStatus, ?string $remarks = null, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;
        $allowed = ['pending', 'active', 'suspended', 'expired', 'inactive'];
        if (!in_array($newStatus, $allowed, true)) return false;

        $existing = $this->getOrganization($id);
        if (!$existing) return false;

        $stmt = $this->pdo->prepare("UPDATE organizations SET status = :status, updated_by = :up_by, updated_at = NOW() WHERE id = :id");
        $ok = $stmt->execute([':status' => $newStatus, ':up_by' => $performedBy, ':id' => $id]);

        if ($ok) {
            $action = match ($newStatus) {
                'active' => 'activated',
                'suspended' => 'suspended',
                'expired' => 'expired',
                default => 'deactivated'
            };

            $this->logAccess($id, $action, $existing['status'], $newStatus, $existing['access_start_date'], $existing['access_end_date'], $performedBy, $remarks);
            $this->auditLog->log($id, $performedBy, 'ORGANIZATION_STATUS_CHANGE', 'Organization', 'organizations', $id, ['status' => $existing['status']], ['status' => $newStatus], "Changed status from {$existing['status']} to {$newStatus}");
        }

        return $ok;
    }

    public function logAccess(int $orgId, string $action, ?string $prevStatus, ?string $newStatus, ?string $start, ?string $end, ?int $by, ?string $remarks): bool
    {
        if (!$this->pdo) return false;
        $stmt = $this->pdo->prepare("
            INSERT INTO organization_access_logs (organization_id, action, previous_status, new_status, access_start_date, access_end_date, performed_by, remarks, created_at)
            VALUES (:org_id, :action, :prev, :new, :start, :end, :by, :remarks, NOW())
        ");
        return $stmt->execute([
            ':org_id' => $orgId,
            ':action' => $action,
            ':prev' => $prevStatus,
            ':new' => $newStatus,
            ':start' => $start,
            ':end' => $end,
            ':by' => $by,
            ':remarks' => $remarks
        ]);
    }

    public function getAccessLogs(int $orgId): array
    {
        if (!$this->pdo) return [];
        $stmt = $this->pdo->prepare("SELECT * FROM organization_access_logs WHERE organization_id = :org_id ORDER BY created_at DESC");
        $stmt->execute([':org_id' => $orgId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createInitialSportsAdmin(int $orgId, array $adminData, ?int $superAdminId = null): ?array
    {
        if (!$this->pdo) return null;

        $email = trim($adminData['email'] ?? '');
        $firstName = trim($adminData['first_name'] ?? '');
        $lastName = trim($adminData['last_name'] ?? '');
        $password = $adminData['password'] ?? 'SecretPassword123';

        if (empty($email) || empty($firstName)) return null;

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        // Check if user exists
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $existingUserId = $stmt->fetchColumn();

        $userId = $existingUserId;
        if (!$userId) {
            $userStmt = $this->pdo->prepare("
                INSERT INTO users (uuid, email, password, first_name, last_name, phone, status, created_at, updated_at)
                VALUES (:uuid, :email, :password, :fname, :lname, :phone, 'active', NOW(), NOW())
            ");
            $userStmt->execute([
                ':uuid' => $uuid,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':fname' => $firstName,
                ':lname' => $lastName,
                ':phone' => $adminData['phone'] ?? null
            ]);
            $userId = (int)$this->pdo->lastInsertId();
        }

        // Bind in organization_users as Sports Administrator (Role ID 2)
        $ouStmt = $this->pdo->prepare("
            INSERT INTO organization_users (organization_id, user_id, role_id, access_status, assigned_by, assigned_at, created_at, updated_at)
            VALUES (:org_id, :user_id, 2, 'active', :assigned_by, NOW(), NOW(), NOW())
            ON DUPLICATE KEY UPDATE role_id = 2, access_status = 'active', updated_at = NOW()
        ");
        $ouStmt->execute([
            ':org_id' => $orgId,
            ':user_id' => $userId,
            ':assigned_by' => $superAdminId
        ]);

        $this->auditLog->log($orgId, $superAdminId, 'USER_CREATE', 'User', 'organization_users', $userId, null, ['role_id' => 2, 'email' => $email], "Created initial Sports Administrator for organization #{$orgId}");

        return [
            'user_id' => $userId,
            'email' => $email,
            'role' => 'Sports Administrator',
            'organization_id' => $orgId
        ];
    }

    public function getOrganizationAdmins(int $orgId): array
    {
        if (!$this->pdo) return [];
        $stmt = $this->pdo->prepare("
            SELECT u.id, u.uuid, u.username, u.email, u.first_name, u.last_name, u.phone, u.status,
                   ou.access_status, ou.role_id, r.name as role_name
            FROM organization_users ou
            JOIN users u ON ou.user_id = u.id
            JOIN roles r ON ou.role_id = r.id
            WHERE ou.organization_id = :org_id 
              AND ou.role_id = 2 
              AND u.deleted_at IS NULL
            ORDER BY u.id ASC
        ");
        $stmt->execute([':org_id' => $orgId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getOrganizationAdmin(int $orgId, int $adminId): ?array
    {
        if (!$this->pdo) return null;
        $stmt = $this->pdo->prepare("
            SELECT u.id, u.uuid, u.username, u.email, u.first_name, u.last_name, u.phone, u.status,
                   ou.access_status, ou.role_id, r.name as role_name
            FROM organization_users ou
            JOIN users u ON ou.user_id = u.id
            JOIN roles r ON ou.role_id = r.id
            WHERE ou.organization_id = :org_id 
              AND ou.user_id = :user_id
              AND ou.role_id = 2 
              AND u.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':org_id' => $orgId, ':user_id' => $adminId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function addOrganizationAdmin(int $orgId, array $data, ?int $performedBy = null): array
    {
        if (!$this->pdo) {
            throw new \RuntimeException("Database connection unavailable.");
        }

        $firstName = trim($data['first_name'] ?? '');
        $lastName = trim($data['last_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = !empty($data['phone']) ? trim($data['phone']) : null;
        $username = !empty($data['username']) ? trim($data['username']) : null;
        $password = !empty($data['password']) ? $data['password'] : 'SecretPassword123';
        $status = in_array(($data['status'] ?? 'active'), ['active', 'inactive'], true) ? $data['status'] : 'active';

        if (empty($firstName)) {
            throw new \InvalidArgumentException('First name is required.');
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('A valid email address is required.');
        }

        // Check if user already exists
        $userStmt = $this->pdo->prepare("SELECT id, username, first_name, last_name, status FROM users WHERE email = :email LIMIT 1");
        $userStmt->execute([':email' => $email]);
        $existingUser = $userStmt->fetch(PDO::FETCH_ASSOC);

        $userId = null;
        if ($existingUser) {
            $userId = (int)$existingUser['id'];

            // Check if already an admin in this organisation
            $checkOu = $this->pdo->prepare("SELECT id FROM organization_users WHERE organization_id = :org_id AND user_id = :user_id AND role_id = 2 LIMIT 1");
            $checkOu->execute([':org_id' => $orgId, ':user_id' => $userId]);
            if ($checkOu->fetchColumn()) {
                // Backend safety: already assigned as Organisation Admin, return existing cleanly without duplicate error
                return $this->getOrganizationAdmin($orgId, $userId) ?? [];
            }

            // Assign user to organization as Sports Administrator (Role ID 2)
            $ouStmt = $this->pdo->prepare("
                INSERT INTO organization_users (organization_id, user_id, role_id, access_status, assigned_by, assigned_at, created_at, updated_at)
                VALUES (:org_id, :user_id, 2, :status, :assigned_by, NOW(), NOW(), NOW())
                ON DUPLICATE KEY UPDATE role_id = 2, access_status = :status, updated_at = NOW()
            ");
            $ouStmt->execute([
                ':org_id' => $orgId,
                ':user_id' => $userId,
                ':status' => $status,
                ':assigned_by' => $performedBy
            ]);
        } else {
            // Check username uniqueness if provided
            if (!empty($username)) {
                $unCheck = $this->pdo->prepare("SELECT id FROM users WHERE username = :un LIMIT 1");
                $unCheck->execute([':un' => $username]);
                if ($unCheck->fetchColumn()) {
                    throw new \InvalidArgumentException("Username '{$username}' is already taken.");
                }
            } else {
                $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstName . ($lastName ? '.' . $lastName : '')));
                $username = $baseUsername ?: 'admin' . mt_rand(100, 999);
                $unCheck = $this->pdo->prepare("SELECT id FROM users WHERE username = :un LIMIT 1");
                $unCheck->execute([':un' => $username]);
                if ($unCheck->fetchColumn()) {
                    $username .= mt_rand(10, 9999);
                }
            }

            // Re-check email uniqueness right before insert for concurrency safety
            $recheckUser = $this->pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $recheckUser->execute([':email' => $email]);
            $foundUserId = $recheckUser->fetchColumn();

            if ($foundUserId) {
                $userId = (int)$foundUserId;
            } else {
                $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                $insertUser = $this->pdo->prepare("
                    INSERT INTO users (uuid, username, email, password, first_name, last_name, phone, status, created_at, updated_at)
                    VALUES (:uuid, :username, :email, :password, :fname, :lname, :phone, :status, NOW(), NOW())
                ");
                $insertUser->execute([
                    ':uuid' => $uuid,
                    ':username' => $username,
                    ':email' => $email,
                    ':password' => $hashedPassword,
                    ':fname' => $firstName,
                    ':lname' => $lastName,
                    ':phone' => $phone,
                    ':status' => $status
                ]);
                $userId = (int)$this->pdo->lastInsertId();
            }

            // Associate user to organization as Sports Administrator (Role ID 2)
            $ouStmt = $this->pdo->prepare("
                INSERT INTO organization_users (organization_id, user_id, role_id, access_status, assigned_by, assigned_at, created_at, updated_at)
                VALUES (:org_id, :user_id, 2, :status, :assigned_by, NOW(), NOW(), NOW())
                ON DUPLICATE KEY UPDATE role_id = 2, access_status = :status, updated_at = NOW()
            ");
            $ouStmt->execute([
                ':org_id' => $orgId,
                ':user_id' => $userId,
                ':status' => $status,
                ':assigned_by' => $performedBy
            ]);
        }

        $this->auditLog->log($orgId, $performedBy, 'USER_CREATE', 'User', 'organization_users', $userId, null, ['email' => $email, 'role_id' => 2], "Assigned Organisation Administrator {$firstName} {$lastName} ({$email}) to organisation #{$orgId}");

        return $this->getOrganizationAdmin($orgId, $userId) ?? [];
    }

    public function updateOrganizationAdmin(int $orgId, int $adminId, array $data, ?int $performedBy = null): ?array
    {
        if (!$this->pdo) return null;

        $existing = $this->getOrganizationAdmin($orgId, $adminId);
        if (!$existing) {
            throw new \InvalidArgumentException("Organisation Administrator not found in this organisation.");
        }

        $firstName = trim($data['first_name'] ?? $existing['first_name']);
        $lastName = trim($data['last_name'] ?? $existing['last_name']);
        $email = trim($data['email'] ?? $existing['email']);
        $phone = isset($data['phone']) ? trim($data['phone']) : $existing['phone'];
        $username = trim($data['username'] ?? $existing['username']);
        $status = in_array(($data['status'] ?? $existing['status']), ['active', 'inactive'], true) ? $data['status'] : $existing['status'];

        if (empty($firstName)) {
            throw new \InvalidArgumentException('First name is required.');
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('A valid email address is required.');
        }

        // Email uniqueness check (excluding current user)
        $emailCheck = $this->pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :uid LIMIT 1");
        $emailCheck->execute([':email' => $email, ':uid' => $adminId]);
        if ($emailCheck->fetchColumn()) {
            throw new \InvalidArgumentException("Email address '{$email}' is already in use by another user.");
        }

        // Username uniqueness check (excluding current user)
        if (!empty($username)) {
            $unCheck = $this->pdo->prepare("SELECT id FROM users WHERE username = :un AND id != :uid LIMIT 1");
            $unCheck->execute([':un' => $username, ':uid' => $adminId]);
            if ($unCheck->fetchColumn()) {
                throw new \InvalidArgumentException("Username '{$username}' is already taken by another user.");
            }
        }

        // Build user update query
        $sql = "UPDATE users SET first_name = :fname, last_name = :lname, email = :email, phone = :phone, username = :un, status = :status, updated_at = NOW()";
        $params = [
            ':fname' => $firstName,
            ':lname' => $lastName,
            ':email' => $email,
            ':phone' => $phone ?: null,
            ':un' => $username,
            ':status' => $status,
            ':id' => $adminId
        ];

        if (!empty($data['password'])) {
            $sql .= ", password = :pass";
            $params[':pass'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        $sql .= " WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        // Update organization_users access_status
        $ouStmt = $this->pdo->prepare("
            UPDATE organization_users SET access_status = :status, updated_at = NOW()
            WHERE organization_id = :org_id AND user_id = :user_id AND role_id = 2
        ");
        $ouStmt->execute([
            ':status' => $status,
            ':org_id' => $orgId,
            ':user_id' => $adminId
        ]);

        $updated = $this->getOrganizationAdmin($orgId, $adminId);
        $this->auditLog->log($orgId, $performedBy, 'USER_UPDATE', 'User', 'organization_users', $adminId, $existing, $updated, "Updated Organisation Administrator #{$adminId} ({$email})");

        return $updated;
    }

    public function updateOrganizationAdminStatus(int $orgId, int $adminId, string $status, ?int $performedBy = null): bool
    {
        if (!$this->pdo || !in_array($status, ['active', 'inactive'], true)) {
            return false;
        }

        $existing = $this->getOrganizationAdmin($orgId, $adminId);
        if (!$existing) {
            return false;
        }

        // Update status in users
        $stmt = $this->pdo->prepare("UPDATE users SET status = :status, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $adminId]);

        // Update access_status in organization_users
        $ouStmt = $this->pdo->prepare("UPDATE organization_users SET access_status = :status, updated_at = NOW() WHERE organization_id = :org_id AND user_id = :user_id AND role_id = 2");
        $ok = $ouStmt->execute([':status' => $status, ':org_id' => $orgId, ':user_id' => $adminId]);

        if ($ok) {
            $this->auditLog->log($orgId, $performedBy, 'USER_STATUS_CHANGE', 'User', 'organization_users', $adminId, ['status' => $existing['status']], ['status' => $status], "Changed Organisation Administrator #{$adminId} status to {$status}");
        }

        return $ok;
    }

    public function removeOrganizationAdmin(int $orgId, int $adminId, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;

        $existing = $this->getOrganizationAdmin($orgId, $adminId);
        if (!$existing) return false;

        // Ensure we don't leave the organisation without any admins if business rule requires at least one
        $stmtCount = $this->pdo->prepare("SELECT COUNT(*) FROM organization_users WHERE organization_id = :org_id AND role_id = 2");
        $stmtCount->execute([':org_id' => $orgId]);
        $totalAdmins = (int)$stmtCount->fetchColumn();
        if ($totalAdmins <= 1) {
            throw new \InvalidArgumentException("Cannot remove the only Organisation Administrator. An organisation must have at least one administrator.");
        }

        // Delete from organization_users only (preserves user account in users table)
        $del = $this->pdo->prepare("DELETE FROM organization_users WHERE organization_id = :org_id AND user_id = :user_id AND role_id = 2");
        $ok = $del->execute([':org_id' => $orgId, ':user_id' => $adminId]);

        if ($ok) {
            $this->auditLog->log($orgId, $performedBy, 'USER_REMOVE', 'User', 'organization_users', $adminId, $existing, null, "Removed user #{$adminId} from Organisation Administrators of organisation #{$orgId}");
        }

        return $ok;
    }

    public function createOrganizationWithAdmins(array $orgData, array $admins, ?int $performedBy = null): ?array
    {
        if (!$this->pdo) return null;

        $name = trim($orgData['name'] ?? '');
        if (empty($name)) {
            throw new \InvalidArgumentException('Organisation name is required.');
        }

        // Validate admins list
        if (empty($admins)) {
            throw new \InvalidArgumentException('At least one Organisation Administrator is required.');
        }

        $emailsSeen = [];
        foreach ($admins as $index => $admin) {
            $num = $index + 1;
            $fName = trim($admin['first_name'] ?? '');
            $email = trim($admin['email'] ?? '');
            if (empty($fName)) {
                throw new \InvalidArgumentException("Admin {$num}: First name is required.");
            }
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("Admin {$num}: A valid email address is required.");
            }
            $lowerEmail = strtolower($email);
            if (in_array($lowerEmail, $emailsSeen, true)) {
                throw new \InvalidArgumentException("Admin {$num}: Duplicate email address ({$email}) provided in the administrators list.");
            }
            $emailsSeen[] = $lowerEmail;
        }

        // Transaction Safety: Roll back everything if any part fails
        $this->pdo->beginTransaction();

        try {
            $orgCode = strtoupper(trim($orgData['organization_code'] ?? 'ORG-' . strtoupper(bin2hex(random_bytes(3)))));

            // Check if organization_code already exists
            $checkOrg = $this->pdo->prepare("SELECT id FROM organizations WHERE organization_code = :code LIMIT 1");
            $checkOrg->execute([':code' => $orgCode]);
            if ($checkOrg->fetchColumn()) {
                throw new \InvalidArgumentException("Organisation code '{$orgCode}' is already in use.");
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO organizations (
                    organization_code, name, legal_name, email, phone, website,
                    address_line1, address_line2, city, state, country, postal_code,
                    status, access_start_date, access_end_date, plan_name, notes,
                    created_by, updated_by, created_at, updated_at
                ) VALUES (
                    :code, :name, :legal_name, :email, :phone, :website,
                    :addr1, :addr2, :city, :state, :country, :postal,
                    :status, :start_date, :end_date, :plan, :notes,
                    :created_by, :updated_by, NOW(), NOW()
                )
            ");

            $status = $orgData['status'] ?? 'active';
            $startDate = !empty($orgData['access_start_date']) ? $orgData['access_start_date'] : date('Y-m-d');
            $endDate = !empty($orgData['access_end_date']) ? $orgData['access_end_date'] : date('Y-m-d', strtotime('+1 year'));

            $stmt->execute([
                ':code' => $orgCode,
                ':name' => $name,
                ':legal_name' => $orgData['legal_name'] ?? null,
                ':email' => $orgData['email'] ?? null,
                ':phone' => $orgData['phone'] ?? null,
                ':website' => $orgData['website'] ?? null,
                ':addr1' => $orgData['address_line1'] ?? null,
                ':addr2' => $orgData['address_line2'] ?? null,
                ':city' => $orgData['city'] ?? null,
                ':state' => $orgData['state'] ?? null,
                ':country' => $orgData['country'] ?? 'India',
                ':postal' => $orgData['postal_code'] ?? null,
                ':status' => $status,
                ':start_date' => $startDate,
                ':end_date' => $endDate,
                ':plan' => $orgData['plan_name'] ?? 'Standard Sports ERP',
                ':notes' => $orgData['notes'] ?? null,
                ':created_by' => $performedBy,
                ':updated_by' => $performedBy,
            ]);

            $newOrgId = (int)$this->pdo->lastInsertId();

            // Create each administrator
            foreach ($admins as $index => $admin) {
                $num = $index + 1;
                $aEmail = trim($admin['email']);
                $aFirstName = trim($admin['first_name']);
                $aLastName = trim($admin['last_name'] ?? '');
                $aPhone = !empty($admin['phone']) ? trim($admin['phone']) : null;
                $aUsername = !empty($admin['username']) ? trim($admin['username']) : null;
                $aPassword = !empty($admin['password']) ? $admin['password'] : 'SecretPassword123';
                $aStatus = in_array(($admin['status'] ?? 'active'), ['active', 'inactive'], true) ? $admin['status'] : 'active';

                // Check if user already exists
                $userCheck = $this->pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
                $userCheck->execute([':email' => $aEmail]);
                $existingUserId = $userCheck->fetchColumn();

                $userId = $existingUserId ? (int)$existingUserId : null;
                if (!$userId) {
                    $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    );
                    $hashedPassword = password_hash($aPassword, PASSWORD_BCRYPT);

                    if (!empty($aUsername)) {
                        $unCheck = $this->pdo->prepare("SELECT id FROM users WHERE username = :un LIMIT 1");
                        $unCheck->execute([':un' => $aUsername]);
                        if ($unCheck->fetchColumn()) {
                            throw new \InvalidArgumentException("Admin {$num}: Username '{$aUsername}' is already taken.");
                        }
                        $username = $aUsername;
                    } else {
                        $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $aFirstName . ($aLastName ? '.' . $aLastName : '')));
                        $username = $baseUsername ?: 'admin' . mt_rand(100, 999);

                        // Ensure unique username
                        $unCheck = $this->pdo->prepare("SELECT id FROM users WHERE username = :un LIMIT 1");
                        $unCheck->execute([':un' => $username]);
                        if ($unCheck->fetchColumn()) {
                            $username .= mt_rand(10, 9999);
                        }
                    }

                    $insertUser = $this->pdo->prepare("
                        INSERT INTO users (uuid, username, email, password, first_name, last_name, phone, status, created_at, updated_at)
                        VALUES (:uuid, :username, :email, :password, :fname, :lname, :phone, :status, NOW(), NOW())
                    ");
                    $insertUser->execute([
                        ':uuid' => $uuid,
                        ':username' => $username,
                        ':email' => $aEmail,
                        ':password' => $hashedPassword,
                        ':fname' => $aFirstName,
                        ':lname' => $aLastName,
                        ':phone' => $aPhone,
                        ':status' => $aStatus
                    ]);
                    $userId = (int)$this->pdo->lastInsertId();
                }

                // Associate user to organization as Sports Administrator (Role ID 2)
                $ouStmt = $this->pdo->prepare("
                    INSERT INTO organization_users (organization_id, user_id, role_id, access_status, assigned_by, assigned_at, created_at, updated_at)
                    VALUES (:org_id, :user_id, 2, :status, :assigned_by, NOW(), NOW(), NOW())
                    ON DUPLICATE KEY UPDATE role_id = 2, access_status = :status, updated_at = NOW()
                ");
                $ouStmt->execute([
                    ':org_id' => $newOrgId,
                    ':user_id' => $userId,
                    ':status' => $aStatus,
                    ':assigned_by' => $performedBy
                ]);
            }

            // Log access record
            $this->logAccess($newOrgId, 'created', null, $status, $startDate, $endDate, $performedBy, 'Organisation provisioned with ' . count($admins) . ' administrator(s)');

            // Audit log
            $this->auditLog->log($newOrgId, $performedBy, 'ORGANIZATION_CREATE', 'Organization', 'organizations', $newOrgId, null, $orgData, "Created organisation {$name} ({$orgCode}) with " . count($admins) . " administrator(s)");

            // Commit transaction
            $this->pdo->commit();

            return $this->getOrganization($newOrgId);

        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
