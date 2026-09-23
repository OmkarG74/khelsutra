<?php
require_once __DIR__ . '/../bootstrap/app.php';
$pdo = \App\Services\BaseService::getDatabaseConnection();

echo "--- EQUIPMENT_ASSIGNMENTS ---\n";
$stmt = $pdo->query("DESCRIBE equipment_assignments");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['Field']} ({$row['Type']})\n";
}
