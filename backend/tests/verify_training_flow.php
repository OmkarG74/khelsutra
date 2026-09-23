<?php
require_once __DIR__ . '/../bootstrap/app.php';

$trainService = new \App\Services\Training\TrainingService();

echo "Testing listSessions(1):\n";
$res = $trainService->listSessions(1);
echo "Total sessions found: {$res['total']}\n";
foreach ($res['data'] as $s) {
    echo "- Session #{$s['id']}: {$s['title']} ({$s['training_reference']}) - Team: {$s['team_name']} - Coach: {$s['coach_name']} - Date: {$s['training_date']}\n";
}

echo "\nTesting createSession:\n";
$newSess = $trainService->createSession(1, [
    'title' => 'Speed & Agility Assessment Drills',
    'training_type' => 'Physical Conditioning',
    'team_id' => 1,
    'coach_id' => 1,
    'venue_id' => 1,
    'training_date' => date('Y-m-d'),
    'start_time' => '16:00:00',
    'end_time' => '18:00:00',
    'status' => 'scheduled'
], 5);

echo "Created Session ID: {$newSess['id']}, Ref: {$newSess['training_reference']}\n";

$details = $trainService->getSession(1, $newSess['id']);
echo "Retrieved Session: {$details['title']}, Attendance Athletes: " . count($details['roster_attendance']) . "\n";

echo "Testing recordAttendance:\n";
$attendancePayload = [];
foreach ($details['roster_attendance'] as $ath) {
    $attendancePayload[$ath['athlete_id']] = 'present';
}
$recorded = $trainService->recordAttendance(1, $newSess['id'], $attendancePayload, 5);
echo "Attendance record result: " . ($recorded ? "SUCCESS" : "FAILED") . "\n";
