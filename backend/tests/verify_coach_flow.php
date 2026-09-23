<?php
require_once __DIR__ . '/../bootstrap/app.php';

$coachService = new \App\Services\Coach\CoachService();

echo "Testing listCoaches(1):\n";
$res = $coachService->listCoaches(1);
echo "Total coaches found: {$res['total']}\n";
foreach ($res['data'] as $c) {
    echo "- Coach #{$c['coach_profile_id']}: {$c['first_name']} {$c['last_name']} ({$c['coach_code']}) - {$c['specialization']}\n";
}

echo "\nTesting createCoach:\n";
$newCoach = $coachService->createCoach(1, [
    'first_name' => 'Vikram',
    'last_name' => 'Rathore',
    'date_of_birth' => '1984-05-12',
    'gender' => 'male',
    'phone' => '+91 9988776655',
    'email' => 'vikram.rathore@khelsutra.local',
    'designation' => 'Senior Batting Coach',
    'specialization' => 'Batting Technique & Mental Conditioning',
    'experience_years' => 8.5,
    'status' => 'active'
], 5);

echo "Created Coach ID: {$newCoach['coach_profile_id']}, Code: {$newCoach['coach_code']}\n";

$details = $coachService->getCoach(1, $newCoach['coach_profile_id']);
echo "Retrieved Coach: {$details['first_name']} {$details['last_name']}, Specialization: {$details['specialization']}\n";

echo "Testing updateCoach:\n";
$updated = $coachService->updateCoach(1, $newCoach['coach_profile_id'], [
    'first_name' => 'Vikram',
    'last_name' => 'Rathore',
    'specialization' => 'Elite Batting Technique & Mindset',
    'experience_years' => 9.0,
    'status' => 'active'
], 5);
echo "Update result: " . ($updated ? "SUCCESS" : "FAILED") . "\n";

$updatedDetails = $coachService->getCoach(1, $newCoach['coach_profile_id']);
echo "Updated Specialization: {$updatedDetails['specialization']}, Experience: {$updatedDetails['experience_years']}\n";
