<?php

namespace App\Services\Coach;

use App\Services\BaseService;
use App\Services\Audit\AuditLogService;
use PDO;

class CoachService extends BaseService
{
    protected AuditLogService $auditLog;

    public function __construct(?PDO $pdo = null, ?AuditLogService $auditLog = null)
    {
        parent::__construct($pdo);
        $this->auditLog = $auditLog ?? new AuditLogService($this->pdo);
    }

    /**
     * Build shared WHERE conditions and bound parameters for both Coach list (paginated) and Coach export (unpaginated).
     */
    protected function buildFilterConditions(
        int $organizationId,
        ?string $search = null,
        ?string $specialization = null,
        ?string $status = null
    ): array {
        $conditions = [
            "cp.organization_id = :org_id",
            "e.organization_id = :emp_org_id",
            "cp.deleted_at IS NULL",
            "e.deleted_at IS NULL",
        ];
        $params = [
            ':org_id'     => $organizationId,
            ':emp_org_id' => $organizationId,
        ];

        $searchClean = trim((string)($search ?? ''));
        if ($searchClean !== '') {
            $conditions[] = "(
                e.first_name LIKE :s1
                OR e.last_name LIKE :s2
                OR CONCAT(e.first_name, ' ', e.last_name) LIKE :s3
                OR e.employee_code LIKE :s4
                OR cp.coach_code LIKE :s5
                OR cp.specialization LIKE :s6
                OR e.email LIKE :s7
                OR e.phone LIKE :s8
                OR e.designation LIKE :s9
            )";
            $like = "%{$searchClean}%";
            $params[':s1'] = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
            $params[':s4'] = $like;
            $params[':s5'] = $like;
            $params[':s6'] = $like;
            $params[':s7'] = $like;
            $params[':s8'] = $like;
            $params[':s9'] = $like;
        }

        $specClean = trim((string)($specialization ?? ''));
        if ($specClean !== '') {
            $conditions[] = "LOWER(TRIM(cp.specialization)) LIKE :spec";
            $params[':spec'] = '%' . strtolower($specClean) . '%';
        }

        $statusClean = strtolower(trim((string)($status ?? '')));
        if ($statusClean !== '') {
            $conditions[] = "LOWER(cp.status) = :status";
            $params[':status'] = $statusClean;
        }

        return [
            'where'  => implode(' AND ', $conditions),
            'params' => $params,
        ];
    }

    /**
     * Batch-hydrate active, non-deleted assigned teams for a list of coaches without multiplying coach rows.
     */
    protected function attachAssignedTeams(int $organizationId, array $coaches): array
    {
        if (empty($coaches) || !$this->pdo) {
            return $coaches;
        }

        $coachIds = [];
        foreach ($coaches as $c) {
            $cid = (int)($c['coach_profile_id'] ?? ($c['id'] ?? 0));
            if ($cid > 0) {
                $coachIds[] = $cid;
            }
        }
        $coachIds = array_values(array_unique($coachIds));
        if (empty($coachIds)) {
            return $coaches;
        }

        $placeholders = implode(',', array_fill(0, count($coachIds), '?'));
        $sql = "
            SELECT
                tc.coach_id,
                tc.team_id,
                tc.coach_role,
                tc.is_primary,
                tc.start_date,
                tc.end_date,
                t.name AS team_name,
                t.team_code,
                t.sport_id,
                s.name AS sport_name
            FROM team_coaches tc
            JOIN teams t ON tc.team_id = t.id
                AND t.deleted_at IS NULL
                AND t.status = 'active'
            LEFT JOIN sports s ON t.sport_id = s.id
            WHERE tc.organization_id = ?
              AND t.organization_id = ?
              AND (tc.end_date IS NULL OR tc.end_date >= CURDATE())
              AND tc.coach_id IN ({$placeholders})
            ORDER BY tc.is_primary DESC, tc.id DESC, t.name ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $bindValues = array_merge([$organizationId, $organizationId], $coachIds);
        $stmt->execute($bindValues);
        $teamRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $teamsByCoach = [];
        $seenTeamKeys = [];
        foreach ($teamRows as $tr) {
            $cid = (int)$tr['coach_id'];
            $tid = (int)$tr['team_id'];
            $normName = strtolower(trim((string)($tr['team_name'] ?? '')));
            if ($normName === '') {
                continue;
            }
            // Deduplicate by normalized team name per coach so duplicate team records or duplicate assignments don't repeat
            if (isset($seenTeamKeys[$cid][$normName]) || isset($seenTeamKeys[$cid]['id:' . $tid])) {
                continue;
            }
            $seenTeamKeys[$cid][$normName] = true;
            $seenTeamKeys[$cid]['id:' . $tid] = true;

            $teamsByCoach[$cid][] = [
                'id'         => $tid,
                'team_id'    => $tid,
                'name'       => $tr['team_name'],
                'team_name'  => $tr['team_name'],
                'team_code'  => $tr['team_code'] ?? null,
                'coach_role' => $tr['coach_role'] ?? 'head_coach',
                'is_primary' => (int)($tr['is_primary'] ?? 0),
                'sport_id'   => !empty($tr['sport_id']) ? (int)$tr['sport_id'] : null,
                'sport_name' => $tr['sport_name'] ?? null,
                'start_date' => $tr['start_date'] ?? null,
            ];
        }

        foreach ($coaches as &$coach) {
            $cid = (int)($coach['coach_profile_id'] ?? ($coach['id'] ?? 0));
            $activeTeams = $teamsByCoach[$cid] ?? [];
            $teamNames = array_column($activeTeams, 'team_name');

            $coach['id'] = $cid;
            $coach['coach_profile_id'] = $cid;
            $coach['teams'] = $activeTeams;
            $coach['team_count'] = count($activeTeams);
            $coach['primary_team_name'] = $teamNames[0] ?? null;
            $coach['extra_teams_count'] = max(0, count($teamNames) - 1);
            $coach['all_teams_label'] = !empty($teamNames) ? implode(', ', $teamNames) : null;
            $coach['assigned_teams'] = $coach['all_teams_label'];
        }
        unset($coach);

        return $coaches;
    }

    /**
     * Get distinct non-empty coach specializations for the current organization (excluding soft-deleted coaches).
     */
    public function getDistinctSpecializations(int $organizationId): array
    {
        if (!$this->pdo) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT cp.specialization
            FROM coach_profiles cp
            JOIN employees e ON cp.employee_id = e.id
            WHERE cp.organization_id = :org_id
              AND e.organization_id = :emp_org_id
              AND cp.deleted_at IS NULL
              AND e.deleted_at IS NULL
              AND cp.specialization IS NOT NULL
              AND TRIM(cp.specialization) != ''
            ORDER BY cp.specialization ASC
        ");
        $stmt->execute([
            ':org_id'     => $organizationId,
            ':emp_org_id' => $organizationId,
        ]);
        $raw = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $unique = [];
        $seenLower = [];
        foreach ($raw as $spec) {
            $trimmed = trim((string)$spec);
            $lower = strtolower($trimmed);
            if ($trimmed !== '' && !isset($seenLower[$lower])) {
                $seenLower[$lower] = true;
                $unique[] = $trimmed;
            }
        }
        natcasesort($unique);
        return array_values($unique);
    }

    public function listCoaches(
        int $organizationId,
        int $page = 1,
        int $limit = 20,
        ?string $search = null,
        ?string $specialization = null,
        ?string $status = null
    ): array {
        $limit = max(1, $limit);
        $page = max(1, $page);

        if (!$this->pdo) {
            return [
                'data'        => [],
                'total'       => 0,
                'page'        => 1,
                'limit'       => $limit,
                'total_pages' => 1,
                'from'        => 0,
                'to'          => 0,
            ];
        }

        $filter = $this->buildFilterConditions($organizationId, $search, $specialization, $status);
        $whereClause = $filter['where'];
        $params = $filter['params'];

        // Unique non-deleted coach count
        $countStmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT cp.id)
            FROM coach_profiles cp
            JOIN employees e ON cp.employee_id = e.id
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $totalPages = max(1, (int)ceil($total / $limit));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $limit;

        // One row per coach_profiles.id
        $sql = "
            SELECT
                cp.id AS id,
                cp.id AS coach_profile_id,
                cp.organization_id,
                cp.coach_code,
                cp.specialization,
                cp.qualification,
                cp.certifications,
                cp.license_number,
                cp.experience_years,
                cp.status AS status,
                cp.status AS coach_status,
                COALESCE(e.joining_date, cp.joining_date) AS joining_date,
                cp.joining_date AS coach_joining_date,
                cp.created_at,
                cp.updated_at,
                e.id AS employee_id,
                e.employee_code,
                e.first_name,
                e.middle_name,
                e.last_name,
                e.phone,
                e.email,
                e.designation,
                e.employment_type,
                e.employment_status,
                d.name AS department_name
            FROM coach_profiles cp
            JOIN employees e ON cp.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE {$whereClause}
            ORDER BY cp.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $coaches = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $coaches = $this->attachAssignedTeams($organizationId, $coaches);

        $from = $total > 0 ? ($offset + 1) : 0;
        $to = $total > 0 ? min($total, $offset + count($coaches)) : 0;

        return [
            'data'        => $coaches,
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'total_pages' => $totalPages,
            'from'        => $from,
            'to'          => $to,
        ];
    }

    /**
     * Fetch all non-deleted coaches matching the active filters without pagination (used by Excel export).
     */
    public function listAllFilteredCoaches(
        int $organizationId,
        ?string $search = null,
        ?string $specialization = null,
        ?string $status = null
    ): array {
        if (!$this->pdo) {
            return [];
        }

        $filter = $this->buildFilterConditions($organizationId, $search, $specialization, $status);
        $whereClause = $filter['where'];
        $params = $filter['params'];

        $sql = "
            SELECT
                cp.id AS id,
                cp.id AS coach_profile_id,
                cp.organization_id,
                cp.coach_code,
                cp.specialization,
                cp.qualification,
                cp.certifications,
                cp.license_number,
                cp.experience_years,
                cp.status AS status,
                cp.status AS coach_status,
                COALESCE(e.joining_date, cp.joining_date) AS joining_date,
                cp.joining_date AS coach_joining_date,
                cp.created_at,
                cp.updated_at,
                e.id AS employee_id,
                e.employee_code,
                e.first_name,
                e.middle_name,
                e.last_name,
                e.phone,
                e.email,
                e.designation,
                e.employment_type,
                e.employment_status,
                d.name AS department_name
            FROM coach_profiles cp
            JOIN employees e ON cp.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE {$whereClause}
            ORDER BY cp.id DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $coaches = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $this->attachAssignedTeams($organizationId, $coaches);
    }

    /**
     * Generate a filtered, unpaginated OpenXML (.xlsx) export for Coaches.
     */
    public function exportCoachesXlsx(
        int $organizationId,
        ?string $search = null,
        ?string $specialization = null,
        ?string $status = null
    ): array {
        $coaches = $this->listAllFilteredCoaches($organizationId, $search, $specialization, $status);
        $filename = \App\Services\Export\CoachXlsxExporter::buildFilename($specialization, $status);
        $binary = \App\Services\Export\CoachXlsxExporter::generateXlsxBinary($coaches);

        return [
            'filename' => $filename,
            'binary'   => $binary,
            'count'    => count($coaches),
            'rows'     => \App\Services\Export\CoachXlsxExporter::formatRows($coaches),
        ];
    }

    public function getCoach(int $organizationId, int $coachProfileId): ?array
    {
        if (!$this->pdo) return null;

        $stmt = $this->pdo->prepare("
            SELECT
                cp.*,
                cp.id AS coach_profile_id,
                cp.status AS coach_status,
                COALESCE(e.joining_date, cp.joining_date) AS joining_date,
                cp.created_at AS created_at,
                cp.updated_at AS updated_at,
                e.id AS employee_id,
                e.employee_code,
                e.first_name,
                e.middle_name,
                e.last_name,
                e.date_of_birth,
                e.gender,
                e.blood_group,
                e.phone,
                e.email,
                e.address_line1,
                e.city,
                e.state,
                e.postal_code,
                e.designation,
                e.employment_type,
                e.employment_status,
                e.emergency_contact_name,
                e.emergency_contact_phone,
                e.emergency_contact_relationship,
                e.department_id,
                d.name AS department_name
            FROM coach_profiles cp
            JOIN employees e ON cp.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE cp.id = :id
              AND cp.organization_id = :org_id
              AND e.organization_id = :emp_org_id
              AND cp.deleted_at IS NULL
              AND e.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([
            ':id'         => $coachProfileId,
            ':org_id'     => $organizationId,
            ':emp_org_id' => $organizationId,
        ]);
        $coach = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$coach) return null;

        // Hydrate deduplicated active assigned teams
        $hydrated = $this->attachAssignedTeams($organizationId, [$coach]);
        $coach = $hydrated[0];

        // Fetch recent training sessions
        $trainStmt = $this->pdo->prepare("
            SELECT ts.*, t.name AS team_name, v.name AS venue_name
            FROM training_sessions ts
            LEFT JOIN teams t ON ts.team_id = t.id
            LEFT JOIN venues v ON ts.venue_id = v.id
            WHERE ts.coach_id = :coach_id AND ts.organization_id = :org_id AND ts.deleted_at IS NULL
            ORDER BY ts.training_date DESC, ts.start_time DESC
            LIMIT 5
        ");
        $trainStmt->execute([':coach_id' => $coachProfileId, ':org_id' => $organizationId]);
        $coach['training_sessions'] = $trainStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Fetch employee documents
        $docStmt = $this->pdo->prepare("
            SELECT * FROM employee_documents
            WHERE employee_id = :emp_id AND organization_id = :org_id AND deleted_at IS NULL
            ORDER BY id DESC
        ");
        $docStmt->execute([':emp_id' => $coach['employee_id'], ':org_id' => $organizationId]);
        $coach['documents'] = $docStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $coach;
    }

    public function createCoach(int $organizationId, array $data, ?int $performedBy = null): array
    {
        if (empty(trim($data['first_name'] ?? ''))) {
            throw new \InvalidArgumentException('First name is required.');
        }
        if (empty(trim($data['last_name'] ?? ''))) {
            throw new \InvalidArgumentException('Last name is required.');
        }
        if (empty(trim($data['specialization'] ?? ''))) {
            throw new \InvalidArgumentException('Specialization is required.');
        }
        if (isset($data['date_of_birth']) && trim($data['date_of_birth']) === '') {
            throw new \InvalidArgumentException('Date of birth is required.');
        }
        if (isset($data['gender']) && trim($data['gender']) === '') {
            throw new \InvalidArgumentException('Gender is required.');
        }
        if (isset($data['designation']) && trim($data['designation']) === '') {
            throw new \InvalidArgumentException('Designation is required.');
        }
        if (isset($data['status']) && !in_array($data['status'], ['active', 'inactive'], true)) {
            throw new \InvalidArgumentException('Valid coach status is required.');
        }

        if (!$this->pdo) return [];

        // Validate optional team_id before inserting into employees/coach_profiles
        $validatedTeamId = null;
        if (!empty($data['team_id'])) {
            $validatedTeamId = (int)$data['team_id'];
            $teamCheck = $this->pdo->prepare("
                SELECT id FROM teams
                WHERE id = :team_id AND organization_id = :org_id AND deleted_at IS NULL
                LIMIT 1
            ");
            $teamCheck->execute([':team_id' => $validatedTeamId, ':org_id' => $organizationId]);
            if (!$teamCheck->fetchColumn()) {
                throw new \InvalidArgumentException('Unauthorized team assignment: team does not exist in this organization.');
            }
        }

        $this->pdo->beginTransaction();
        try {
            // 1. Create employee
            $empCode = $data['employee_code'] ?? ('EMP-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4)));
            $coachCode = $data['coach_code'] ?? ('CCH-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4)));

            $empSql = "
                INSERT INTO employees (
                    organization_id, employee_code, first_name, middle_name, last_name,
                    date_of_birth, gender, blood_group, phone, email, address_line1,
                    city, state, country, postal_code, department_id, designation,
                    joining_date, employment_type, employment_status, notes, created_at, updated_at
                ) VALUES (
                    :org_id, :emp_code, :first_name, :middle_name, :last_name,
                    :dob, :gender, :blood_group, :phone, :email, :addr,
                    :city, :state, 'India', :zip, :dept_id, :designation,
                    :joining_date, :employment_type, 'active', :notes, NOW(), NOW()
                )
            ";

            $empStmt = $this->pdo->prepare($empSql);
            $empStmt->execute([
                ':org_id' => $organizationId,
                ':emp_code' => $empCode,
                ':first_name' => trim($data['first_name'] ?? ''),
                ':middle_name' => trim($data['middle_name'] ?? ''),
                ':last_name' => trim($data['last_name'] ?? ''),
                ':dob' => !empty($data['date_of_birth']) ? $data['date_of_birth'] : '1985-01-01',
                ':gender' => $data['gender'] ?? 'not_specified',
                ':blood_group' => $data['blood_group'] ?? null,
                ':phone' => $data['phone'] ?? null,
                ':email' => $data['email'] ?? null,
                ':addr' => $data['address_line1'] ?? null,
                ':city' => $data['city'] ?? null,
                ':state' => $data['state'] ?? null,
                ':zip' => $data['postal_code'] ?? null,
                ':dept_id' => !empty($data['department_id']) ? (int)$data['department_id'] : null,
                ':designation' => $data['designation'] ?? 'Coach',
                ':joining_date' => !empty($data['joining_date']) ? $data['joining_date'] : date('Y-m-d'),
                ':employment_type' => $data['employment_type'] ?? 'full_time',
                ':notes' => $data['notes'] ?? null,
            ]);

            $employeeId = (int)$this->pdo->lastInsertId();

            // 2. Create coach_profiles
            $cchSql = "
                INSERT INTO coach_profiles (
                    organization_id, employee_id, coach_code, specialization,
                    qualification, certifications, experience_years, joining_date,
                    license_number, status, notes, created_at, updated_at
                ) VALUES (
                    :org_id, :emp_id, :code, :spec,
                    :qual, :cert, :exp, :joining,
                    :lic_num, :status, :notes, NOW(), NOW()
                )
            ";
            $cchStmt = $this->pdo->prepare($cchSql);
            $cchStmt->execute([
                ':org_id' => $organizationId,
                ':emp_id' => $employeeId,
                ':code' => $coachCode,
                ':spec' => $data['specialization'] ?? 'General Coaching',
                ':qual' => $data['qualification'] ?? null,
                ':cert' => $data['certifications'] ?? null,
                ':exp' => isset($data['experience_years']) ? (float)$data['experience_years'] : 0,
                ':joining' => !empty($data['joining_date']) ? $data['joining_date'] : date('Y-m-d'),
                ':lic_num' => $data['license_number'] ?? null,
                ':status' => $data['status'] ?? 'active',
                ':notes' => $data['notes'] ?? null,
            ]);

            $coachProfileId = (int)$this->pdo->lastInsertId();

            // 3. Optional Team Assignment
            if ($validatedTeamId !== null) {
                $tcSql = "
                    INSERT INTO team_coaches (organization_id, team_id, coach_id, coach_role, start_date, is_primary, created_at, updated_at)
                    VALUES (:org_id, :team_id, :coach_id, :role, CURDATE(), 1, NOW(), NOW())
                ";
                $tcStmt = $this->pdo->prepare($tcSql);
                $tcStmt->execute([
                    ':org_id' => $organizationId,
                    ':team_id' => $validatedTeamId,
                    ':coach_id' => $coachProfileId,
                    ':role' => $data['coach_role'] ?? 'head_coach'
                ]);
            }

            $this->pdo->commit();

            // 4. Audit Log
            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'COACH_CREATE',
                'Coaching Staff',
                'coach_profiles',
                $coachProfileId,
                null,
                ['coach_code' => $coachCode, 'name' => ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')],
                "Registered coach {$data['first_name']} {$data['last_name']} with code {$coachCode}"
            );

            return [
                'coach_profile_id' => $coachProfileId,
                'employee_id' => $employeeId,
                'coach_code' => $coachCode,
                'first_name' => $data['first_name'] ?? '',
                'last_name' => $data['last_name'] ?? ''
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateCoach(int $organizationId, int $coachProfileId, array $data, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;

        $existing = $this->getCoach($organizationId, $coachProfileId);
        if (!$existing) return false;

        // Ensure resulting coach record preserves all 7 required fields
        $finalFirstName = isset($data['first_name']) ? trim($data['first_name']) : trim($existing['first_name'] ?? '');
        $finalLastName = isset($data['last_name']) ? trim($data['last_name']) : trim($existing['last_name'] ?? '');
        $finalDob = isset($data['date_of_birth']) ? trim($data['date_of_birth']) : trim($existing['date_of_birth'] ?? '');
        $finalGender = isset($data['gender']) ? trim($data['gender']) : trim($existing['gender'] ?? '');
        $finalDesignation = isset($data['designation']) ? trim($data['designation']) : trim($existing['designation'] ?? '');
        $finalSpec = isset($data['specialization']) ? trim($data['specialization']) : trim($existing['specialization'] ?? '');
        $finalStatus = isset($data['status']) ? trim($data['status']) : trim($existing['coach_status'] ?? ($existing['status'] ?? ''));

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
        if (empty($finalDesignation)) {
            throw new \InvalidArgumentException('Designation is required.');
        }
        if (empty($finalSpec)) {
            throw new \InvalidArgumentException('Specialization is required.');
        }
        if (empty($finalStatus) || !in_array($finalStatus, ['active', 'inactive'], true)) {
            throw new \InvalidArgumentException('Valid coach status is required.');
        }

        // Validate optional team_id before updating employee/coach_profiles
        $validatedTeamId = null;
        if (!empty($data['team_id'])) {
            $validatedTeamId = (int)$data['team_id'];
            $teamCheck = $this->pdo->prepare("
                SELECT id FROM teams
                WHERE id = :team_id AND organization_id = :org_id AND deleted_at IS NULL
                LIMIT 1
            ");
            $teamCheck->execute([':team_id' => $validatedTeamId, ':org_id' => $organizationId]);
            if (!$teamCheck->fetchColumn()) {
                throw new \InvalidArgumentException('Unauthorized team assignment: team does not exist in this organization.');
            }
        }

        $this->pdo->beginTransaction();
        try {
            $employeeId = (int)$existing['employee_id'];

            // 1. Update employee
            $empSql = "
                UPDATE employees SET
                    first_name = :first_name,
                    middle_name = :middle_name,
                    last_name = :last_name,
                    date_of_birth = :dob,
                    gender = :gender,
                    blood_group = :blood_group,
                    phone = :phone,
                    email = :email,
                    address_line1 = :addr,
                    city = :city,
                    state = :state,
                    postal_code = :zip,
                    department_id = :dept_id,
                    designation = :designation,
                    joining_date = :joining_date,
                    employment_type = :employment_type,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :emp_id AND organization_id = :org_id
            ";
            $empStmt = $this->pdo->prepare($empSql);
            $empStmt->execute([
                ':first_name' => $data['first_name'] ?? $existing['first_name'],
                ':middle_name' => $data['middle_name'] ?? $existing['middle_name'],
                ':last_name' => $data['last_name'] ?? $existing['last_name'],
                ':dob' => $data['date_of_birth'] ?? $existing['date_of_birth'],
                ':gender' => $data['gender'] ?? $existing['gender'],
                ':blood_group' => $data['blood_group'] ?? $existing['blood_group'],
                ':phone' => $data['phone'] ?? $existing['phone'],
                ':email' => $data['email'] ?? $existing['email'],
                ':addr' => $data['address_line1'] ?? $existing['address_line1'],
                ':city' => $data['city'] ?? $existing['city'],
                ':state' => $data['state'] ?? $existing['state'],
                ':zip' => $data['postal_code'] ?? $existing['postal_code'],
                ':dept_id' => !empty($data['department_id']) ? (int)$data['department_id'] : ($existing['department_id'] ?? null),
                ':designation' => $data['designation'] ?? $existing['designation'],
                ':joining_date' => !empty($data['joining_date']) ? $data['joining_date'] : ($existing['joining_date'] ?? null),
                ':employment_type' => $data['employment_type'] ?? $existing['employment_type'],
                ':notes' => $data['notes'] ?? $existing['notes'],
                ':emp_id' => $employeeId,
                ':org_id' => $organizationId,
            ]);

            // 2. Update coach profile
            $cchSql = "
                UPDATE coach_profiles SET
                    specialization = :spec,
                    qualification = :qual,
                    certifications = :cert,
                    experience_years = :exp,
                    joining_date = :joining_date,
                    license_number = :lic_num,
                    status = :status,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :id AND organization_id = :org_id
            ";
            $cchStmt = $this->pdo->prepare($cchSql);
            $cchStmt->execute([
                ':spec' => $data['specialization'] ?? $existing['specialization'],
                ':qual' => $data['qualification'] ?? $existing['qualification'],
                ':cert' => $data['certifications'] ?? $existing['certifications'],
                ':exp' => isset($data['experience_years']) ? (float)$data['experience_years'] : $existing['experience_years'],
                ':joining_date' => !empty($data['joining_date']) ? $data['joining_date'] : ($existing['joining_date'] ?? null),
                ':lic_num' => $data['license_number'] ?? $existing['license_number'],
                ':status' => $data['status'] ?? $existing['coach_status'],
                ':notes' => $data['notes'] ?? $existing['notes'],
                ':id' => $coachProfileId,
                ':org_id' => $organizationId,
            ]);

            // 3. Update team assignment if provided
            if ($validatedTeamId !== null) {
                $checkStmt = $this->pdo->prepare("SELECT id FROM team_coaches WHERE coach_id = :cch_id AND team_id = :t_id AND organization_id = :org_id");
                $checkStmt->execute([':cch_id' => $coachProfileId, ':t_id' => $validatedTeamId, ':org_id' => $organizationId]);
                if (!$checkStmt->fetch()) {
                    $tcStmt = $this->pdo->prepare("
                        INSERT INTO team_coaches (organization_id, team_id, coach_id, coach_role, start_date, is_primary, created_at, updated_at)
                        VALUES (:org_id, :team_id, :coach_id, :role, CURDATE(), 1, NOW(), NOW())
                    ");
                    $tcStmt->execute([
                        ':org_id' => $organizationId,
                        ':team_id' => $validatedTeamId,
                        ':coach_id' => $coachProfileId,
                        ':role' => $data['coach_role'] ?? 'head_coach'
                    ]);
                }
            }

            $this->pdo->commit();

            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'COACH_UPDATE',
                'Coaching Staff',
                'coach_profiles',
                $coachProfileId,
                $existing,
                $data,
                "Updated coach #{$coachProfileId} ({$existing['first_name']} {$existing['last_name']})"
            );

            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Update coach status between active and inactive non-destructively
     */
    public function updateStatus(int $organizationId, int $coachProfileId, string $status, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new \InvalidArgumentException("Invalid coach status: {$status}");
        }

        $existing = $this->getCoach($organizationId, $coachProfileId);
        if (!$existing) return false;

        $stmt = $this->pdo->prepare("
            UPDATE coach_profiles 
            SET status = :status, updated_at = NOW() 
            WHERE id = :id AND organization_id = :org_id AND deleted_at IS NULL
        ");
        $stmt->execute([
            ':status' => $status,
            ':id' => $coachProfileId,
            ':org_id' => $organizationId,
        ]);

        $this->auditLog->log(
            $organizationId,
            $performedBy,
            'COACH_STATUS_UPDATE',
            'Coaching Staff',
            'coach_profiles',
            $coachProfileId,
            ['status' => $existing['coach_status']],
            ['status' => $status],
            "Changed coach #{$coachProfileId} ({$existing['first_name']} {$existing['last_name']}) status to {$status}"
        );

        return true;
    }

    public function deleteCoach(int $organizationId, int $coachProfileId, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;

        $existing = $this->getCoach($organizationId, $coachProfileId);
        if (!$existing) return false;

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("UPDATE coach_profiles SET deleted_at = NOW() WHERE id = :id AND organization_id = :org_id");
            $stmt->execute([':id' => $coachProfileId, ':org_id' => $organizationId]);

            $stmtEmp = $this->pdo->prepare("UPDATE employees SET deleted_at = NOW() WHERE id = :id AND organization_id = :org_id");
            $stmtEmp->execute([':id' => $existing['employee_id'], ':org_id' => $organizationId]);

            $this->pdo->commit();

            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'COACH_DELETE',
                'Coaching Staff',
                'coach_profiles',
                $coachProfileId,
                $existing,
                null,
                "Soft deleted coach #{$coachProfileId}"
            );

            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
