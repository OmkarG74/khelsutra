<?php

namespace App\Services\Training;

use App\Services\BaseService;
use App\Services\Audit\AuditLogService;
use PDO;

class TrainingService extends BaseService
{
    protected AuditLogService $auditLog;

    public function __construct(?PDO $pdo = null, ?AuditLogService $auditLog = null)
    {
        parent::__construct($pdo);
        $this->auditLog = $auditLog ?? new AuditLogService($this->pdo);
    }

    public function listSessions(int $organizationId, int $page = 1, int $limit = 20, ?string $search = null, ?string $date = null, ?string $status = null, ?int $teamId = null, ?int $coachId = null, ?int $athleteId = null): array
    {
        if (!$this->pdo) {
            return ['data' => [], 'total' => 0, 'page' => 1, 'limit' => $limit, 'total_pages' => 0, 'from' => 0, 'to' => 0];
        }

        $page = max(1, $page);
        $limit = max(1, $limit);

        $conditions = ["ts.organization_id = :org_id", "ts.deleted_at IS NULL"];
        $params = [':org_id' => $organizationId];

        if (!empty($search)) {
            $conditions[] = "(ts.title LIKE :search OR ts.training_reference LIKE :search OR ts.training_type LIKE :search OR t.name LIKE :search OR v.name LIKE :search OR CONCAT(e.first_name, ' ', e.last_name) LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if (!empty($date)) {
            $dateLower = strtolower(trim($date));
            if ($dateLower === 'today') {
                $conditions[] = "ts.training_date = CURDATE()";
            } elseif ($dateLower === 'upcoming') {
                $conditions[] = "(ts.training_date > CURDATE() OR (ts.training_date = CURDATE() AND ts.status != 'completed'))";
            } elseif ($dateLower === 'completed') {
                $conditions[] = "(ts.status = 'completed' OR ts.training_date < CURDATE())";
            } else {
                $conditions[] = "ts.training_date = :date";
                $params[':date'] = $date;
            }
        }

        if (!empty($status)) {
            $conditions[] = "ts.status = :status";
            $params[':status'] = $status;
        }

        if (!empty($teamId)) {
            $conditions[] = "ts.team_id = :team_id";
            $params[':team_id'] = $teamId;
        }

        if (!empty($coachId)) {
            $conditions[] = "(ts.coach_id = :coach_id OR ts.team_id IN (SELECT tc.team_id FROM team_coaches tc WHERE tc.coach_id = :coach_id AND tc.organization_id = :org_id))";
            $params[':coach_id'] = $coachId;
        }

        if (!empty($athleteId)) {
            $conditions[] = "ts.team_id IN (SELECT tm_f.team_id FROM team_members tm_f WHERE tm_f.athlete_id = :athlete_id AND tm_f.organization_id = :org_id AND tm_f.is_current = 1)";
            $params[':athlete_id'] = $athleteId;
        }

        $whereClause = implode(' AND ', $conditions);

        $countStmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT ts.id)
            FROM training_sessions ts
            LEFT JOIN teams t ON ts.team_id = t.id AND t.organization_id = ts.organization_id AND t.deleted_at IS NULL
            LEFT JOIN venues v ON ts.venue_id = v.id AND v.organization_id = ts.organization_id AND v.deleted_at IS NULL
            LEFT JOIN coach_profiles cp ON ts.coach_id = cp.id AND cp.organization_id = ts.organization_id AND cp.deleted_at IS NULL
            LEFT JOIN employees e ON cp.employee_id = e.id AND e.deleted_at IS NULL
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $totalPages = $total > 0 ? (int)ceil($total / $limit) : 1;
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT 
                ts.*,
                t.name as team_name,
                s.name as sport_name,
                v.name as venue_name,
                vf.name as facility_name,
                CONCAT(e.first_name, ' ', e.last_name) as coach_name,
                cp.coach_code,
                cp.specialization as coach_specialization,
                e.designation as coach_designation,
                (SELECT COUNT(*) FROM team_members tm WHERE tm.team_id = ts.team_id AND tm.organization_id = ts.organization_id AND tm.is_current = 1) as squad_size,
                (SELECT COUNT(*) FROM training_attendance ta WHERE ta.training_session_id = ts.id AND ta.organization_id = ts.organization_id) as marked_count,
                (SELECT COUNT(*) FROM training_attendance ta WHERE ta.training_session_id = ts.id AND ta.organization_id = ts.organization_id AND ta.attendance_status = 'present') as present_count,
                (SELECT COUNT(*) FROM training_attendance ta WHERE ta.training_session_id = ts.id AND ta.organization_id = ts.organization_id AND ta.attendance_status = 'absent') as absent_count,
                (SELECT COUNT(*) FROM training_attendance ta WHERE ta.training_session_id = ts.id AND ta.organization_id = ts.organization_id AND ta.attendance_status = 'late') as late_count,
                (SELECT COUNT(*) FROM training_attendance ta WHERE ta.training_session_id = ts.id AND ta.organization_id = ts.organization_id AND ta.attendance_status = 'excused') as excused_count
            FROM training_sessions ts
            LEFT JOIN teams t ON ts.team_id = t.id AND t.organization_id = ts.organization_id AND t.deleted_at IS NULL
            LEFT JOIN sports s ON t.sport_id = s.id
            LEFT JOIN venues v ON ts.venue_id = v.id AND v.organization_id = ts.organization_id AND v.deleted_at IS NULL
            LEFT JOIN venue_facilities vf ON ts.facility_id = vf.id AND vf.organization_id = ts.organization_id AND vf.deleted_at IS NULL
            LEFT JOIN coach_profiles cp ON ts.coach_id = cp.id AND cp.organization_id = ts.organization_id AND cp.deleted_at IS NULL
            LEFT JOIN employees e ON cp.employee_id = e.id AND e.deleted_at IS NULL
            WHERE {$whereClause}
            GROUP BY ts.id
            ORDER BY ts.training_date DESC, ts.start_time DESC, ts.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($sessions as &$s) {
            $s['id'] = (int)$s['id'];
            if (isset($s['team_id'])) $s['team_id'] = $s['team_id'] !== null ? (int)$s['team_id'] : null;
            if (isset($s['venue_id'])) $s['venue_id'] = $s['venue_id'] !== null ? (int)$s['venue_id'] : null;
            if (isset($s['facility_id'])) $s['facility_id'] = $s['facility_id'] !== null ? (int)$s['facility_id'] : null;
            if (isset($s['coach_id'])) $s['coach_id'] = $s['coach_id'] !== null ? (int)$s['coach_id'] : null;
            $s['squad_size'] = (int)($s['squad_size'] ?? 0);
            $s['marked_count'] = (int)($s['marked_count'] ?? 0);
            $s['total_roster_count'] = $s['squad_size'];
            $s['present_count'] = (int)($s['present_count'] ?? 0);
            $s['absent_count'] = (int)($s['absent_count'] ?? 0);
            $s['late_count'] = (int)($s['late_count'] ?? 0);
            $s['excused_count'] = (int)($s['excused_count'] ?? 0);
        }
        unset($s);

        $count = count($sessions);
        $from = $total > 0 && $count > 0 ? $offset + 1 : 0;
        $to = $total > 0 && $count > 0 ? $offset + $count : 0;

        return [
            'data' => $sessions,
            'total' => (int)$total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => (int)ceil($total / max(1, $limit)),
            'from' => $from,
            'to' => $to,
        ];
    }

    public function getTrainingKPIs(int $organizationId, ?string $search = null, ?string $date = null, ?string $status = null, ?int $teamId = null, ?int $coachId = null): array
    {
        if (!$this->pdo) {
            return [
                'total_sessions' => 0,
                'total_attendance' => 0,
                'present_count' => 0,
                'absent_count' => 0,
                'late_count' => 0,
                'excused_count' => 0,
            ];
        }

        $conditions = ["ts.organization_id = :org_id", "ts.deleted_at IS NULL"];
        $params = [':org_id' => $organizationId];

        if (!empty($search)) {
            $conditions[] = "(ts.title LIKE :search OR ts.training_reference LIKE :search OR ts.training_type LIKE :search OR t.name LIKE :search OR v.name LIKE :search OR CONCAT(e.first_name, ' ', e.last_name) LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if (!empty($date)) {
            $dateLower = strtolower(trim($date));
            if ($dateLower === 'today') {
                $conditions[] = "ts.training_date = CURDATE()";
            } elseif ($dateLower === 'upcoming') {
                $conditions[] = "(ts.training_date > CURDATE() OR (ts.training_date = CURDATE() AND ts.status != 'completed'))";
            } elseif ($dateLower === 'completed') {
                $conditions[] = "(ts.status = 'completed' OR ts.training_date < CURDATE())";
            } else {
                $conditions[] = "ts.training_date = :date";
                $params[':date'] = $date;
            }
        }

        if (!empty($status)) {
            $conditions[] = "ts.status = :status";
            $params[':status'] = $status;
        }

        if (!empty($teamId)) {
            $conditions[] = "ts.team_id = :team_id";
            $params[':team_id'] = $teamId;
        }

        if (!empty($coachId)) {
            $conditions[] = "(ts.coach_id = :coach_id OR ts.team_id IN (SELECT tc.team_id FROM team_coaches tc WHERE tc.coach_id = :coach_id AND tc.organization_id = :org_id))";
            $params[':coach_id'] = $coachId;
        }

        $whereClause = implode(' AND ', $conditions);

        $sql = "
            SELECT 
                COUNT(DISTINCT ts.id) as total_sessions,
                COUNT(ta.id) as total_attendance,
                COUNT(CASE WHEN ta.attendance_status = 'present' THEN 1 END) as present_count,
                COUNT(CASE WHEN ta.attendance_status = 'absent' THEN 1 END) as absent_count,
                COUNT(CASE WHEN ta.attendance_status = 'late' THEN 1 END) as late_count,
                COUNT(CASE WHEN ta.attendance_status = 'excused' THEN 1 END) as excused_count
            FROM training_sessions ts
            LEFT JOIN teams t ON ts.team_id = t.id AND t.organization_id = ts.organization_id AND t.deleted_at IS NULL
            LEFT JOIN venues v ON ts.venue_id = v.id AND v.organization_id = ts.organization_id AND v.deleted_at IS NULL
            LEFT JOIN coach_profiles cp ON ts.coach_id = cp.id AND cp.organization_id = ts.organization_id AND cp.deleted_at IS NULL
            LEFT JOIN employees e ON cp.employee_id = e.id AND e.deleted_at IS NULL
            LEFT JOIN training_attendance ta ON ts.id = ta.training_session_id AND ta.organization_id = ts.organization_id
            WHERE {$whereClause}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_sessions' => (int)($row['total_sessions'] ?? 0),
            'total_attendance' => (int)($row['total_attendance'] ?? 0),
            'present_count' => (int)($row['present_count'] ?? 0),
            'absent_count' => (int)($row['absent_count'] ?? 0),
            'late_count' => (int)($row['late_count'] ?? 0),
            'excused_count' => (int)($row['excused_count'] ?? 0),
        ];
    }

    public function getSession(int $organizationId, int $id): ?array
    {
        if (!$this->pdo) return null;

        $stmt = $this->pdo->prepare("
            SELECT 
                ts.*,
                t.name as team_name,
                t.sport_id,
                s.name as sport_name,
                v.name as venue_name,
                vf.name as facility_name,
                CONCAT(e.first_name, ' ', e.last_name) as coach_name,
                cp.coach_code,
                cp.specialization as coach_specialization,
                e.phone as coach_phone
            FROM training_sessions ts
            LEFT JOIN teams t ON ts.team_id = t.id AND t.organization_id = ts.organization_id AND t.deleted_at IS NULL
            LEFT JOIN sports s ON t.sport_id = s.id
            LEFT JOIN venues v ON ts.venue_id = v.id AND v.organization_id = ts.organization_id AND v.deleted_at IS NULL
            LEFT JOIN venue_facilities vf ON ts.facility_id = vf.id AND vf.organization_id = ts.organization_id AND vf.deleted_at IS NULL
            LEFT JOIN coach_profiles cp ON ts.coach_id = cp.id AND cp.organization_id = ts.organization_id AND cp.deleted_at IS NULL
            LEFT JOIN employees e ON cp.employee_id = e.id AND e.deleted_at IS NULL
            WHERE ts.id = :id AND ts.organization_id = :org_id AND ts.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':org_id' => $organizationId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) return null;

        // Fetch roster athletes with attendance status (default 'not_marked' for unrecorded athletes)
        $athStmt = $this->pdo->prepare("
            SELECT 
                a.id as athlete_id,
                a.athlete_code,
                a.first_name,
                a.last_name,
                a.status as athlete_status,
                MAX(tm.position) as preferred_position,
                MAX(tm.jersey_number) as jersey_number,
                MAX(tm.member_role) as member_role,
                MAX(ta.id) as attendance_id,
                COALESCE(MAX(ta.attendance_status), 'not_marked') as attendance_status,
                MAX(ta.check_in_time) as check_in_time,
                MAX(ta.check_out_time) as check_out_time,
                MAX(ta.remarks) as remarks
            FROM team_members tm
            JOIN athletes a ON tm.athlete_id = a.id AND a.organization_id = tm.organization_id AND a.deleted_at IS NULL
            LEFT JOIN training_attendance ta ON ta.training_session_id = :session_id AND ta.athlete_id = a.id AND ta.organization_id = tm.organization_id
            WHERE tm.team_id = :team_id AND tm.organization_id = :org_id AND tm.is_current = 1
            GROUP BY a.id, a.athlete_code, a.first_name, a.last_name, a.status
            ORDER BY jersey_number ASC, a.first_name ASC
        ");
        $athStmt->execute([
            ':session_id' => $id,
            ':team_id' => $session['team_id'],
            ':org_id' => $organizationId
        ]);
        $session['id'] = (int)$session['id'];
        if (isset($session['team_id'])) $session['team_id'] = $session['team_id'] !== null ? (int)$session['team_id'] : null;
        if (isset($session['venue_id'])) $session['venue_id'] = $session['venue_id'] !== null ? (int)$session['venue_id'] : null;
        if (isset($session['facility_id'])) $session['facility_id'] = $session['facility_id'] !== null ? (int)$session['facility_id'] : null;
        if (isset($session['coach_id'])) $session['coach_id'] = $session['coach_id'] !== null ? (int)$session['coach_id'] : null;

        $roster = $athStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $existingAthIds = array_column($roster, 'athlete_id');

        // Also check if any other athletes have attendance recorded for this session (e.g. guest attendees)
        $extraAttStmt = $this->pdo->prepare("
            SELECT 
                a.id as athlete_id,
                a.athlete_code,
                a.first_name,
                a.last_name,
                a.status as athlete_status,
                'Guest / Attendee' as preferred_position,
                NULL as jersey_number,
                'guest' as member_role,
                ta.id as attendance_id,
                ta.attendance_status,
                ta.check_in_time,
                ta.check_out_time,
                ta.remarks
            FROM training_attendance ta
            JOIN athletes a ON ta.athlete_id = a.id AND a.organization_id = ta.organization_id AND a.deleted_at IS NULL
            WHERE ta.training_session_id = :session_id AND ta.organization_id = :org_id
        ");
        $extraAttStmt->execute([':session_id' => $id, ':org_id' => $organizationId]);
        $extraAttendees = $extraAttStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($extraAttendees as $ea) {
            if (!in_array((int)$ea['athlete_id'], $existingAthIds, true)) {
                $roster[] = $ea;
            }
        }

        $summary = [
            'total' => count($roster),
            'marked' => 0,
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'excused' => 0,
            'not_marked' => 0,
        ];
        foreach ($roster as &$r) {
            $r['athlete_id'] = (int)$r['athlete_id'];
            if (isset($r['attendance_id']) && $r['attendance_id'] !== null) {
                $r['attendance_id'] = (int)$r['attendance_id'];
            }
            $st = $r['attendance_status'] ?? 'not_marked';
            if (isset($summary[$st])) {
                $summary[$st]++;
            }
            if ($st !== 'not_marked') {
                $summary['marked']++;
            }
        }
        unset($r);
        $session['roster_attendance'] = $roster;
        $session['attendance_summary'] = $summary;

        return $session;
    }

    public function getSessionAthletes(int $organizationId, int $sessionId, int $page = 1, int $limit = 10, ?string $search = null, ?string $status = null): array
    {
        $session = $this->getSession($organizationId, $sessionId);
        if (!$session) {
            return [
                'data' => [],
                'total' => 0,
                'page' => 1,
                'limit' => $limit,
                'total_pages' => 0,
                'from' => 0,
                'to' => 0,
                'summary' => [
                    'total' => 0, 'marked' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'not_marked' => 0
                ],
                'all_roster' => []
            ];
        }

        $allRoster = $session['roster_attendance'] ?? [];
        $summary = $session['attendance_summary'] ?? [];

        // Filter by search (Athlete Name or Registration ID / athlete_code)
        if (!empty($search)) {
            $searchLower = strtolower(trim($search));
            $allRoster = array_filter($allRoster, function ($r) use ($searchLower) {
                $name = strtolower(trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')));
                $code = strtolower(trim($r['athlete_code'] ?? ''));
                return str_contains($name, $searchLower) || str_contains($code, $searchLower);
            });
        }

        // Filter by attendance status ('present', 'absent', 'late', 'excused', 'not_marked')
        if (!empty($status)) {
            $statusLower = strtolower(trim($status));
            $allRoster = array_filter($allRoster, function ($r) use ($statusLower) {
                return strtolower(trim($r['attendance_status'] ?? 'not_marked')) === $statusLower;
            });
        }

        // Re-index array values
        $allRoster = array_values($allRoster);
        $total = count($allRoster);
        $limit = max(1, $limit);
        $totalPages = $total > 0 ? (int)ceil($total / $limit) : 1;
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $limit;

        $pagedData = array_slice($allRoster, $offset, $limit);
        $from = $total > 0 ? $offset + 1 : 0;
        $to = $total > 0 ? min($total, $offset + count($pagedData)) : 0;

        return [
            'data' => $pagedData,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $totalPages,
            'from' => $from,
            'to' => $to,
            'summary' => $summary,
            'all_roster' => $session['roster_attendance'] ?? [],
        ];
    }

    public function createSession(int $organizationId, array $data, ?int $performedBy = null): array
    {
        if (!$this->pdo) return [];

        $this->pdo->beginTransaction();
        try {
            $ref = $data['training_reference'] ?? ('TRN-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4)));

            $teamId = !empty($data['team_id']) ? (int)$data['team_id'] : null;
            if ($teamId) {
                $chk = $this->pdo->prepare("SELECT id FROM teams WHERE id = :id AND organization_id = :org_id AND deleted_at IS NULL LIMIT 1");
                $chk->execute([':id' => $teamId, ':org_id' => $organizationId]);
                if (!$chk->fetchColumn()) {
                    throw new \InvalidArgumentException('Selected team does not belong to your organization.');
                }
            }

            $coachId = !empty($data['coach_id']) ? (int)$data['coach_id'] : null;
            if ($coachId) {
                $chk = $this->pdo->prepare("SELECT id FROM coach_profiles WHERE id = :id AND organization_id = :org_id AND deleted_at IS NULL LIMIT 1");
                $chk->execute([':id' => $coachId, ':org_id' => $organizationId]);
                if (!$chk->fetchColumn()) {
                    throw new \InvalidArgumentException('Selected coach does not belong to your organization.');
                }
            }

            $venueId = !empty($data['venue_id']) ? (int)$data['venue_id'] : null;
            if ($venueId) {
                $chk = $this->pdo->prepare("SELECT id FROM venues WHERE id = :id AND organization_id = :org_id AND deleted_at IS NULL LIMIT 1");
                $chk->execute([':id' => $venueId, ':org_id' => $organizationId]);
                if (!$chk->fetchColumn()) {
                    throw new \InvalidArgumentException('Selected venue does not belong to your organization.');
                }
            }

            $facilityId = !empty($data['facility_id']) ? (int)$data['facility_id'] : null;
            if ($facilityId) {
                $facSql = "SELECT id, venue_id FROM venue_facilities WHERE id = :id AND organization_id = :org_id AND deleted_at IS NULL LIMIT 1";
                $chk = $this->pdo->prepare($facSql);
                $chk->execute([':id' => $facilityId, ':org_id' => $organizationId]);
                $facRow = $chk->fetch(PDO::FETCH_ASSOC);
                if (!$facRow) {
                    throw new \InvalidArgumentException('Selected facility does not belong to your organization.');
                }
                if ($venueId && (int)$facRow['venue_id'] !== $venueId) {
                    $facilityId = null;
                }
            }

            $sql = "
                INSERT INTO training_sessions (
                    organization_id, training_reference, team_id, coach_id, venue_id,
                    facility_id, training_type, title, objectives, training_date,
                    start_time, end_time, status, notes, created_at, updated_at
                ) VALUES (
                    :org_id, :ref, :team_id, :coach_id, :venue_id,
                    :fac_id, :type, :title, :obj, :tdate,
                    :stime, :etime, :status, :notes, NOW(), NOW()
                )
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':org_id' => $organizationId,
                ':ref' => $ref,
                ':team_id' => $teamId,
                ':coach_id' => $coachId,
                ':venue_id' => $venueId,
                ':fac_id' => $facilityId,
                ':type' => $data['training_type'] ?? 'Tactical Drills',
                ':title' => trim($data['title'] ?? 'Scheduled Training Session'),
                ':obj' => $data['objectives'] ?? null,
                ':tdate' => $data['training_date'] ?? date('Y-m-d'),
                ':stime' => $data['start_time'] ?? '08:00:00',
                ':etime' => $data['end_time'] ?? '10:00:00',
                ':status' => $data['status'] ?? 'scheduled',
                ':notes' => $data['notes'] ?? null,
            ]);

            $sessionId = (int)$this->pdo->lastInsertId();

            // Note: Attendance records start unrecorded (not_marked) until the coach explicitly marks attendance

            $this->pdo->commit();

            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'TRAINING_CREATE',
                'Training Sessions',
                'training_sessions',
                $sessionId,
                null,
                ['reference' => $ref, 'title' => $data['title'] ?? ''],
                "Scheduled training session {$ref} on {$data['training_date']}"
            );

            return [
                'id' => $sessionId,
                'training_reference' => $ref,
                'title' => $data['title'] ?? ''
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateSession(int $organizationId, int $id, array $data, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;

        $existing = $this->getSession($organizationId, $id);
        if (!$existing) return false;

        $coachId = array_key_exists('coach_id', $data)
            ? (!empty($data['coach_id']) ? (int)$data['coach_id'] : null)
            : $existing['coach_id'];
        if ($coachId) {
            $chk = $this->pdo->prepare("SELECT id FROM coach_profiles WHERE id = :id AND organization_id = :org_id AND deleted_at IS NULL LIMIT 1");
            $chk->execute([':id' => $coachId, ':org_id' => $organizationId]);
            if (!$chk->fetchColumn()) {
                throw new \InvalidArgumentException('Selected coach does not belong to your organization.');
            }
        }

        $venueId = array_key_exists('venue_id', $data)
            ? (!empty($data['venue_id']) ? (int)$data['venue_id'] : null)
            : $existing['venue_id'];
        if ($venueId) {
            $chk = $this->pdo->prepare("SELECT id FROM venues WHERE id = :id AND organization_id = :org_id AND deleted_at IS NULL LIMIT 1");
            $chk->execute([':id' => $venueId, ':org_id' => $organizationId]);
            if (!$chk->fetchColumn()) {
                throw new \InvalidArgumentException('Selected venue does not belong to your organization.');
            }
        }

        $facilityId = array_key_exists('facility_id', $data)
            ? (!empty($data['facility_id']) ? (int)$data['facility_id'] : null)
            : $existing['facility_id'];
        if ($facilityId) {
            $chk = $this->pdo->prepare("SELECT id, venue_id FROM venue_facilities WHERE id = :id AND organization_id = :org_id AND deleted_at IS NULL LIMIT 1");
            $chk->execute([':id' => $facilityId, ':org_id' => $organizationId]);
            $facRow = $chk->fetch(PDO::FETCH_ASSOC);
            if (!$facRow) {
                throw new \InvalidArgumentException('Selected facility does not belong to your organization.');
            }
            if ($venueId && (int)$facRow['venue_id'] !== $venueId) {
                $facilityId = null;
            }
        }

        $sql = "
            UPDATE training_sessions SET
                title = :title,
                training_type = :type,
                coach_id = :coach_id,
                venue_id = :venue_id,
                facility_id = :fac_id,
                objectives = :obj,
                training_date = :tdate,
                start_time = :stime,
                end_time = :etime,
                status = :status,
                notes = :notes,
                updated_at = NOW()
            WHERE id = :id AND organization_id = :org_id
        ";

        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute([
            ':title' => trim($data['title'] ?? $existing['title']),
            ':type' => $data['training_type'] ?? $existing['training_type'],
            ':coach_id' => $coachId,
            ':venue_id' => $venueId,
            ':fac_id' => $facilityId,
            ':obj' => $data['objectives'] ?? $existing['objectives'],
            ':tdate' => $data['training_date'] ?? $existing['training_date'],
            ':stime' => $data['start_time'] ?? $existing['start_time'],
            ':etime' => $data['end_time'] ?? $existing['end_time'],
            ':status' => $data['status'] ?? $existing['status'],
            ':notes' => $data['notes'] ?? $existing['notes'],
            ':id' => $id,
            ':org_id' => $organizationId,
        ]);

        if ($ok) {
            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'TRAINING_UPDATE',
                'Training Sessions',
                'training_sessions',
                $id,
                $existing,
                $data,
                "Updated training session #{$id}"
            );
        }

        return $ok;
    }

    public function recordAttendance(int $organizationId, int $sessionId, array $attendanceData, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;

        $existingSession = $this->getSession($organizationId, $sessionId);
        if (!$existingSession) {
            return false;
        }

        $this->pdo->beginTransaction();
        try {
            $athVerifyStmt = $this->pdo->prepare("SELECT id FROM athletes WHERE id = :a_id AND organization_id = :org_id AND deleted_at IS NULL LIMIT 1");

            foreach ($attendanceData as $athleteId => $info) {
                $athleteId = (int)$athleteId;
                if ($athleteId <= 0) continue;

                $athVerifyStmt->execute([':a_id' => $athleteId, ':org_id' => $organizationId]);
                if (!$athVerifyStmt->fetchColumn()) {
                    continue;
                }

                $status = is_array($info) ? ($info['status'] ?? $info['attendance_status'] ?? 'present') : $info;
                $status = strtolower(trim((string)$status));
                if ($status === 'not_marked' || empty($status)) {
                    continue; // Skip unmarked athletes
                }
                $allowed = ['present', 'absent', 'late', 'excused'];
                if (!in_array($status, $allowed, true)) {
                    $status = 'present';
                }
                $hasRemarksKey = is_array($info) && array_key_exists('remarks', $info);
                $remarks = $hasRemarksKey ? (trim((string)$info['remarks']) !== '' ? trim((string)$info['remarks']) : null) : null;
                $cin = is_array($info) && !empty($info['check_in_time']) ? trim((string)$info['check_in_time']) : null;
                $cout = is_array($info) && !empty($info['check_out_time']) ? trim((string)$info['check_out_time']) : null;

                $checkStmt = $this->pdo->prepare("SELECT id FROM training_attendance WHERE training_session_id = :s_id AND athlete_id = :a_id AND organization_id = :org_id");
                $checkStmt->execute([':s_id' => $sessionId, ':a_id' => $athleteId, ':org_id' => $organizationId]);
                $existingId = $checkStmt->fetchColumn();

                if ($existingId) {
                    $sql = "UPDATE training_attendance SET 
                                attendance_status = :status, 
                                remarks = " . ($hasRemarksKey ? ":remarks" : "COALESCE(:remarks, remarks)") . ",
                                check_in_time = COALESCE(:cin, check_in_time),
                                check_out_time = COALESCE(:cout, check_out_time),
                                recorded_by = :rec_by, 
                                updated_at = NOW() 
                            WHERE id = :id AND organization_id = :org_id";
                    $upStmt = $this->pdo->prepare($sql);
                    $upStmt->execute([
                        ':status' => $status,
                        ':remarks' => $remarks,
                        ':cin' => $cin,
                        ':cout' => $cout,
                        ':rec_by' => $performedBy,
                        ':id' => $existingId,
                        ':org_id' => $organizationId
                    ]);
                } else {
                    $inStmt = $this->pdo->prepare("INSERT INTO training_attendance (organization_id, training_session_id, athlete_id, attendance_status, check_in_time, check_out_time, remarks, recorded_by, created_at, updated_at) VALUES (:org_id, :s_id, :a_id, :status, :cin, :cout, :remarks, :rec_by, NOW(), NOW())");
                    $inStmt->execute([
                        ':org_id' => $organizationId,
                        ':s_id' => $sessionId,
                        ':a_id' => $athleteId,
                        ':status' => $status,
                        ':cin' => $cin,
                        ':cout' => $cout,
                        ':remarks' => $remarks,
                        ':rec_by' => $performedBy
                    ]);
                }
            }

            $this->pdo->commit();

            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'TRAINING_ATTENDANCE',
                'Training Sessions',
                'training_attendance',
                $sessionId,
                null,
                ['session_id' => $sessionId, 'records_count' => count($attendanceData)],
                "Updated attendance for training session #{$sessionId}"
            );

            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function deleteSession(int $organizationId, int $id, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;

        $existing = $this->getSession($organizationId, $id);
        if (!$existing) return false;

        $stmt = $this->pdo->prepare("UPDATE training_sessions SET deleted_at = NOW() WHERE id = :id AND organization_id = :org_id");
        $stmt->execute([':id' => $id, ':org_id' => $organizationId]);

        $this->auditLog->log(
            $organizationId,
            $performedBy,
            'TRAINING_DELETE',
            'Training Sessions',
            'training_sessions',
            $id,
            $existing,
            null,
            "Soft deleted training session #{$id}"
        );

        return true;
    }
}
