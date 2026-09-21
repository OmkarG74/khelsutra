<?php
require_once __DIR__ . '/../bootstrap/app.php';

$venueService = new \App\Services\Venue\VenueService();

echo "Testing listVenues(1):\n";
$res = $venueService->listVenues(1);
echo "Total venues found: {$res['total']}\n";
foreach ($res['data'] as $v) {
    echo "- Venue #{$v['id']}: {$v['name']} ({$v['venue_code']}) - Facilities: {$v['facility_count']}\n";
}

echo "\nTesting createVenue:\n";
$newVen = $venueService->createVenue(1, [
    'name' => 'Apex Aquatics & Tennis Hub',
    'venue_type' => 'Sports Complex',
    'city' => 'Pune',
    'state' => 'Maharashtra',
    'facility_name' => 'Olympic Size Swimming Pool',
    'facility_type' => 'Swimming Pool',
    'facility_capacity' => 40,
    'status' => 'active'
], 5);

echo "Created Venue ID: {$newVen['id']}, Code: {$newVen['venue_code']}\n";

$details = $venueService->getVenue(1, $newVen['id']);
echo "Retrieved Venue: {$details['name']}, Facilities: " . count($details['facilities']) . "\n";
$facId = $details['facilities'][0]['id'] ?? 1;

echo "\nTesting createBooking (Slot 10:00 - 12:00):\n";
$bkg1 = $venueService->createBooking(1, [
    'venue_id' => $newVen['id'],
    'facility_id' => $facId,
    'booking_date' => date('Y-m-d', strtotime('+1 day')),
    'start_time' => '10:00:00',
    'end_time' => '12:00:00',
    'purpose' => 'Squad Swimming Practice'
], 5);
echo "Booking 1 created successfully: {$bkg1['booking_reference']}\n";

echo "\nTesting createBooking Conflict Detection (Slot 11:00 - 13:00 overlapping):\n";
try {
    $venueService->createBooking(1, [
        'venue_id' => $newVen['id'],
        'facility_id' => $facId,
        'booking_date' => date('Y-m-d', strtotime('+1 day')),
        'start_time' => '11:00:00',
        'end_time' => '13:00:00',
        'purpose' => 'Conflicting Private Booking'
    ], 5);
    echo "ERROR: Conflict check FAILED to catch overlap!\n";
} catch (\Throwable $e) {
    echo "SUCCESS: Conflict caught properly! Error message: " . $e->getMessage() . "\n";
}
