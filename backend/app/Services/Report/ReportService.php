<?php

namespace App\Services\Report;

use App\Services\BaseService;
use PDO;

class ReportService extends BaseService
{
    public function getDashboardMetrics(int $organizationId): array
    {
        if (!$this->pdo) {
            return [
                'total_athletes' => 0,
                'total_coaches' => 0,
                'total_teams' => 0,
                'upcoming_tournaments' => 0,
                'upcoming_matches' => 0,
                'todays_training' => 0,
                'venue_bookings' => 0,
                'pending_leave' => 0,
                'low_inventory' => 0,
            ];
        }

        // 1. Total Athletes
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM athletes WHERE organization_id = :org AND deleted_at IS NULL");
        $stmt->execute([':org' => $organizationId]);
        $totalAthletes = (int)$stmt->fetchColumn();

        // 2. Total Coaches
        $stmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT cp.id) 
            FROM coach_profiles cp 
            JOIN employees e ON cp.employee_id = e.id 
            WHERE cp.organization_id = :org AND cp.deleted_at IS NULL AND e.deleted_at IS NULL
        ");
        $stmt->execute([':org' => $organizationId]);
        $totalCoaches = (int)$stmt->fetchColumn();

        // 3. Total Teams
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM teams WHERE organization_id = :org AND deleted_at IS NULL");
        $stmt->execute([':org' => $organizationId]);
        $totalTeams = (int)$stmt->fetchColumn();

        // 4. Upcoming Tournaments
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM tournaments 
            WHERE organization_id = :org AND end_date >= CURDATE() AND status != 'cancelled' AND deleted_at IS NULL
        ");
        $stmt->execute([':org' => $organizationId]);
        $upcomingTournaments = (int)$stmt->fetchColumn();

        // 5. Upcoming Matches / Fixtures
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM fixtures 
            WHERE organization_id = :org AND scheduled_date >= CURDATE() AND status != 'completed' AND status != 'cancelled' AND deleted_at IS NULL
        ");
        $stmt->execute([':org' => $organizationId]);
        $upcomingMatches = (int)$stmt->fetchColumn();

        // 6. Today's Training Sessions
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM training_sessions 
            WHERE organization_id = :org AND training_date = CURDATE() AND status != 'cancelled' AND deleted_at IS NULL
        ");
        $stmt->execute([':org' => $organizationId]);
        $todaysTraining = (int)$stmt->fetchColumn();

        // 7. Venue Bookings (Today & Upcoming)
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM venue_bookings 
            WHERE organization_id = :org AND booking_date >= CURDATE() AND status != 'cancelled' AND deleted_at IS NULL
        ");
        $stmt->execute([':org' => $organizationId]);
        $venueBookings = (int)$stmt->fetchColumn();

        // 8. Pending Leave Requests
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM leave_requests 
            WHERE organization_id = :org AND status = 'pending'
        ");
        $stmt->execute([':org' => $organizationId]);
        $pendingLeave = (int)$stmt->fetchColumn();

        // 9. Low Inventory Alerts
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM inventory_items 
            WHERE organization_id = :org AND quantity <= minimum_stock_level AND deleted_at IS NULL
        ");
        $stmt->execute([':org' => $organizationId]);
        $lowInventory = (int)$stmt->fetchColumn();

        return [
            'total_athletes' => $totalAthletes,
            'total_coaches' => $totalCoaches,
            'total_teams' => $totalTeams,
            'upcoming_tournaments' => $upcomingTournaments,
            'upcoming_matches' => $upcomingMatches,
            'todays_training' => $todaysTraining,
            'venue_bookings' => $venueBookings,
            'pending_leave' => $pendingLeave,
            'low_inventory' => $lowInventory,
        ];
    }

    public function getUpcomingFixtures(int $organizationId, int $limit = 5): array
    {
        if (!$this->pdo) return [];

        $stmt = $this->pdo->prepare("
            SELECT f.id, f.fixture_reference, f.round_name, f.scheduled_date, f.scheduled_start_time, f.status,
                   t.name as tournament_name,
                   ht.name as home_team_name, ht.team_code as home_team_code,
                   at.name as away_team_name, at.team_code as away_team_code,
                   v.name as venue_name,
                   vf.name as facility_name
            FROM fixtures f
            LEFT JOIN tournaments t ON f.tournament_id = t.id
            LEFT JOIN teams ht ON f.home_team_id = ht.id
            LEFT JOIN teams at ON f.away_team_id = at.id
            LEFT JOIN venues v ON f.venue_id = v.id
            LEFT JOIN venue_facilities vf ON f.facility_id = vf.id
            WHERE f.organization_id = :org AND f.deleted_at IS NULL
            ORDER BY f.scheduled_date ASC, f.scheduled_start_time ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':org', $organizationId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getTodaySessions(int $organizationId, int $limit = 5): array
    {
        if (!$this->pdo) return [];

        $stmt = $this->pdo->prepare("
            SELECT ts.id, ts.training_reference, ts.title, ts.training_type, ts.training_date, ts.start_time, ts.end_time, ts.status,
                   tm.name as team_name,
                   CONCAT(e.first_name, ' ', COALESCE(e.last_name, '')) as coach_name,
                   v.name as venue_name,
                   vf.name as facility_name,
                   s.name as sport_name
            FROM training_sessions ts
            LEFT JOIN teams tm ON ts.team_id = tm.id
            LEFT JOIN sports s ON tm.sport_id = s.id
            LEFT JOIN coach_profiles cp ON ts.coach_id = cp.id
            LEFT JOIN employees e ON cp.employee_id = e.id
            LEFT JOIN venues v ON ts.venue_id = v.id
            LEFT JOIN venue_facilities vf ON ts.facility_id = vf.id
            WHERE ts.organization_id = :org AND ts.deleted_at IS NULL
            ORDER BY ts.training_date DESC, ts.start_time ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':org', $organizationId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getOperationalReports(int $organizationId): array
    {
        if (!$this->pdo) return [];

        // 1. Athletes by sport
        $stmt = $this->pdo->prepare("
            SELECT s.name as sport_name, COUNT(a.id) as count 
            FROM sports s 
            LEFT JOIN athletes a ON a.current_sport_id = s.id AND a.organization_id = :org AND a.deleted_at IS NULL 
            GROUP BY s.id, s.name 
            ORDER BY count DESC
        ");
        $stmt->execute([':org' => $organizationId]);
        $athletesBySport = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 2. Teams by sport
        $stmt = $this->pdo->prepare("
            SELECT s.name as sport_name, COUNT(t.id) as count 
            FROM sports s 
            LEFT JOIN teams t ON t.sport_id = s.id AND t.organization_id = :org AND t.deleted_at IS NULL 
            GROUP BY s.id, s.name 
            ORDER BY count DESC
        ");
        $stmt->execute([':org' => $organizationId]);
        $teamsBySport = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 3. Training Attendance metrics
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_records,
                SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN attendance_status = 'excused' THEN 1 ELSE 0 END) as excused_count
            FROM training_attendance ta
            JOIN training_sessions ts ON ta.training_session_id = ts.id
            WHERE ts.organization_id = :org AND ts.deleted_at IS NULL
        ");
        $stmt->execute([':org' => $organizationId]);
        $attendanceStats = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_records' => 0, 'present_count' => 0, 'absent_count' => 0, 'excused_count' => 0];
        $totalAtt = (int)($attendanceStats['total_records'] ?? 0);
        $presentAtt = (int)($attendanceStats['present_count'] ?? 0);
        $attendanceRate = $totalAtt > 0 ? round(($presentAtt / $totalAtt) * 100, 1) : 0;
        $attendanceStats['attendance_rate'] = $attendanceRate;

        // 4. Tournament & Match Activity
        $stmt = $this->pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM tournaments WHERE organization_id = :org AND deleted_at IS NULL) as total_tournaments,
                (SELECT COUNT(*) FROM fixtures WHERE organization_id = :org AND deleted_at IS NULL) as total_fixtures,
                (SELECT COUNT(*) FROM matches m JOIN fixtures f ON m.fixture_id = f.id WHERE f.organization_id = :org AND m.status = 'completed') as completed_matches,
                (SELECT COUNT(*) FROM matches m JOIN fixtures f ON m.fixture_id = f.id WHERE f.organization_id = :org AND m.status != 'completed') as pending_matches
        ");
        $stmt->execute([':org' => $organizationId]);
        $tournamentActivity = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // 5. Venue Utilization
        $stmt = $this->pdo->prepare("
            SELECT v.name as venue_name, COUNT(vb.id) as booking_count
            FROM venues v
            LEFT JOIN venue_bookings vb ON vb.venue_id = v.id AND vb.organization_id = :org AND vb.deleted_at IS NULL
            WHERE v.organization_id = :org AND v.deleted_at IS NULL
            GROUP BY v.id, v.name
            ORDER BY booking_count DESC
        ");
        $stmt->execute([':org' => $organizationId]);
        $venueUtilization = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 6. Leave Summary
        $stmt = $this->pdo->prepare("
            SELECT 
                status, COUNT(*) as count
            FROM leave_requests
            WHERE organization_id = :org
            GROUP BY status
        ");
        $stmt->execute([':org' => $organizationId]);
        $leaveStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        // 7. Inventory Valuation & Summary
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_items,
                SUM(quantity) as total_units,
                SUM(quantity * unit_cost) as total_valuation,
                SUM(CASE WHEN quantity <= minimum_stock_level THEN 1 ELSE 0 END) as low_stock_count
            FROM inventory_items
            WHERE organization_id = :org AND deleted_at IS NULL
        ");
        $stmt->execute([':org' => $organizationId]);
        $inventoryStats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // 8. Payroll Summary
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(DISTINCT employee_id) as total_employees,
                COALESCE(SUM(gross_salary), 0) as total_gross,
                COALESCE(SUM(net_salary), 0) as total_net
            FROM payroll
            WHERE organization_id = :org
        ");
        $stmt->execute([':org' => $organizationId]);
        $payrollStats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'athletes_by_sport' => $athletesBySport,
            'teams_by_sport' => $teamsBySport,
            'attendance_stats' => $attendanceStats,
            'tournament_activity' => $tournamentActivity,
            'venue_utilization' => $venueUtilization,
            'leave_stats' => $leaveStats,
            'inventory_stats' => $inventoryStats,
            'payroll_stats' => $payrollStats,
        ];
    }
}

