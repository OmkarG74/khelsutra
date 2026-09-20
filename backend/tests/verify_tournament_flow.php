<?php
require_once __DIR__ . '/../bootstrap/app.php';

$tournService = new \App\Services\Tournament\TournamentService();

echo "Testing listTournaments(1):\n";
$res = $tournService->listTournaments(1);
echo "Total tournaments found: {$res['total']}\n";
foreach ($res['data'] as $t) {
    echo "- Tournament #{$t['id']}: {$t['name']} ({$t['tournament_reference']}) - {$t['sport_name']} - Teams: {$t['enrolled_teams_count']} - Fixtures: {$t['fixtures_count']}\n";
}

echo "\nTesting createTournament:\n";
$newTourn = $tournService->createTournament(1, [
    'name' => 'Apex Champions League 2026',
    'sport_id' => 1,
    'tournament_level_id' => 3,
    'tournament_format_id' => 2,
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+14 days')),
    'venue_id' => 1,
    'team_ids' => [1, 2],
    'status' => 'ongoing'
], 5);

echo "Created Tournament ID: {$newTourn['id']}, Ref: {$newTourn['tournament_reference']}\n";

$details = $tournService->getTournament(1, $newTourn['id']);
echo "Retrieved Tournament: {$details['name']}, Enrolled Teams: " . count($details['participating_teams']) . "\n";

echo "\nTesting createFixture:\n";
$newFix = $tournService->createFixture(1, $newTourn['id'], [
    'round_name' => 'Group Match 1',
    'home_team_id' => 1,
    'away_team_id' => 2,
    'venue_id' => 1,
    'scheduled_date' => date('Y-m-d'),
    'scheduled_start_time' => '17:00:00',
    'scheduled_end_time' => '19:00:00'
], 5);
echo "Created Fixture ID: {$newFix['id']}, Ref: {$newFix['fixture_reference']}\n";

echo "\nTesting updateMatchResult:\n";
$updatedScore = $tournService->updateMatchResult(1, $newFix['id'], [
    'home_score' => 3,
    'away_score' => 1
], 5);
echo "Score record result: " . ($updatedScore ? "SUCCESS" : "FAILED") . "\n";

$detailsAfter = $tournService->getTournament(1, $newTourn['id']);
echo "Standings calculated count: " . count($detailsAfter['standings']) . "\n";
foreach ($detailsAfter['standings'] as $s) {
    echo "- Team {$s['team_name']}: P={$s['played']}, W={$s['won']}, D={$s['drawn']}, L={$s['lost']}, PTS={$s['points']}, GD={$s['difference']}\n";
}
