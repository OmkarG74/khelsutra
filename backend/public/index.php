<?php

// Allow PHP built-in server to serve existing static assets directly
if (php_sapi_name() === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $staticFile = realpath(__DIR__ . $requestPath);

    if (
        $staticFile !== false &&
        str_starts_with($staticFile, realpath(__DIR__)) &&
        is_file($staticFile)
    ) {
        return false;
    }
}

define('LARAVEL_START', microtime(true));

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/app.php';

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
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

require_once __DIR__ . '/../app/Helpers/ApiResponse.php';
require_once __DIR__ . '/../app/Helpers/AuthContext.php';

// Helper env function
if (!function_exists('env')) {
    function env($key, $default = null) {
        static $envCache = null;
        if ($envCache === null) {
            $envCache = [];
            $envFile = __DIR__ . '/../.env';
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

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Handle CORS Pre-flight
if ($method === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Organization-ID');
    http_response_code(200);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Web Auth Action Handlers
if ($uri === '/login' && $method === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $authService = new \App\Services\Auth\AuthService();
    $auth = $authService->login($email, $password);
    if ($auth) {
        $_SESSION['auth'] = $auth;
        $_SESSION['role_slug'] = $auth['role']['slug'] ?? 'sports_admin';
        header('Location: /dashboard');
        exit;
    } else {
        header('Location: /login?error=' . urlencode('Invalid email or password.') . '&email=' . urlencode($email));
        exit;
    }
}

if ($uri === '/logout') {
    if (isset($_SESSION['auth']['user']['id'])) {
        (new \App\Services\Auth\AuthService())->logout((int)$_SESSION['auth']['user']['id'], (int)($_SESSION['auth']['organization']['id'] ?? 1));
    }
    unset($_SESSION['auth'], $_SESSION['role_slug']);
    session_destroy();
    header('Location: /login');
    exit;
}

// 2. Web Form Actions Dispatcher (POST Submissions)
require __DIR__ . '/../routes/web_actions.php';

// Athlete Excel Export Handler (Real .xlsx, filtered, unpaginated, organization-scoped)
if ($uri === '/athletes/export' && $method === 'GET') {
    $orgId = current_organization_id();
    $currentUser = $_SESSION['auth']['user'] ?? [
        'id' => 102,
        'email' => 'sportsadmin@khelsutra.local',
        'role_id' => 2,
    ];
    $currentRoleSlug = $_SESSION['auth']['role']['slug'] ?? ($_SESSION['role_slug'] ?? 'sports_admin');
    $currentRoleId = (int)($_SESSION['auth']['role']['id'] ?? ($currentUser['role_id'] ?? 2));
    $userId = current_user_id() ?? (int)($currentUser['id'] ?? 102);

    if ($currentRoleSlug === 'athlete' || $currentRoleId === 5 || $currentRoleSlug === 'inventory_manager' || $currentRoleId === 7) {
        http_response_code(403);
        echo "403 Forbidden: You do not have permission to export athletes.";
        exit;
    }

    $permissionService = new \App\Services\Rbac\PermissionService();
    $userPayload = array_merge(
        $_SESSION['auth'] ?? [],
        $currentUser,
        [
            'id' => $userId,
            'role_id' => $currentRoleId,
            'role' => $_SESSION['auth']['role'] ?? ['id' => $currentRoleId, 'slug' => $currentRoleSlug],
            'permissions' => $_SESSION['auth']['permissions'] ?? ($currentUser['permissions'] ?? []),
        ]
    );

    if (!$permissionService->hasPermission($userPayload, 'athlete.view', $orgId)) {
        http_response_code(403);
        echo "403 Forbidden: Insufficient permissions to export athletes.";
        exit;
    }

    $coachId = null;
    if ($currentRoleSlug === 'coach' || $currentRoleId === 4) {
        $coachId = (int)($currentUser['coach_id'] ?? 0);
        $pdo = \App\Services\BaseService::getDatabaseConnection();
        if (!$coachId && $userId && $pdo) {
            $cStmt = $pdo->prepare("
                SELECT cp.id
                FROM coach_profiles cp
                JOIN employees e ON cp.employee_id = e.id
                WHERE e.user_id = :uid AND cp.organization_id = :oid AND cp.deleted_at IS NULL
                LIMIT 1
            ");
            $cStmt->execute([':uid' => $userId, ':oid' => $orgId]);
            $coachId = (int)($cStmt->fetchColumn() ?: 0);
        }
    }

    $search = isset($_GET['search']) ? trim((string)$_GET['search']) : null;
    $sportId = !empty($_GET['sport_id']) ? (int)$_GET['sport_id'] : null;
    $status = !empty($_GET['status']) ? trim((string)$_GET['status']) : null;

    $athleteService = new \App\Services\Athlete\AthleteService();
    $export = $athleteService->exportAthletesXlsx(
        $orgId,
        $search !== '' ? $search : null,
        $sportId,
        $status !== '' ? $status : null,
        $coachId
    );

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $export['filename'] . '"');
    header('Content-Length: ' . strlen($export['binary']));
    header('Cache-Control: max-age=0');
    echo $export['binary'];
    exit;
}

// Coach Excel Export Handler (Real .xlsx, filtered, unpaginated, organization-scoped)
if ($uri === '/coaches/export' && $method === 'GET') {
    $orgId = current_organization_id();
    $currentUser = $_SESSION['auth']['user'] ?? [
        'id' => 102,
        'email' => 'sportsadmin@khelsutra.local',
        'role_id' => 2,
    ];
    $currentRoleSlug = $_SESSION['auth']['role']['slug'] ?? ($_SESSION['role_slug'] ?? 'sports_admin');
    $currentRoleId = (int)($_SESSION['auth']['role']['id'] ?? ($currentUser['role_id'] ?? 2));
    $userId = current_user_id() ?? (int)($currentUser['id'] ?? 102);

    if (
        $currentRoleSlug === 'athlete' || $currentRoleId === 5 ||
        $currentRoleSlug === 'inventory_manager' || $currentRoleId === 7 ||
        $currentRoleSlug === 'venue_manager' || $currentRoleId === 6
    ) {
        http_response_code(403);
        echo "403 Forbidden: You do not have permission to export coaches.";
        exit;
    }

    $permissionService = new \App\Services\Rbac\PermissionService();
    $userPayload = array_merge(
        $_SESSION['auth'] ?? [],
        $currentUser,
        [
            'id' => $userId,
            'role_id' => $currentRoleId,
            'role' => $_SESSION['auth']['role'] ?? ['id' => $currentRoleId, 'slug' => $currentRoleSlug],
            'permissions' => $_SESSION['auth']['permissions'] ?? ($currentUser['permissions'] ?? []),
        ]
    );

    if (
        !$permissionService->hasPermission($userPayload, 'coach.view', $orgId) &&
        !$permissionService->hasPermission($userPayload, 'coach.manage', $orgId)
    ) {
        http_response_code(403);
        echo "403 Forbidden: Insufficient permissions to export coaches.";
        exit;
    }

    $search = isset($_GET['search']) ? trim((string)$_GET['search']) : null;
    $specialization = isset($_GET['specialization']) ? trim((string)$_GET['specialization']) : null;
    $status = isset($_GET['status']) ? trim((string)$_GET['status']) : null;

    $coachService = new \App\Services\Coach\CoachService();
    $export = $coachService->exportCoachesXlsx(
        $orgId,
        $search !== '' ? $search : null,
        $specialization !== '' ? $specialization : null,
        $status !== '' ? $status : null
    );

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $export['filename'] . '"');
    header('Content-Length: ' . strlen($export['binary']));
    header('Cache-Control: max-age=0');
    echo $export['binary'];
    exit;
}

// Secure Document View / Download Handler
if (preg_match('#^/athletes/(\d+)/documents/(\d+)/(view|download)$#', $uri, $m)) {
    $athId = (int)$m[1];
    $docId = (int)$m[2];
    $action = $m[3]; // 'view' or 'download'
    $orgId = current_organization_id();

    $currentUser = $_SESSION['auth']['user'] ?? null;
    $currentRoleSlug = $_SESSION['auth']['role']['slug'] ?? ($_SESSION['role_slug'] ?? 'sports_admin');
    $currentRoleId = (int)($_SESSION['auth']['role']['id'] ?? 2);

    if ($currentRoleSlug === 'inventory_manager' || $currentRoleId === 7) {
        http_response_code(403);
        echo "Access denied.";
        exit;
    }
    if ($currentRoleSlug === 'athlete' || $currentRoleId === 5) {
        $myAthId = (int)($currentUser['athlete_id'] ?? 0);
        if ($myAthId !== $athId) {
            http_response_code(403);
            echo "Access denied: Athletes can only access their own documents.";
            exit;
        }
    } elseif ($currentRoleSlug === 'coach' || $currentRoleId === 4) {
        $coachId = (int)($currentUser['coach_id'] ?? 0);
        $pdo = \App\Services\BaseService::getDatabaseConnection();
        if (!$coachId && !empty($currentUser['id']) && $pdo) {
            $cStmt = $pdo->prepare("
                SELECT cp.id FROM coach_profiles cp JOIN employees e ON cp.employee_id = e.id
                WHERE e.user_id = :uid AND cp.organization_id = :oid AND cp.deleted_at IS NULL LIMIT 1
            ");
            $cStmt->execute([':uid' => (int)$currentUser['id'], ':oid' => $orgId]);
            $coachId = (int)($cStmt->fetchColumn() ?: 0);
        }
        $authorizedCoach = false;
        if ($coachId && $pdo) {
            $tcStmt = $pdo->prepare("
                SELECT 1 FROM team_coaches tc JOIN team_members tm ON tc.team_id = tm.team_id AND tm.is_current = 1
                WHERE tc.coach_id = :cid AND tm.athlete_id = :aid AND tc.organization_id = :oid LIMIT 1
            ");
            $tcStmt->execute([':cid' => $coachId, ':aid' => $athId, ':oid' => $orgId]);
            $authorizedCoach = (bool)$tcStmt->fetchColumn();
        }
        if (!$authorizedCoach) {
            http_response_code(403);
            echo "Access denied: Athlete is not in your assigned squads.";
            exit;
        }
    }

    $docService = new \App\Services\Athlete\AthleteDocumentService();
    $doc = $docService->getDocument($orgId, $athId, $docId);
    if (!$doc) {
        http_response_code(404);
        echo "Document not found or access denied.";
        exit;
    }
    $fullPath = $docService->getFullFilePath($doc['file_path']);
    if (!$fullPath || !file_exists($fullPath)) {
        http_response_code(404);
        echo "Document file missing on server.";
        exit;
    }
    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($fullPath) ?: 'application/octet-stream';
    $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
    $fileName = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $doc['document_name']) . '.' . $ext;
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($fullPath));
    $disposition = ($action === 'download') ? 'attachment' : 'inline';
    header('Content-Disposition: ' . $disposition . '; filename="' . $fileName . '"');
    readfile($fullPath);
    exit;
}

// Team Candidates Search Handlers (Organization-scoped & Sport-scoped JSON endpoints)
if ($uri === '/teams/candidates/athletes' && $method === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    $orgId = current_organization_id();
    $currentUser = $_SESSION['auth']['user'] ?? [
        'id' => 102,
        'email' => 'sportsadmin@khelsutra.local',
        'role_id' => 2,
    ];
    $currentRoleSlug = $_SESSION['auth']['role']['slug'] ?? ($_SESSION['role_slug'] ?? 'sports_admin');
    $currentRoleId = (int)($_SESSION['auth']['role']['id'] ?? ($currentUser['role_id'] ?? 2));
    $userId = current_user_id() ?? (int)($currentUser['id'] ?? 102);

    $isAthlete = ($currentRoleSlug === 'athlete') || ($currentRoleId === 5) || !empty($currentUser['athlete_id']);
    $isCoach = ($currentRoleSlug === 'coach') || ($currentRoleId === 4) || !empty($currentUser['coach_id']);

    $permissionService = new \App\Services\Rbac\PermissionService();
    $userPayload = array_merge(
        $_SESSION['auth'] ?? [],
        $currentUser,
        [
            'id' => $userId,
            'role_id' => $currentRoleId,
            'role' => $_SESSION['auth']['role'] ?? ['id' => $currentRoleId, 'slug' => $currentRoleSlug],
            'permissions' => $_SESSION['auth']['permissions'] ?? ($currentUser['permissions'] ?? []),
        ]
    );

    $canSearch = !$isAthlete && !$isCoach && (
        $permissionService->hasPermission($userPayload, 'team.create', $orgId) ||
        $permissionService->hasPermission($userPayload, 'team.update', $orgId) ||
        $permissionService->hasPermission($userPayload, 'team.manage', $orgId) ||
        $permissionService->hasPermission($userPayload, 'team.members.manage', $orgId)
    );

    if (!$canSearch) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => '403 Forbidden: You do not have permission to search team athletes.']);
        exit;
    }

    $sportId = (int)($_GET['sport_id'] ?? 0);
    $search = isset($_GET['search']) ? trim((string)$_GET['search']) : null;
    $gender = isset($_GET['gender']) ? trim((string)$_GET['gender']) : null;
    $excludeTeamId = !empty($_GET['team_id']) ? (int)$_GET['team_id'] : null;

    $teamService = new \App\Services\Team\TeamService();
    $athletes = $teamService->searchEligibleAthletes($orgId, $sportId, $search, $gender, $excludeTeamId, 30);
    echo json_encode(['success' => true, 'data' => $athletes]);
    exit;
}

if ($uri === '/teams/candidates/coaches' && $method === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    $orgId = current_organization_id();
    $currentUser = $_SESSION['auth']['user'] ?? [
        'id' => 102,
        'email' => 'sportsadmin@khelsutra.local',
        'role_id' => 2,
    ];
    $currentRoleSlug = $_SESSION['auth']['role']['slug'] ?? ($_SESSION['role_slug'] ?? 'sports_admin');
    $currentRoleId = (int)($_SESSION['auth']['role']['id'] ?? ($currentUser['role_id'] ?? 2));
    $userId = current_user_id() ?? (int)($currentUser['id'] ?? 102);

    $isAthlete = ($currentRoleSlug === 'athlete') || ($currentRoleId === 5) || !empty($currentUser['athlete_id']);
    $isCoach = ($currentRoleSlug === 'coach') || ($currentRoleId === 4) || !empty($currentUser['coach_id']);

    $permissionService = new \App\Services\Rbac\PermissionService();
    $userPayload = array_merge(
        $_SESSION['auth'] ?? [],
        $currentUser,
        [
            'id' => $userId,
            'role_id' => $currentRoleId,
            'role' => $_SESSION['auth']['role'] ?? ['id' => $currentRoleId, 'slug' => $currentRoleSlug],
            'permissions' => $_SESSION['auth']['permissions'] ?? ($currentUser['permissions'] ?? []),
        ]
    );

    $canSearch = !$isAthlete && !$isCoach && (
        $permissionService->hasPermission($userPayload, 'team.create', $orgId) ||
        $permissionService->hasPermission($userPayload, 'team.update', $orgId) ||
        $permissionService->hasPermission($userPayload, 'team.manage', $orgId) ||
        $permissionService->hasPermission($userPayload, 'team.coaches.manage', $orgId)
    );

    if (!$canSearch) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => '403 Forbidden: You do not have permission to search team coaches.']);
        exit;
    }

    $search = isset($_GET['search']) ? trim((string)$_GET['search']) : null;
    $sportName = isset($_GET['sport_name']) ? trim((string)$_GET['sport_name']) : null;
    $excludeTeamId = !empty($_GET['team_id']) ? (int)$_GET['team_id'] : null;

    $teamService = new \App\Services\Team\TeamService();
    $coaches = $teamService->searchAvailableCoaches($orgId, $search, $sportName, $excludeTeamId, 30);
    echo json_encode(['success' => true, 'data' => $coaches]);
    exit;
}

// 3. API Route Handler
if (str_starts_with($uri, '/api/')) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Organization-ID');
    
    // Parse JSON or Form payload
    $rawInput = file_get_contents('php://input');
    $body = json_decode($rawInput, true) ?? [];
    $requestData = array_merge($_GET, $_POST, $body);
    $requestData['headers'] = array_change_key_case(getallheaders() ?: [], CASE_LOWER);

    try {
        $apiRouter = require __DIR__ . '/../routes/api.php';
        $response = $apiRouter($uri, $method, $requestData);
        \App\Helpers\ApiResponse::send($response);
    } catch (\Throwable $e) {
        $error = \App\Exceptions\Handler::render($e);
        \App\Helpers\ApiResponse::send($error);
    }
    exit;
}

// 3. Web RBAC Protection Guards
$render403 = function (string $message = 'You do not have the required permissions to access this area.') {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>403 Forbidden — KhelSutra Platform</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        <link rel="stylesheet" href="/assets/css/khelsutra-design-system.css">
        <style>
            body {
                font-family: 'Inter', sans-serif;
                background: var(--ks-page-bg, #F8FAFC);
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 24px;
                color: var(--ks-text, #1E293B);
            }
            .ks-error-box {
                max-width: 480px;
                text-align: center;
                background: #fff;
                padding: 40px 32px;
                border-radius: var(--ks-radius-modal, 16px);
                border: 1px solid var(--ks-border, #E2E8F0);
                box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08);
            }
        </style>
    </head>
    <body>
        <div class="ks-error-box">
            <div style="font-size: 56px; font-weight: 800; color: #DC2626; line-height: 1;">403</div>
            <h3 class="fw-bold mt-3 mb-2" style="color: #0B192C;">Access Forbidden</h3>
            <p class="text-muted small mb-4"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="d-flex justify-content-center gap-2">
                <a href="/dashboard" class="btn btn-primary px-4 py-2" style="border-radius: 8px; font-weight: 600; font-size: 13px;">Return to Dashboard</a>
                <a href="/logout" class="btn btn-outline-secondary px-3 py-2" style="border-radius: 8px; font-size: 13px;">Sign Out</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
};

if (str_starts_with($uri, '/super-admin/')) {
    $currentRoleSlug = $_SESSION['auth']['role']['slug'] ?? ($_SESSION['role_slug'] ?? 'sports_admin');
    $currentRoleId = (int)($_SESSION['auth']['role']['id'] ?? 2);
    if ($currentRoleId !== 1 && $currentRoleSlug !== 'super_admin') {
        $render403('You do not have the required permissions to access this platform administration area. Your current authenticated role is strictly isolated to your organisation context.');
    }
}

// Session-level web RBAC validation for other roles
if (isset($_SESSION['auth']['role'])) {
    $currentRoleSlug = $_SESSION['auth']['role']['slug'] ?? ($_SESSION['role_slug'] ?? 'sports_admin');
    $currentRoleId = (int)($_SESSION['auth']['role']['id'] ?? 2);

    if ($currentRoleSlug === 'athlete' || $currentRoleId === 5) {
        $blockedPatterns = [
            '#^/settings#', '#^/payroll#', '#^/hr#', '#^/hr-finance#', '#^/inventory#', '#^/equipment#', 
            '#^/vendors#', '#^/purchases#', '#^/finance#', '#^/users#', '#^/roles#', 
            '#^/permissions#', '#^/audit-logs#', '#^/venues#', '#^/operations#',
            '#^/coaches#', '#^/athletes/create#', '#^/athletes/\d+/edit#',
            '#^/teams/create#', '#^/teams/\d+/edit#', '#^/tournaments/create#', '#^/tournaments/\d+/edit#',
            '#^/training/create#', '#^/training/\d+/edit#', '#^/attendance/training#', '#^/attendance/matches#'
        ];
        foreach ($blockedPatterns as $p) {
            if (preg_match($p, $uri)) {
                $render403('Athletes are restricted from accessing this administrative or operational area.');
            }
        }
    } elseif ($currentRoleSlug === 'coach' || $currentRoleId === 4) {
        $blockedPatterns = [
            '#^/settings#', '#^/payroll#', '#^/hr#', '#^/hr-finance#', '#^/inventory#', '#^/equipment#', 
            '#^/vendors#', '#^/purchases#', '#^/finance#', '#^/users#', '#^/roles#', 
            '#^/permissions#', '#^/audit-logs#', '#^/venues/create#', '#^/venues/\d+/edit#',
            '#^/coaches/create#', '#^/coaches/\d+/edit#', '#^/athletes/create#', '#^/athletes/\d+/edit#',
            '#^/teams/create#', '#^/teams/\d+/edit#', '#^/tournaments/create#', '#^/tournaments/\d+/edit#'
        ];
        foreach ($blockedPatterns as $p) {
            if (preg_match($p, $uri)) {
                $render403('Coaches are restricted from accessing system-level, HR/payroll, or inventory configuration.');
            }
        }
    } elseif ($currentRoleSlug === 'hr_finance' || $currentRoleId === 3) {
        $blockedPatterns = [
            '#^/settings#', '#^/users#', '#^/roles#', '#^/permissions#',
            '#^/athletes/create#', '#^/athletes/\d+/edit#',
            '#^/coaches/create#', '#^/coaches/\d+/edit#',
            '#^/teams/create#', '#^/teams/\d+/edit#',
            '#^/tournaments/create#', '#^/tournaments/\d+/edit#',
            '#^/training/create#', '#^/training/\d+/edit#',
            '#^/venues/create#', '#^/venues/\d+/edit#',
            '#^/inventory/create#', '#^/equipment/create#', '#^/vendors/create#'
        ];
        foreach ($blockedPatterns as $p) {
            if (preg_match($p, $uri)) {
                $render403('HR & Finance role is not authorized to create or modify sports competitions or facilities.');
            }
        }
    } elseif ($currentRoleSlug === 'inventory_manager' || $currentRoleId === 7) {
        $blockedPatterns = [
            '#^/settings#', '#^/payroll#', '#^/hr#', '#^/hr-finance#', '#^/users#', '#^/roles#', 
            '#^/permissions#', '#^/audit-logs#',
            '#^/athletes#', '#^/coaches#', '#^/teams#', '#^/tournaments#',
            '#^/training#', '#^/attendance#', '#^/venues#', '#^/operations#'
        ];
        foreach ($blockedPatterns as $p) {
            if (preg_match($p, $uri)) {
                $render403('Inventory Managers are restricted to inventory, equipment, vendors, and purchase orders.');
            }
        }
    } elseif ($currentRoleSlug === 'venue_manager' || $currentRoleId === 6) {
        $blockedPatterns = [
            '#^/settings#', '#^/payroll#', '#^/hr#', '#^/hr-finance#', '#^/users#', '#^/roles#', 
            '#^/permissions#', '#^/audit-logs#',
            '#^/inventory#', '#^/equipment#', '#^/vendors#', '#^/purchases#',
            '#^/athletes/create#', '#^/athletes/\d+/edit#',
            '#^/coaches/create#', '#^/coaches/\d+/edit#',
            '#^/teams/create#', '#^/teams/\d+/edit#',
            '#^/training/create#', '#^/training/\d+/edit#'
        ];
        foreach ($blockedPatterns as $p) {
            if (preg_match($p, $uri)) {
                $render403('Venue & Tournament Managers are restricted from HR, payroll, and inventory administration.');
            }
        }
    }
}

// 2. Web UI Route Handler
$webRoutes = require __DIR__ . '/../routes/web.php';
$viewTarget = $webRoutes[$uri] ?? null;
$routeParams = [];

if (!$viewTarget) {
    foreach ($webRoutes as $pattern => $handler) {
        if (str_contains($pattern, '{')) {
            $regex = '#^' . preg_replace('#\{[a-zA-Z0-9_]+\}#', '([a-zA-Z0-9_-]+)', $pattern) . '$#';
            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches);
                $routeParams = $matches;
                $viewTarget = $handler;
                break;
            }
        }
    }
}
$viewTarget = $viewTarget ?? $webRoutes['/'] ?? null;

if ($viewTarget && is_callable($viewTarget)) {
    $result = $viewTarget(...$routeParams);
    if (!empty($result['view'])) {
        $viewPath = __DIR__ . '/../resources/views/' . $result['view'] . '.blade.php';
        if (file_exists($viewPath)) {
            $data = $result['data'] ?? [];
            extract($result['data'] ?? []);
            include $viewPath;
            exit;
        }
    }
}

// Fallback to Dashboard
$dashboardView = __DIR__ . '/../resources/views/dashboard/index.blade.php';
if (file_exists($dashboardView)) {
    include $dashboardView;
    exit;
}

echo "<h1>KhelSutra Sports Management Platform</h1><p>API is active at /api/v1/health</p>";
