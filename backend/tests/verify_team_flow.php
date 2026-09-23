<?php
require_once __DIR__ . '/../bootstrap/app.php';

$teamService = new \App\Services\Team\TeamService();

echo "Testing listTeams(1):\n";
$res = $teamService->listTeams(1);
echo "Total teams found: {$res['total']}\n";
foreach ($res['data'] as $t) {
    echo "- Team #{$t['id']}: {$t['name']} ({$t['team_code']}) - {$t['sport_name']} - Coach: " . ($t['head_coach_name'] ?? 'None') . " - Athletes: {$t['athlete_count']}\n";
}

echo "\nTesting createTeam:\n";
$newTeam = $teamService->createTeam(1, [
    'name' => 'Apex Thunderbolts Basketball',
    'sport_id' => 1,
    'gender' => 'male',
    'age_group' => 'Under-19',
    'formation_or_level' => 'Junior Premier',
    'coach_id' => 1,
    'athlete_ids' => [1, 2],
    'status' => 'active'
], 5);

echo "Created Team ID: {$newTeam['id']}, Code: {$newTeam['team_code']}\n";

$details = $teamService->getTeam(1, $newTeam['id']);
echo "Retrieved Team: {$details['name']}, Athletes in Roster: " . count($details['current_athletes']) . "\n";

echo "Testing updateTeam:\n";
$updated = $teamService->updateTeam(1, $newTeam['id'], [
    'name' => 'Apex Thunderbolts Basketball Elite',
    'status' => 'active'
], 5);
echo "Update result: " . ($updated ? "SUCCESS" : "FAILED") . "\n";
