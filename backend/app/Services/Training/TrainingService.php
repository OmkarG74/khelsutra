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

    public function listSessions(int $organizationId, int $page = 1, int $limit = 15, ?string $search = null, ?string $date = null, ?string $status = null, ?int $teamId = null, ?int $coachId = null): array
    {
        if (!$this->pdo) {
            return ['data' => [], 'total' => 0, 'page' => 1, 'limit' => $limit, 'total_pages' => 0];
        }

        $conditions = ["ts.organization_id = :org_id", "ts.deleted_at IS NULL"];
        $params = [':org_id' => $organizationId];

        if (!empty($search)) {
            $conditions[] = "(ts.title LIKE :search OR ts.training_reference LIKE :search OR ts.training_type LIKE :search OR t.name LIKE :search)";
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

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM training_sessions ts LEFT JOIN teams t ON ts.team_id = t.id WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT 
                ts.*,
                t.name as team_name,
                s.name as sport_name,
                v.name as venue_name,
                vf.name as facility_name,
                CONCAT(e.first_name, ' ', e.last_name) as coach_name,
                COUNT(DISTINCT ta.id) as total_roster_count,
                SUM(CASE WHEN ta.attendance_status = 'present' THEN 1 ELSE 0 END) as present_count
            FROM training_sessions ts
            LEFT JOIN teams t ON ts.team_id = t.id
            LEFT JOIN sports s ON t.sport_id = s.id
            LEFT JOIN venues v ON ts.venue_id = v.id
            LEFT JOIN venue_facilities vf ON ts.facility_id = vf.id
            LEFT JOIN coach_profiles cp ON ts.coach_id = cp.id
            LEFT JOIN employees e ON cp.employee_id = e.id
            LEFT JOIN training_attendance ta ON ts.id = ta.training_session_id
            WHERE {$whereClause}
            GROUP BY ts.id, t.name, s.name, v.name, vf.name, e.first_name, e.last_name
            ORDER BY ts.training_date DESC, ts.start_time DESC
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
            $s['total_roster_count'] = (int)($s['total_roster_count'] ?? 0);
            $s['present_count'] = (int)($s['present_count'] ?? 0);
        }
        unset($s);

        return [
            'data' => $sessions,
            'total' => (int)$total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => (int)ceil($total / max(1, $limit)),
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
                e.phone as coach_phone
            FROM training_sessions ts
            LEFT JOIN teams t ON ts.team_id = t.id
            LEFT JOIN sports s ON t.sport_id = s.id
            LEFT JOIN venues v ON ts.venue_id = v.id
            LEFT JOIN venue_facilities vf ON ts.facility_id = vf.id
            LEFT JOIN coach_profiles cp ON ts.coach_id = cp.id
            LEFT JOIN employees e ON cp.employee_id = e.id
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
                MAX(tm.jersey_number) as jersey_number,
                MAX(tm.member_role) as member_role,
                MAX(ta.id) as attendance_id,
                COALESCE(MAX(ta.attendance_status), 'not_marked') as attendance_status,
                MAX(ta.check_in_time) as check_in_time,
                MAX(ta.remarks) as remarks
            FROM team_members tm
            JOIN athletes a ON tm.athlete_id = a.id AND a.deleted_at IS NULL
            LEFT JOIN training_attendance ta ON ta.training_session_id = :session_id AND ta.athlete_id = a.id
            WHERE tm.team_id = :team_id AND tm.organization_id = :org_id AND tm.is_current = 1
            GROUP BY a.id, a.athlete_code, a.first_name, a.last_name
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
        foreach ($roster as &$r) {
            $r['athlete_id'] = (int)$r['athlete_id'];
            if (isset($r['attendance_id']) && $r['attendance_id'] !== null) {
                $r['attendance_id'] = (int)$r['attendance_id'];
            }
        }
        unset($r);
        $session['roster_attendance'] = $roster;

        return $session;
    }

    public function createSession(int $organizationId, array $data, ?int $performedBy = null): array
    {
        if (!$this->pdo) return [];

        $this->pdo->beginTransaction();
        try {
            $ref = $data['training_reference'] ?? ('TRN-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4)));

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
                ':team_id' => !empty($data['team_id']) ? (int)$data['team_id'] : null,
                ':coach_id' => !empty($data['coach_id']) ? (int)$data['coach_id'] : null,
                ':venue_id' => !empty($data['venue_id']) ? (int)$data['venue_id'] : null,
                ':fac_id' => !empty($data['facility_id']) ? (int)$data['facility_id'] : null,
                ':type' => $data['training_type'] ?? 'Tactical Drill',
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
            ':coach_id' => !empty($data['coach_id']) ? (int)$data['coach_id'] : $existing['coach_id'],
            ':venue_id' => !empty($data['venue_id']) ? (int)$data['venue_id'] : $existing['venue_id'],
            ':fac_id' => !empty($data['facility_id']) ? (int)$data['facility_id'] : $existing['facility_id'],
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

        $this->pdo->beginTransaction();
        try {
            foreach ($attendanceData as $athleteId => $info) {
                $athleteId = (int)$athleteId;
                if ($athleteId <= 0) continue;

                $status = is_array($info) ? ($info['status'] ?? $info['attendance_status'] ?? 'present') : $info;
                $status = strtolower(trim((string)$status));
                if ($status === 'not_marked' || empty($status)) {
                    continue; // Skip unmarked athletes
                }
                $allowed = ['present', 'absent', 'late', 'excused'];
                if (!in_array($status, $allowed, true)) {
                    $status = 'present';
                }
                $remarks = is_array($info) ? ($info['remarks'] ?? null) : null;

                $checkStmt = $this->pdo->prepare("SELECT id FROM training_attendance WHERE training_session_id = :s_id AND athlete_id = :a_id AND organization_id = :org_id");
                $checkStmt->execute([':s_id' => $sessionId, ':a_id' => $athleteId, ':org_id' => $organizationId]);
                $existingId = $checkStmt->fetchColumn();

                if ($existingId) {
                    $upStmt = $this->pdo->prepare("UPDATE training_attendance SET attendance_status = :status, remarks = COALESCE(:remarks, remarks), recorded_by = :rec_by, updated_at = NOW() WHERE id = :id");
                    $upStmt->execute([':status' => $status, ':remarks' => $remarks, ':rec_by' => $performedBy, ':id' => $existingId]);
                } else {
                    $inStmt = $this->pdo->prepare("INSERT INTO training_attendance (organization_id, training_session_id, athlete_id, attendance_status, remarks, recorded_by, created_at, updated_at) VALUES (:org_id, :s_id, :a_id, :status, :remarks, :rec_by, NOW(), NOW())");
                    $inStmt->execute([
                        ':org_id' => $organizationId,
                        ':s_id' => $sessionId,
                        ':a_id' => $athleteId,
                        ':status' => $status,
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
