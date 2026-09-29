<?php

namespace Tests\Feature;

use App\Services\Athlete\AthleteService;
use App\Services\Sport\SportService;
use App\Services\Team\TeamService;
use App\Services\BaseService;
use PDO;

class AthleteSportTeamValidationTest
{
    private PDO $pdo;
    private AthleteService $athleteService;
    private TeamService $teamService;
    private SportService $sportService;
    private int $orgId = 1;
    private int $userId = 5;

    public function __construct()
    {
        $this->pdo = BaseService::getDatabaseConnection();
        $this->athleteService = new AthleteService();
        $this->teamService = new TeamService($this->pdo);
        $this->sportService = new SportService($this->pdo);
    }

    public function testSportTeamValidation(): bool
    {
        $badmintonId = $this->sportService->resolveSportId('badminton');
        $cricketId = $this->sportService->resolveSportId('cricket');

        // 1. Ensure or find a cricket team in org 1
        $stmt = $this->pdo->prepare("SELECT id FROM teams WHERE organization_id = :org AND sport_id = :sport AND status = 'active' AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([':org' => $this->orgId, ':sport' => $cricketId]);
        $cricketTeam = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cricketTeam) {
            $cricketTeamId = $this->teamService->createTeam($this->orgId, [
                'name' => 'Cricket Validation Test Team',
                'team_code' => 'CRK-VAL-' . rand(100, 999),
                'sport_id' => $cricketId,
                'status' => 'active'
            ], $this->userId);
        } else {
            $cricketTeamId = (int)$cricketTeam['id'];
        }

        // 2. Register a test athlete with badminton sport
        $athleteData = [
            'first_name' => 'SportVal',
            'last_name' => 'Athlete',
            'date_of_birth' => '2005-05-15',
            'gender' => 'male',
            'current_sport_id' => $badmintonId,
            'status' => 'active'
        ];

        $athlete = $this->athleteService->registerAthlete($this->orgId, $athleteData, $this->userId);
        $athleteId = (int)($athlete['id'] ?? 0);
        if ($athleteId <= 0) return false;

        $mismatchBlocked = false;
        try {
            // Attempt to assign Cricket team to Badminton athlete -> Must throw
            $this->athleteService->updateAthlete($this->orgId, $athleteId, [
                'current_sport_id' => $badmintonId,
                'team_id' => $cricketTeamId
            ], $this->userId);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'The selected team does not match')) {
                $mismatchBlocked = true;
            }
        }

        // Cleanup test athlete
        $this->athleteService->deleteAthlete($this->orgId, $athleteId, $this->userId);

        return $mismatchBlocked;
    }
}
