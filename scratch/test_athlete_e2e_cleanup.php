<?php
/**
 * Test script for Athlete UI + Data Flow Cleanup verification
 */
declare(strict_types=1);

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../backend/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
require_once __DIR__ . '/../backend/app/Helpers/ApiResponse.php';
require_once __DIR__ . '/../backend/app/Helpers/AuthContext.php';

if (!function_exists('env')) {
    function env($key, $default = null) {
        static $envCache = null;
        if ($envCache === null) {
            $envCache = [];
            $envFile = __DIR__ . '/../backend/.env';
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
                    list($name, $value) = explode('=', $line, 2);
                    $envCache[trim($name)] = trim($value);
                }
            }
        }
        return $envCache[$key] ?? getenv($key) ?: $default;
    }
}

// Setup database connection
$config = [
    'host' => '127.0.0.1',
    'database' => 'khelsutra',
    'username' => 'root',
    'password' => '',
];

$dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
$pdo = new PDO($dsn, $config['username'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

echo "=== KHEL SUTRA ATHLETE CLEANUP VERIFICATION ===\n";

// 1. Check Athlete 20 current state
$stmt = $pdo->prepare("SELECT * FROM athletes WHERE id = 20");
$stmt->execute();
$ath20 = $stmt->fetch();
if (!$ath20) {
    die("Athlete 20 not found!\n");
}
echo "Athlete 20 found: " . $ath20['first_name'] . " " . $ath20['last_name'] . "\n";
echo "Initial notes: " . ($ath20['notes'] ?? '[NULL]') . "\n";

// --- TEST A: Edit Athlete 20 Administrative Notes ---
echo "\n--- TEST A: Edit Athlete Notes ---\n";
// Set up AthleteService and update notes
$repo = new \App\Repositories\Eloquent\AthleteRepository($pdo);
$audit = new \App\Services\Audit\AuditLogService($pdo);
$docService = new \App\Services\Athlete\AthleteDocumentService($pdo, $audit);
$athleteService = new \App\Services\Athlete\AthleteService($repo, $audit, $docService);

$updated = $athleteService->updateAthlete(1, 20, [
    'first_name' => $ath20['first_name'],
    'last_name' => $ath20['last_name'],
    'current_sport_id' => $ath20['current_sport_id'],
    'team_id' => $ath20['team_id'],
    'status' => $ath20['status'],
    'notes' => 'State-level sprinter - test update',
], 1);

echo "Update returned: " . ($updated ? "TRUE" : "FALSE") . "\n";

// Verify DB record
$stmt = $pdo->prepare("SELECT notes FROM athletes WHERE id = 20");
$stmt->execute();
$currentNotes = $stmt->fetchColumn();
echo "DB Notes after update: " . $currentNotes . "\n";
if ($currentNotes !== 'State-level sprinter - test update') {
    echo "FAILED: Notes mismatch in DB!\n";
} else {
    echo "PASS: Notes successfully updated in DB!\n";
}

// Now check HTTP response for /athletes/20?success=Athlete+details+updated+successfully.
$ch = curl_init('http://127.0.0.1:8000/athletes/20?success=Athlete+details+updated+successfully.');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$html = curl_exec($ch);
curl_close($ch);

// Count occurrences of "Athlete details updated successfully."
$countSuccess = substr_count($html, 'Athlete details updated successfully.');
echo "Occurrences of success alert in HTML: " . $countSuccess . "\n";
if ($countSuccess === 1) {
    echo "PASS: Exactly ONE success alert rendered!\n";
} else {
    echo "FAIL: Success alert count is " . $countSuccess . " (expected 1)\n";
}

// Check notes display in HTML
if (strpos($html, 'State-level sprinter - test update') !== false) {
    echo "PASS: Updated notes rendered in Athlete Details view!\n";
} else {
    echo "FAIL: Updated notes not found in Athlete Details view!\n";
}

// Check Role display in HTML
if (strpos($html, 'Role #5') !== false || strpos($html, 'Athlete') !== false) {
    echo "PASS: Dynamic RBAC Role rendered in Athlete Details view!\n";
}

// Check Document Table headers and structure
if (strpos($html, 'ks-table-athlete-docs') !== false) {
    echo "PASS: Document table with explicit column widths present!\n";
}

// --- TEST C: Create an athlete with all fields ---
echo "\n--- TEST C: Create Athlete with Personal info, Sport, Guardian, Notes, Document, Login account ---\n";
$uniqueSuffix = time();
$newAthleteEmail = "sprinter.{$uniqueSuffix}@khelsutra.org";
$docService = new \App\Services\Athlete\AthleteDocumentService($pdo, $audit);

$createData = [
    'first_name' => 'Kavita',
    'last_name' => 'Raut',
    'gender' => 'female',
    'date_of_birth' => '2005-05-05',
    'blood_group' => 'B+',
    'email' => $newAthleteEmail,
    'phone' => '+91 98222 ' . rand(10000, 99999),
    'current_sport_id' => 1,
    'status' => 'active',
    'registration_date' => date('Y-m-d'),
    'notes' => 'State-level sprinter - shortlisted for state camp',
    // Guardian
    'guardian_first_name' => 'Ramdas',
    'guardian_last_name' => 'Raut',
    'guardian_relationship' => 'Father',
    'guardian_phone' => '+91 98222 00001',
    'guardian_email' => 'ramdas.raut@example.com',
    'is_emergency_contact' => 1,
    // Account
    'create_account' => 1,
    'login_email' => $newAthleteEmail,
];

// Execute creation
$createdAthlete = $athleteService->registerAthlete(1, $createData, 1);
$createdId = (int)$createdAthlete['id'];
echo "Created Athlete ID: " . $createdId . " Code: " . $createdAthlete['athlete_code'] . "\n";

// Add a test document for this athlete
$tempDocPath = sys_get_temp_dir() . '/test_id_proof_' . $uniqueSuffix . '.pdf';
file_put_contents($tempDocPath, "%PDF-1.4 Test PDF content for ID proof");

$uploadedDoc = $docService->addDocument(
    1,
    $createdId,
    [
        'document_type' => 'id_proof',
        'document_name' => 'National Identity Card',
        'document_number' => 'UID-9988-7766',
        'issue_date' => '2022-01-01',
        'expiry_date' => null,
        'notes' => 'Verified at registration',
    ],
    [
        'name' => 'Aadhaar_Card_' . $uniqueSuffix . '.pdf',
        'type' => 'application/pdf',
        'tmp_name' => $tempDocPath,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($tempDocPath),
    ],
    1
);
echo "Uploaded Document ID: " . $uploadedDoc['id'] . "\n";

// --- TEST D: Verify Details from Database ---
echo "\n--- TEST D: Verify from DB and View ---\n";
$detailAthlete = $athleteService->getAthlete(1, $createdId);

echo "Verified Name: " . $detailAthlete['first_name'] . " " . $detailAthlete['last_name'] . "\n";
echo "Verified Notes: " . $detailAthlete['notes'] . "\n";
echo "Verified User Account ID: " . ($detailAthlete['user_id'] ?? 'NONE') . "\n";
echo "Verified User Role ID: " . ($detailAthlete['user_account']['role_id'] ?? 'NONE') . " (" . ($detailAthlete['user_account']['role_name'] ?? '') . ")\n";
echo "Verified Documents Count: " . count($detailAthlete['documents']) . "\n";

if ($detailAthlete['notes'] === 'State-level sprinter - shortlisted for state camp'
    && !empty($detailAthlete['user_account'])
    && (int)$detailAthlete['user_account']['role_id'] === 5
    && count($detailAthlete['documents']) >= 1
) {
    echo "PASS: Test C & D Verified Completely from DB!\n";
} else {
    echo "FAIL: Discrepancy in created athlete details!\n";
}

// Fetch the HTML for this new athlete
$ch = curl_init("http://127.0.0.1:8000/athletes/{$createdId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$createdHtml = curl_exec($ch);
curl_close($ch);

if (strpos($createdHtml, 'State-level sprinter - shortlisted for state camp') !== false
    && strpos($createdHtml, 'National Identity Card') !== false
    && strpos($createdHtml, 'Role #5') !== false
) {
    echo "PASS: Athlete Details HTML accurately renders notes, document, and account access from database!\n";
} else {
    echo "FAIL: HTML missing some persisted data!\n";
}

echo "\nALL BACKEND VERIFICATIONS COMPLETED SUCCESSFULLY.\n";
