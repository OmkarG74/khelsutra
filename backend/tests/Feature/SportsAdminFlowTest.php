<?php

namespace Tests\Feature;

use App\Services\Athlete\AthleteService;
use App\Services\Coach\CoachService;
use App\Services\Team\TeamService;
use App\Services\Training\TrainingService;
use App\Services\Tournament\TournamentService;
use App\Services\Venue\VenueService;
use App\Services\Inventory\InventoryService;
use App\Services\Report\ReportService;
use App\Services\BaseService;
use PDO;

class SportsAdminFlowTest
{
    private int $orgId = 1; // Apex Sports Academy
    private int $userId = 2; // Sports Admin / Authorized user
    private ?PDO $pdo;

    public function __construct()
    {
        $this->pdo = BaseService::getDatabaseConnection();
    }

    public function testSportsAdminAthleteFlow(): bool
    {
        $service = new AthleteService();
        $code = 'ATH-TEST-' . rand(1000, 9999);

        // 1. Register
        $athlete = $service->registerAthlete($this->orgId, [
            'first_name' => 'Automated',
            'last_name' => 'Runner',
            'date_of_birth' => '2006-05-15',
            'gender' => 'male',
            'current_sport_id' => 1,
            'admission_number' => $code,
            'status' => 'active',
            'guardian_name' => 'Test Guardian',
            'guardian_relationship' => 'Father',
            'guardian_phone' => '9876543210',
        ], $this->userId);

        if (empty($athlete['id'])) return false;
        $athleteId = (int)$athlete['id'];

        // 2. Fetch & verify details
        $details = $service->getAthlete($this->orgId, $athleteId);
        if (!$details || $details['first_name'] !== 'Automated') return false;

        // 3. Tenant Isolation Check: Org 2 should NOT be able to access Org 1's athlete
        $crossOrgDetails = $service->getAthlete(2, $athleteId);
        if ($crossOrgDetails !== null) return false;

        // 4. Update
        $updated = $service->updateAthlete($this->orgId, $athleteId, [
            'first_name' => 'AutomatedUpdated',
            'last_name' => 'Runner',
            'status' => 'active',
        ], $this->userId);
        if (!$updated) return false;

        $check = $service->getAthlete($this->orgId, $athleteId);
        if ($check['first_name'] !== 'AutomatedUpdated') return false;

        // 5. Soft Delete
        $deleted = $service->deleteAthlete($this->orgId, $athleteId, $this->userId);
        if (!$deleted) return false;

        $checkAfterDelete = $service->getAthlete($this->orgId, $athleteId);
        if ($checkAfterDelete !== null) return false;

        return true;
    }

    public function testSportsAdminCoachFlow(): bool
    {
        $service = new CoachService();
        $code = 'EMP-COACH-' . rand(100, 999);
        $email = 'coach.' . rand(1000, 9999) . '@testacademy.com';

        // 1. Create Coach
        $coach = $service->createCoach($this->orgId, [
            'first_name' => 'Sanjay',
            'last_name' => 'Bangar',
            'email' => $email,
            'employee_code' => $code,
            'specialization' => 'Batting & Power Hitting',
            'experience_years' => 12,
            'license_level' => 'BCCI Level 3',
        ], $this->userId);

        $coachProfileId = (int)($coach['coach_profile_id'] ?? $coach['id'] ?? 0);
        if ($coachProfileId <= 0) return false;

        // 2. Fetch details
        $details = $service->getCoach($this->orgId, $coachProfileId);
        if (!$details || $details['specialization'] !== 'Batting & Power Hitting') return false;

        // 3. Cross-Tenant isolation check
        $crossCheck = $service->getCoach(2, $coachProfileId);
        if ($crossCheck !== null) return false;

        // 4. Update Coach
        $ok = $service->updateCoach($this->orgId, $coachProfileId, [
            'specialization' => 'High Performance Batting Specialist',
            'license_level' => 'ICC Level 3 High Performance',
            'experience_years' => 14,
        ], $this->userId);
        if (!$ok) return false;

        $updatedDetails = $service->getCoach($this->orgId, $coachProfileId);
        return $updatedDetails['specialization'] === 'High Performance Batting Specialist';
    }

    public function testSportsAdminTeamFlow(): bool
    {
        $service = new TeamService();
        $code = 'TM-' . rand(100, 999);

        // 1. Create Team with Coach and Athletes
        $team = $service->createTeam($this->orgId, [
            'name' => 'Apex Test Strikers',
            'team_code' => $code,
            'sport_id' => 1,
            'gender' => 'men',
            'age_group' => 'senior',
            'coach_id' => 1,
            'athlete_ids' => [1, 2],
            'status' => 'active',
        ], $this->userId);

        if (empty($team['id'])) return false;
        $teamId = (int)$team['id'];

        // 2. Fetch & verify details
        $details = $service->getTeam($this->orgId, $teamId);
        if (!$details || $details['team_code'] !== $code) return false;
        if (empty($details['current_athletes'])) return false;

        // 3. Update team
        $updated = $service->updateTeam($this->orgId, $teamId, [
            'name' => 'Apex Test Strikers Elite',
            'status' => 'active',
        ], $this->userId);

        if (!$updated) return false;

        $check = $service->getTeam($this->orgId, $teamId);
        return $check['name'] === 'Apex Test Strikers Elite';
    }

    public function testSportsAdminTrainingFlow(): bool
    {
        $service = new TrainingService();

        // Query a team
        $stmt = $this->pdo->prepare("SELECT id FROM teams WHERE organization_id = :org AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([':org' => $this->orgId]);
        $teamId = (int)$stmt->fetchColumn();

        // 1. Schedule Session
        $session = $service->createSession($this->orgId, [
            'team_id' => $teamId > 0 ? $teamId : 1,
            'title' => 'Automated Defensive Tactics Session',
            'training_type' => 'tactical',
            'training_date' => date('Y-m-d'),
            'start_time' => '16:00:00',
            'end_time' => '18:00:00',
            'objectives' => 'High pressing and zonal marking drill',
        ], $this->userId);

        if (empty($session['id'])) return false;
        $sessionId = (int)$session['id'];

        // 2. Fetch session details
        $details = $service->getSession($this->orgId, $sessionId);
        if (!$details || $details['title'] !== 'Automated Defensive Tactics Session') return false;

        // 3. Record Attendance
        $stmt = $this->pdo->prepare("SELECT id FROM athletes WHERE organization_id = :org AND deleted_at IS NULL LIMIT 2");
        $stmt->execute([':org' => $this->orgId]);
        $athletes = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($athletes)) {
            $attendanceData = [];
            foreach ($athletes as $aId) {
                $attendanceData[(int)$aId] = 'present';
            }
            $service->recordAttendance($this->orgId, $sessionId, $attendanceData, $this->userId);
            $detailsAfter = $service->getSession($this->orgId, $sessionId);
            if (empty($detailsAfter['roster_attendance'])) return false;
        }

        return true;
    }

    public function testSportsAdminTournamentFlow(): bool
    {
        $service = new TournamentService();

        // 1. Create Tournament
        $code = 'TRN-' . rand(100, 999);
        $tourney = $service->createTournament($this->orgId, [
            'name' => 'Apex Champions League ' . date('Y'),
            'tournament_code' => $code,
            'sport_id' => 1,
            'level_id' => 1,
            'format_id' => 1,
            'start_date' => date('Y-m-d', strtotime('+7 days')),
            'end_date' => date('Y-m-d', strtotime('+14 days')),
            'status' => 'scheduled',
        ], $this->userId);

        if (empty($tourney['id'])) return false;
        $tourneyId = (int)$tourney['id'];

        // 2. Add participating teams
        $stmt = $this->pdo->prepare("SELECT id FROM teams WHERE organization_id = :org AND deleted_at IS NULL LIMIT 2");
        $stmt->execute([':org' => $this->orgId]);
        $teams = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($teams) >= 2) {
            $service->addTeam($this->orgId, $tourneyId, (int)$teams[0], $this->userId);
            $service->addTeam($this->orgId, $tourneyId, (int)$teams[1], $this->userId);

            // 3. Create fixture
            $fixture = $service->createFixture($this->orgId, $tourneyId, [
                'round_name' => 'Group Match 1',
                'home_team_id' => (int)$teams[0],
                'away_team_id' => (int)$teams[1],
                'scheduled_date' => date('Y-m-d', strtotime('+8 days')),
                'scheduled_start_time' => '15:00:00',
            ], $this->userId);

            if (empty($fixture['id'])) return false;

            // 4. Record Match Result
            $res = $service->updateMatchResult($this->orgId, (int)$fixture['id'], [
                'home_score' => 3,
                'away_score' => 1,
                'winner_team_id' => (int)$teams[0],
            ], $this->userId);

            if (!$res) return false;

            // 5. Verify Standings updated
            $details = $service->getTournament($this->orgId, $tourneyId);
            if (empty($details['standings'])) return false;
        }

        return true;
    }

    public function testSportsAdminVenueBookingConflictFlow(): bool
    {
        $service = new VenueService();

        // 1. Create Venue
        $venue = $service->createVenue($this->orgId, [
            'name' => 'Apex Test Arena ' . rand(100, 999),
            'address' => 'Olympic Boulevard Sector 4',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'contact_phone' => '020-88997766',
        ], $this->userId);

        if (empty($venue['id'])) return false;
        $venueId = (int)$venue['id'];

        // 2. Create Facility
        $facility = $service->createFacility($this->orgId, $venueId, [
            'name' => 'Main Turf Pitch A',
            'facility_type' => 'turf',
            'hourly_rate' => 1200.00,
            'capacity' => 200,
        ], $this->userId);

        if (empty($facility['id'])) return false;
        $facilityId = (int)$facility['id'];

        // 3. Booking 1: 10:00 to 12:00
        $bookingDate = date('Y-m-d', strtotime('+3 days'));
        $b1 = $service->createBooking($this->orgId, [
            'venue_id' => $venueId,
            'facility_id' => $facilityId,
            'booked_by_name' => 'Apex Titans FC',
            'booking_date' => $bookingDate,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'total_amount' => 2400.00,
        ], $this->userId);

        if (empty($b1['id'])) return false;

        // 4. Overlapping Booking Attempt: 11:00 to 13:00 (Should be rejected with conflict exception)
        $conflictCaught = false;
        try {
            $service->createBooking($this->orgId, [
                'venue_id' => $venueId,
                'facility_id' => $facilityId,
                'booked_by_name' => 'Rival Club',
                'booking_date' => $bookingDate,
                'start_time' => '11:00:00',
                'end_time' => '13:00:00',
                'total_amount' => 2400.00,
            ], $this->userId);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'conflict') || str_contains($e->getMessage(), 'already booked')) {
                $conflictCaught = true;
            }
        }

        return $conflictCaught;
    }

    public function testSportsAdminInventoryFlow(): bool
    {
        $inv = new InventoryService();
        $code = 'TEST-BALL-' . rand(100, 999);

        // 1. Create item
        $item = $inv->createItem($this->orgId, [
            'category_id' => 1,
            'item_code' => $code,
            'item_name' => 'Automated Test Cricket Bat',
            'quantity' => 15,
            'unit_cost' => 2500.00,
            'minimum_stock_level' => 3,
            'reorder_level' => 5,
            'location_name' => 'Gear Locker C'
        ], $this->userId);

        if (empty($item['id'])) return false;
        $itemId = (int)$item['id'];

        // 2. Issue 5 items
        $inv->recordStockTransaction($this->orgId, $itemId, [
            'transaction_type' => 'issue',
            'quantity' => 5,
            'remarks' => 'Issued to batting academy'
        ], $this->userId);

        $check1 = $inv->getItem($this->orgId, $itemId);
        if ((int)$check1['quantity'] !== 10) return false;

        // 3. Receive 10 items
        $inv->recordStockTransaction($this->orgId, $itemId, [
            'transaction_type' => 'receive',
            'quantity' => 10,
            'remarks' => 'Received new shipment'
        ], $this->userId);

        $check2 = $inv->getItem($this->orgId, $itemId);
        if ((int)$check2['quantity'] !== 20) return false;

        // 4. Insufficient stock check
        $caught = false;
        try {
            $inv->recordStockTransaction($this->orgId, $itemId, [
                'transaction_type' => 'issue',
                'quantity' => 50,
            ], $this->userId);
        } catch (\Throwable $e) {
            $caught = true;
        }

        if (!$caught) return false;

        // 5. Clean up soft delete
        $inv->deleteItem($this->orgId, $itemId, $this->userId);
        return true;
    }

    public function testSportsAdminOperationalReports(): bool
    {
        $reportService = new ReportService();
        $reports = $reportService->getOperationalReports($this->orgId);

        if (!isset($reports['athletes_by_sport']) || !isset($reports['tournament_activity'])) {
            return false;
        }
        if (!isset($reports['attendance_stats']) || !isset($reports['inventory_stats'])) {
            return false;
        }

        return true;
    }

    public function testAuditLogVerification(): bool
    {
        if (!$this->pdo) return false;
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE organization_id = :org");
        $stmt->execute([':org' => $this->orgId]);
        $count = (int)$stmt->fetchColumn();

        return $count > 0;
    }
}
