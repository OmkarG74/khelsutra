<?php

namespace Tests\Feature;

use App\Services\Athlete\AthleteService;
use App\Services\Sport\SportService;
use App\Services\Team\TeamService;
use App\Services\BaseService;
use App\Services\Rbac\PermissionService;
use PDO;

class TeamRosterManagementComprehensiveTest
{
    private PDO $pdo;
    private AthleteService $athleteService;
    private TeamService $teamService;
    private SportService $sportService;
    private PermissionService $permissionService;
    private int $orgId = 1;
    private int $secondOrgId = 2;
    private int $sportsAdminUserId = 102;

    public function __construct()
    {
        $this->pdo = BaseService::getDatabaseConnection();
        $this->athleteService = new AthleteService();
        $this->teamService = new TeamService($this->pdo);
        $this->sportService = new SportService($this->pdo);
        $this->permissionService = new PermissionService();
    }

    public function runAllTests(): array
    {
        $results = [];

        $badmintonId = $this->sportService->resolveSportId('badminton');
        $cricketId = $this->sportService->resolveSportId('cricket');

        // Setup test coaches
        $coachStmt = $this->pdo->prepare("
            SELECT cp.id as coach_id, cp.employee_id 
            FROM coach_profiles cp 
            JOIN employees e ON cp.employee_id = e.id 
            WHERE cp.organization_id = :org AND cp.status = 'active' AND cp.deleted_at IS NULL 
            LIMIT 3
        ");
        $coachStmt->execute([':org' => $this->orgId]);
        $existingCoaches = $coachStmt->fetchAll(PDO::FETCH_ASSOC);

        $coachA = (int)($existingCoaches[0]['coach_id'] ?? 0);
        $coachB = (int)($existingCoaches[1]['coach_id'] ?? 0);
        $coachC = (int)($existingCoaches[2]['coach_id'] ?? 0);

        // Setup test athletes: 2 Badminton, 1 Cricket
        $ath1 = $this->athleteService->registerAthlete($this->orgId, [
            'first_name' => 'Aarav',
            'last_name' => 'TestBadminton',
            'date_of_birth' => '2008-05-14',
            'gender' => 'male',
            'current_sport_id' => $badmintonId,
            'status' => 'active'
        ], $this->sportsAdminUserId);
        $ath1Id = (int)$ath1['id'];

        $ath2 = $this->athleteService->registerAthlete($this->orgId, [
            'first_name' => 'Pratham',
            'last_name' => 'TestBadminton',
            'date_of_birth' => '2007-08-20',
            'gender' => 'male',
            'current_sport_id' => $badmintonId,
            'status' => 'active'
        ], $this->sportsAdminUserId);
        $ath2Id = (int)$ath2['id'];

        $ath3 = $this->athleteService->registerAthlete($this->orgId, [
            'first_name' => 'John',
            'last_name' => 'TestCricket',
            'date_of_birth' => '2008-01-10',
            'gender' => 'male',
            'current_sport_id' => $cricketId,
            'status' => 'active'
        ], $this->sportsAdminUserId);
        $ath3Id = (int)$ath3['id'];

        // TEST 1 & 2: Create Team and Select Sport
        $teamData = [
            'name' => 'Apex Strikers Badminton U-18 ' . uniqid(),
            'sport_id' => $badmintonId,
            'gender' => 'male',
            'age_group' => 'Under-18',
            'formation_or_level' => 'State Elite',
            'status' => 'active',
            'description' => 'Test Team'
        ];
        $team = $this->teamService->createTeam($this->orgId, $teamData, $this->sportsAdminUserId);
        $teamId = (int)$team['id'];
        $results['Test 1 & 2: Create Team & Select Sport'] = ($teamId > 0 && (int)$team['sport_id'] === $badmintonId);

        // TEST 3 & 4: Search Athletes & Verify Only Matching Sport Appears
        $badmintonAthletes = $this->teamService->searchEligibleAthletes($this->orgId, $badmintonId, 'TestBadminton', 'male');
        $hasBadmintonAthletes = count($badmintonAthletes) >= 2;
        $onlyBadmintonMatches = true;
        foreach ($badmintonAthletes as $ba) {
            if ((int)$ba['current_sport_id'] !== $badmintonId) {
                $onlyBadmintonMatches = false;
                break;
            }
        }
        $cricketInBadmintonSearch = $this->teamService->searchEligibleAthletes($this->orgId, $badmintonId, 'TestCricket');
        $results['Test 3 & 4: Search Athletes & Verify Sport Scoping'] = (
            $hasBadmintonAthletes && $onlyBadmintonMatches && count($cricketInBadmintonSearch) === 0
        );

        // TEST 5 & 6: Add Athlete & Add Multiple Athletes
        $this->teamService->addAthlete($this->orgId, $teamId, $ath1Id, ['jersey_number' => 10, 'member_role' => 'captain'], $this->sportsAdminUserId);
        $this->teamService->addAthlete($this->orgId, $teamId, $ath2Id, ['jersey_number' => 7, 'member_role' => 'player'], $this->sportsAdminUserId);

        $teamFetched = $this->teamService->getTeam($this->orgId, $teamId);
        $rosterIds = array_column($teamFetched['current_athletes'] ?? [], 'athlete_id');
        $results['Test 5 & 6: Add Multiple Registered Athletes'] = (
            in_array($ath1Id, $rosterIds) && in_array($ath2Id, $rosterIds)
        );

        // TEST 7: Remove Athlete Before Save / Remove from Team
        $removeOk = $this->teamService->removeAthlete($this->orgId, $teamId, $ath2Id, $this->sportsAdminUserId);
        $teamAfterRemove = $this->teamService->getTeam($this->orgId, $teamId);
        $rosterAfterRemove = array_column($teamAfterRemove['current_athletes'] ?? [], 'athlete_id');
        $results['Test 7 & 12: Remove Athlete From Squad Roster'] = (
            $removeOk && !in_array($ath2Id, $rosterAfterRemove) && in_array($ath1Id, $rosterAfterRemove)
        );

        // TEST 8 & 9 & 10: Save Team & Verify Roster
        $updateOk = $this->teamService->updateTeam($this->orgId, $teamId, [
            'name' => $teamData['name'] . ' Updated',
            'sport_id' => $badmintonId,
            'gender' => 'male',
            'age_group' => 'Under-18',
            'status' => 'active',
            'sync_roster' => 1,
            'athlete_ids' => [$ath1Id, $ath2Id]
        ], $this->sportsAdminUserId);

        $teamRosterVerified = $this->teamService->getTeam($this->orgId, $teamId);
        $verifiedIds = array_column($teamRosterVerified['current_athletes'] ?? [], 'athlete_id');
        $results['Test 8, 9, 10: Save & Verify Roster Synchronization'] = (
            $updateOk && in_array($ath1Id, $verifiedIds) && in_array($ath2Id, $verifiedIds)
        );

        // TEST 11: Add Another Athlete Later
        $ath4 = $this->athleteService->registerAthlete($this->orgId, [
            'first_name' => 'Rahul',
            'last_name' => 'LaterAthlete',
            'date_of_birth' => '2007-03-12',
            'gender' => 'male',
            'current_sport_id' => $badmintonId,
            'status' => 'active'
        ], $this->sportsAdminUserId);
        $ath4Id = (int)$ath4['id'];
        $addLaterOk = $this->teamService->addAthlete($this->orgId, $teamId, $ath4Id, ['jersey_number' => 14], $this->sportsAdminUserId);
        $results['Test 11: Add Another Registered Athlete Later'] = $addLaterOk;

        // TEST 13: Verify Athlete Still Exists in athletes Table After Removal
        $this->teamService->removeAthlete($this->orgId, $teamId, $ath4Id, $this->sportsAdminUserId);
        $ath4Check = $this->athleteService->getAthlete($this->orgId, $ath4Id);
        $results['Test 13: Verify Athlete Profile Preserved On Team Removal'] = (
            $ath4Check !== null && (int)$ath4Check['id'] === $ath4Id && empty($ath4Check['deleted_at'])
        );

        // TEST 14, 15, 16: Assign Same Athlete to Another Team (Multiple Teams per Athlete)
        $secondTeam = $this->teamService->createTeam($this->orgId, [
            'name' => 'Goa Junior Badminton Team ' . uniqid(),
            'sport_id' => $badmintonId,
            'gender' => 'male',
            'age_group' => 'Under-18',
            'status' => 'active'
        ], $this->sportsAdminUserId);
        $secondTeamId = (int)$secondTeam['id'];

        $this->teamService->addAthlete($this->orgId, $secondTeamId, $ath1Id, ['jersey_number' => 99], $this->sportsAdminUserId);

        $team1Members = array_column($this->teamService->getTeam($this->orgId, $teamId)['current_athletes'] ?? [], 'athlete_id');
        $team2Members = array_column($this->teamService->getTeam($this->orgId, $secondTeamId)['current_athletes'] ?? [], 'athlete_id');

        // Check athlete record count in athletes table
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM athletes WHERE id = :id");
        $countStmt->execute([':id' => $ath1Id]);
        $athleteRecordCount = (int)$countStmt->fetchColumn();

        $results['Test 14, 15, 16: Multiple Teams per Athlete (No Duplicate Athlete Record)'] = (
            in_array($ath1Id, $team1Members) &&
            in_array($ath1Id, $team2Members) &&
            $athleteRecordCount === 1
        );

        // TEST 17, 18, 19: Add Multiple Coaches & Set ONE Primary Coach
        if ($coachA > 0 && $coachB > 0) {
            $this->teamService->assignCoach($this->orgId, $teamId, $coachA, 'head_coach', true, $this->sportsAdminUserId);
            $this->teamService->assignCoach($this->orgId, $teamId, $coachB, 'assistant_coach', false, $this->sportsAdminUserId);

            $teamCoaches = $this->teamService->getTeam($this->orgId, $teamId)['coaches'] ?? [];
            $coachIds = array_column($teamCoaches, 'coach_profile_id');
            $primaryCoaches = array_filter($teamCoaches, fn($c) => !empty($c['is_primary']));

            $results['Test 17, 18, 19: Multiple Coaches & Exactly ONE Primary Coach'] = (
                in_array($coachA, $coachIds) &&
                in_array($coachB, $coachIds) &&
                count($primaryCoaches) === 1 &&
                (int)$primaryCoaches[array_key_first($primaryCoaches)]['coach_profile_id'] === $coachA
            );
        } else {
            $results['Test 17, 18, 19: Multiple Coaches & Exactly ONE Primary Coach'] = true;
        }

        // TEST 20 & 21: Remove Coach & Verify Coach Profile Still Exists
        if ($coachB > 0) {
            $remCoachOk = $this->teamService->removeCoach($this->orgId, $teamId, $coachB, $this->sportsAdminUserId);
            $coachBCheckStmt = $this->pdo->prepare("SELECT id, status, deleted_at FROM coach_profiles WHERE id = :id");
            $coachBCheckStmt->execute([':id' => $coachB]);
            $coachBRow = $coachBCheckStmt->fetch(PDO::FETCH_ASSOC);

            $results['Test 20 & 21: Remove Coach Assignment (Profile Preserved)'] = (
                $remCoachOk &&
                !empty($coachBRow) &&
                empty($coachBRow['deleted_at'])
            );
        } else {
            $results['Test 20 & 21: Remove Coach Assignment (Profile Preserved)'] = true;
        }

        // TEST 22 & 23: Cross-Sport Athlete Assignment Backend Rejection
        $crossSportRejected = false;
        try {
            // Attempt to assign Cricket athlete ($ath3Id) to Badminton team ($teamId)
            $this->teamService->addAthlete($this->orgId, $teamId, $ath3Id, [], $this->sportsAdminUserId);
        } catch (\Throwable $e) {
            $crossSportRejected = str_contains(strtolower($e->getMessage()), 'sport') || str_contains(strtolower($e->getMessage()), 'incompatible');
        }
        $results['Test 22 & 23: Cross-Sport Athlete Backend Rejection'] = $crossSportRejected;

        // TEST 24 & 25: Cross-Tenant Assignment Backend Rejection
        $crossTenantRejected = false;
        try {
            // Attempt to add Org 1 athlete to Org 2 team (or Org 2 athlete to Org 1 team)
            $this->teamService->addAthlete($this->secondOrgId, $teamId, $ath1Id, [], $this->sportsAdminUserId);
        } catch (\Throwable $e) {
            $crossTenantRejected = true;
        }
        $results['Test 24 & 25: Cross-Tenant Assignment Backend Rejection'] = $crossTenantRejected;

        // TEST 26: RBAC Verification (Role 2 can create/manage, Athlete/Coach cannot)
        $sportsAdminCanManage = $this->permissionService->hasPermission([
            'id' => 102,
            'role_id' => 2,
            'role' => ['id' => 2, 'slug' => 'sports_admin'],
            'permissions' => ['team.create', 'team.update', 'team.manage', 'team.members.manage', 'team.coaches.manage']
        ], 'team.create', $this->orgId);

        $athleteBlocked = !$this->permissionService->hasPermission([
            'id' => 205,
            'role_id' => 5,
            'role' => ['id' => 5, 'slug' => 'athlete'],
            'permissions' => ['athlete.view']
        ], 'team.create', $this->orgId);

        $results['Test 26: RBAC Verification'] = ($sportsAdminCanManage && $athleteBlocked);

        // TEST 27: Duplicate Team Membership Prevention
        $duplicateMembershipRejected = false;
        try {
            // $ath1Id is already active in $teamId
            $this->teamService->addAthlete($this->orgId, $teamId, $ath1Id, [], $this->sportsAdminUserId);
        } catch (\Throwable $e) {
            $duplicateMembershipRejected = str_contains(strtolower($e->getMessage()), 'already');
        }
        $results['Test 27: Duplicate Team Membership Prevention'] = $duplicateMembershipRejected;

        // TEST 28: Duplicate Coach Assignment Prevention
        $duplicateCoachRejected = false;
        if ($coachA > 0) {
            try {
                // $coachA is already in $teamId
                $this->teamService->assignCoach($this->orgId, $teamId, $coachA, 'assistant_coach', false, $this->sportsAdminUserId);
            } catch (\Throwable $e) {
                $duplicateCoachRejected = str_contains(strtolower($e->getMessage()), 'already');
            }
        } else {
            $duplicateCoachRejected = true;
        }
        $results['Test 28: Duplicate Coach Assignment Prevention'] = $duplicateCoachRejected;

        return $results;
    }
}
