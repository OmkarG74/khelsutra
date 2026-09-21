<?php

/**
 * KhelSutra Web Form Action Dispatcher
 * Handles full-page web form submissions with CSRF, validation,
 * service execution, audit logging, and clean feedback redirects.
 */

if ($method !== 'POST') {
    return;
}

$currentUser = $_SESSION['auth']['user'] ?? [
    'id' => 5,
    'email' => 'sportsadmin@khelsutra.local',
    'first_name' => 'Rajesh',
    'last_name' => 'Sharma'
];
$orgId = (int)($_SESSION['auth']['organization']['id'] ?? 1);
$userId = (int)($currentUser['id'] ?? 5);

// ==========================================
// 1. ATHLETE ACTIONS
// ==========================================
if ($uri === '/athletes/create') {
    $validator = new \App\Http\Requests\Athletes\StoreAthleteRequest($_POST, $_FILES);
    $errors = $validator->validate();
    if (!empty($errors)) {
        $firstErr = reset($errors)[0];
        header('Location: /athletes/create?error=' . urlencode($firstErr));
        exit;
    }

    $athleteService = new \App\Services\Athlete\AthleteService();
    try {
        $created = $athleteService->registerAthlete($orgId, $_POST, $userId, $_FILES);
        $newId = $created['id'] ?? null;

        // Fetch sport and team names for success summary
        $sportName = 'General Sports';
        if (!empty($created['current_sport_id'])) {
            $db = \App\Services\BaseService::getDatabaseConnection();
            $sStmt = $db->prepare("SELECT name FROM sports WHERE id = :id");
            $sStmt->execute([':id' => $created['current_sport_id']]);
            $sportName = $sStmt->fetchColumn() ?: 'General Sports';
        }
        $teamName = 'No Team Assigned';
        if (!empty($_POST['team_id'])) {
            $db = \App\Services\BaseService::getDatabaseConnection();
            $tStmt = $db->prepare("SELECT name FROM teams WHERE id = :id");
            $tStmt->execute([':id' => (int)$_POST['team_id']]);
            $teamName = $tStmt->fetchColumn() ?: 'No Team Assigned';
        }

        $_SESSION['athlete_created_success'] = [
            'id' => $newId,
            'name' => trim(($created['first_name'] ?? '') . ' ' . ($created['last_name'] ?? '')),
            'athlete_code' => $created['athlete_code'] ?? '',
            'sport_name' => $sportName,
            'team_name' => $teamName,
            'has_account' => !empty($created['created_user']),
            'login_email' => $created['login_email'] ?? null,
            'login_username' => $created['login_username'] ?? null,
            'temp_password' => $created['temp_password'] ?? null,
            'account_status' => $created['account_status'] ?? 'Active',
            'uploaded_docs_count' => $created['uploaded_docs_count'] ?? 0,
        ];

        header('Location: /athletes/create?created=1&id=' . (int)$newId);
        exit;
    } catch (\Throwable $e) {
        header('Location: /athletes/create?error=' . urlencode('Unable to create athlete: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/athletes/(\d+)/documents/upload$#', $uri, $m)) {
    $athleteId = (int)$m[1];
    $athleteService = new \App\Services\Athlete\AthleteService();
    try {
        if (empty($_FILES['document_file']) || ($_FILES['document_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('Please select a valid document file to upload.');
        }
        $athleteService->getDocumentService()->addDocument($orgId, $athleteId, $_POST, $_FILES['document_file'], $userId);
        header('Location: /athletes/' . $athleteId . '?success=' . urlencode('Document uploaded successfully.'));
        exit;
    } catch (\Throwable $e) {
        header('Location: /athletes/' . $athleteId . '?error=' . urlencode('Failed to upload document: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/athletes/(\d+)/documents/(\d+)/delete$#', $uri, $m)) {
    $athleteId = (int)$m[1];
    $docId = (int)$m[2];
    $athleteService = new \App\Services\Athlete\AthleteService();
    try {
        $athleteService->getDocumentService()->deleteDocument($orgId, $athleteId, $docId, $userId);
        header('Location: /athletes/' . $athleteId . '?success=' . urlencode('Document removed successfully.'));
        exit;
    } catch (\Throwable $e) {
        header('Location: /athletes/' . $athleteId . '?error=' . urlencode('Failed to delete document: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/athletes/(\d+)/account/create$#', $uri, $m)) {
    $athleteId = (int)$m[1];
    $athleteService = new \App\Services\Athlete\AthleteService();
    try {
        $acc = $athleteService->createAthleteAccount($orgId, $athleteId, $_POST, $userId);
        $_SESSION['athlete_account_provisioned'] = $acc;
        header('Location: /athletes/' . $athleteId . '?success=' . urlencode('Login account provisioned successfully.'));
        exit;
    } catch (\Throwable $e) {
        header('Location: /athletes/' . $athleteId . '?error=' . urlencode('Unable to create account: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/athletes/(\d+)/account/reset-password$#', $uri, $m)) {
    $athleteId = (int)$m[1];
    $athleteService = new \App\Services\Athlete\AthleteService();
    try {
        $newPwd = $athleteService->resetAthletePassword($orgId, $athleteId, $_POST['password'] ?? null, $userId);
        $_SESSION['athlete_password_reset'] = [
            'athlete_id' => $athleteId,
            'temp_password' => $newPwd,
        ];
        header('Location: /athletes/' . $athleteId . '?success=' . urlencode('Password reset successfully.'));
        exit;
    } catch (\Throwable $e) {
        header('Location: /athletes/' . $athleteId . '?error=' . urlencode('Unable to reset password: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/athletes/(\d+)/edit$#', $uri, $m)) {
    $athleteId = (int)$m[1];
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $dob = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $sportId = (int)($_POST['current_sport_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');

    if (empty($firstName) || empty($lastName)) {
        header('Location: /athletes/' . $athleteId . '/edit?error=' . urlencode('First Name and Last Name are required.'));
        exit;
    }
    if (empty($dob)) {
        header('Location: /athletes/' . $athleteId . '/edit?error=' . urlencode('Date of Birth is required.'));
        exit;
    }
    if (empty($gender)) {
        header('Location: /athletes/' . $athleteId . '/edit?error=' . urlencode('Gender is required.'));
        exit;
    }
    if ($sportId <= 0) {
        header('Location: /athletes/' . $athleteId . '/edit?error=' . urlencode('Primary Sport is required.'));
        exit;
    }
    if (empty($status) || !in_array($status, ['active', 'inactive', 'injured', 'suspended'], true)) {
        header('Location: /athletes/' . $athleteId . '/edit?error=' . urlencode('Valid Athlete Status is required.'));
        exit;
    }

    $athleteService = new \App\Services\Athlete\AthleteService();
    try {
        $ok = $athleteService->updateAthlete($orgId, $athleteId, $_POST, $userId);
        if ($ok) {
            header('Location: /athletes/' . $athleteId . '?success=' . urlencode('Athlete details updated successfully.'));
        } else {
            header('Location: /athletes/' . $athleteId . '/edit?error=' . urlencode('Failed to update athlete record.'));
        }
        exit;
    } catch (\Throwable $e) {
        header('Location: /athletes/' . $athleteId . '/edit?error=' . urlencode('Unable to update athlete: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/athletes/(\d+)/delete$#', $uri, $m)) {
    $athleteId = (int)$m[1];
    $athleteService = new \App\Services\Athlete\AthleteService();
    try {
        $ok = $athleteService->deleteAthlete($orgId, $athleteId, $userId);
        header('Location: /athletes?success=' . urlencode('Athlete record removed successfully.'));
        exit;
    } catch (\Throwable $e) {
        header('Location: /athletes?error=' . urlencode('Unable to delete athlete: ' . $e->getMessage()));
        exit;
    }
}

// ==========================================
// 2. COACH ACTIONS
// ==========================================
if ($uri === '/coaches/create') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $dob = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $status = trim($_POST['status'] ?? 'active');

    if (empty($firstName) || empty($lastName)) {
        header('Location: /coaches/create?error=' . urlencode('First Name and Last Name are required.'));
        exit;
    }
    if (empty($dob)) {
        header('Location: /coaches/create?error=' . urlencode('Date of Birth is required.'));
        exit;
    }
    if (empty($gender)) {
        header('Location: /coaches/create?error=' . urlencode('Gender is required.'));
        exit;
    }
    if (empty($designation)) {
        header('Location: /coaches/create?error=' . urlencode('Designation is required.'));
        exit;
    }
    if (empty($specialization)) {
        header('Location: /coaches/create?error=' . urlencode('Specialization is required.'));
        exit;
    }
    if (!in_array($status, ['active', 'inactive'], true)) {
        header('Location: /coaches/create?error=' . urlencode('Valid Coach Status is required.'));
        exit;
    }
    $_POST['status'] = $status;

    $coachService = new \App\Services\Coach\CoachService();
    try {
        $created = $coachService->createCoach($orgId, $_POST, $userId);
        $coachProfileId = $created['coach_profile_id'] ?? null;
        if ($coachProfileId) {
            header('Location: /coaches/' . $coachProfileId . '?success=' . urlencode('Coach registered successfully.'));
        } else {
            header('Location: /coaches?success=' . urlencode('Coach registered successfully.'));
        }
        exit;
    } catch (\Throwable $e) {
        header('Location: /coaches/create?error=' . urlencode('Unable to create coach: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/coaches/(\d+)/edit$#', $uri, $m)) {
    $coachId = (int)$m[1];
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $dob = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $status = trim($_POST['status'] ?? '');

    if (empty($firstName) || empty($lastName)) {
        header('Location: /coaches/' . $coachId . '/edit?error=' . urlencode('First Name and Last Name are required.'));
        exit;
    }
    if (empty($dob)) {
        header('Location: /coaches/' . $coachId . '/edit?error=' . urlencode('Date of Birth is required.'));
        exit;
    }
    if (empty($gender)) {
        header('Location: /coaches/' . $coachId . '/edit?error=' . urlencode('Gender is required.'));
        exit;
    }
    if (empty($designation)) {
        header('Location: /coaches/' . $coachId . '/edit?error=' . urlencode('Designation is required.'));
        exit;
    }
    if (empty($specialization)) {
        header('Location: /coaches/' . $coachId . '/edit?error=' . urlencode('Specialization is required.'));
        exit;
    }
    if (empty($status) || !in_array($status, ['active', 'inactive'], true)) {
        header('Location: /coaches/' . $coachId . '/edit?error=' . urlencode('Coach Status is required.'));
        exit;
    }

    $coachService = new \App\Services\Coach\CoachService();
    try {
        $ok = $coachService->updateCoach($orgId, $coachId, $_POST, $userId);
        if ($ok) {
            header('Location: /coaches/' . $coachId . '?success=' . urlencode('Coach details updated successfully.'));
        } else {
            header('Location: /coaches/' . $coachId . '/edit?error=' . urlencode('Failed to update coach record.'));
        }
        exit;
    } catch (\Throwable $e) {
        header('Location: /coaches/' . $coachId . '/edit?error=' . urlencode('Unable to update coach: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/coaches/(\d+)/delete$#', $uri, $m)) {
    $coachId = (int)$m[1];
    $coachService = new \App\Services\Coach\CoachService();
    try {
        $ok = $coachService->deleteCoach($orgId, $coachId, $userId);
        header('Location: /coaches?success=' . urlencode('Coach record removed successfully.'));
        exit;
    } catch (\Throwable $e) {
        header('Location: /coaches?error=' . urlencode('Unable to delete coach: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/coaches/(\d+)/status$#', $uri, $m)) {
    $coachId = (int)$m[1];
    $status = trim($_POST['status'] ?? '');
    if (!in_array($status, ['active', 'inactive'], true)) {
        header('Location: /coaches?error=' . urlencode('Invalid status value.'));
        exit;
    }

    // RBAC Authorization Check using existing PermissionService
    $permissionService = new \App\Services\Rbac\PermissionService();
    $canUpdate = false;

    if (isset($_SESSION['auth'])) {
        $canUpdate = $permissionService->hasPermission($_SESSION['auth'], 'coach.update', $orgId)
                  || $permissionService->hasPermission($_SESSION['auth'], 'coach.manage', $orgId);
    }
    if (!$canUpdate) {
        $perms = $permissionService->getUserPermissions($userId, $orgId);
        $canUpdate = in_array('coach.update', $perms, true)
                  || in_array('coach.manage', $perms, true)
                  || ($userId === 1 || ($currentUser['role_id'] ?? 0) === 1);
    }

    if (!$canUpdate) {
        header('Location: /coaches?error=' . urlencode('Unauthorized: You do not have permission to update coach status.'));
        exit;
    }

    $coachService = new \App\Services\Coach\CoachService();
    try {
        $ok = $coachService->updateStatus($orgId, $coachId, $status, $userId);
        if ($ok) {
            header('Location: /coaches?success=' . urlencode('Coach status updated to ' . ucfirst($status) . '.'));
        } else {
            header('Location: /coaches?error=' . urlencode('Coach not found or access denied.'));
        }
        exit;
    } catch (\Throwable $e) {
        header('Location: /coaches?error=' . urlencode('Unable to update coach status: ' . $e->getMessage()));
        exit;
    }
}

// ==========================================
// 3. TEAM ACTIONS
// ==========================================
if ($uri === '/teams/create') {
    $name = trim($_POST['name'] ?? '');
    $sportId = (int)($_POST['sport_id'] ?? 0);

    if (empty($name) || empty($sportId)) {
        header('Location: /teams/create?error=' . urlencode('Team Name and Sport are required.'));
        exit;
    }

    $teamService = new \App\Services\Team\TeamService();
    try {
        $created = $teamService->createTeam($orgId, $_POST, $userId);
        $teamId = $created['id'] ?? null;
        if ($teamId) {
            header('Location: /teams/' . $teamId . '?success=' . urlencode('Team created successfully.'));
        } else {
            header('Location: /teams?success=' . urlencode('Team created successfully.'));
        }
        exit;
    } catch (\Throwable $e) {
        header('Location: /teams/create?error=' . urlencode('Unable to create team: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/teams/(\d+)/edit$#', $uri, $m)) {
    $teamId = (int)$m[1];
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        header('Location: /teams/' . $teamId . '/edit?error=' . urlencode('Team Name is required.'));
        exit;
    }

    $teamService = new \App\Services\Team\TeamService();
    try {
        $ok = $teamService->updateTeam($orgId, $teamId, $_POST, $userId);
        if ($ok) {
            header('Location: /teams/' . $teamId . '?success=' . urlencode('Team updated successfully.'));
        } else {
            header('Location: /teams/' . $teamId . '/edit?error=' . urlencode('Failed to update team record.'));
        }
        exit;
    } catch (\Throwable $e) {
        header('Location: /teams/' . $teamId . '/edit?error=' . urlencode('Unable to update team: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/teams/(\d+)/delete$#', $uri, $m)) {
    $teamId = (int)$m[1];
    $teamService = new \App\Services\Team\TeamService();
    try {
        $ok = $teamService->deleteTeam($orgId, $teamId, $userId);
        header('Location: /teams?success=' . urlencode('Team record removed successfully.'));
        exit;
    } catch (\Throwable $e) {
        header('Location: /teams?error=' . urlencode('Unable to delete team: ' . $e->getMessage()));
        exit;
    }
}

// ==========================================
// 4. TRAINING ACTIONS
// ==========================================
if ($uri === '/training/create') {
    $title = trim($_POST['title'] ?? '');
    $teamId = (int)($_POST['team_id'] ?? 0);
    $venueId = (int)($_POST['venue_id'] ?? 0);
    $date = trim($_POST['training_date'] ?? '');

    if (empty($title) || empty($teamId) || empty($venueId) || empty($date)) {
        header('Location: /training/create?error=' . urlencode('Session Title, Team, Venue, and Date are required.'));
        exit;
    }

    $trainService = new \App\Services\Training\TrainingService();
    try {
        $created = $trainService->createSession($orgId, $_POST, $userId);
        $sessionId = $created['id'] ?? null;
        if ($sessionId) {
            header('Location: /training/' . $sessionId . '?success=' . urlencode('Training session scheduled successfully.'));
        } else {
            header('Location: /training?success=' . urlencode('Training session scheduled successfully.'));
        }
        exit;
    } catch (\Throwable $e) {
        header('Location: /training/create?error=' . urlencode('Unable to schedule session: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/training/(\d+)/edit$#', $uri, $m)) {
    $sessionId = (int)$m[1];
    $title = trim($_POST['title'] ?? '');

    if (empty($title)) {
        header('Location: /training/' . $sessionId . '/edit?error=' . urlencode('Session Title is required.'));
        exit;
    }

    $trainService = new \App\Services\Training\TrainingService();
    try {
        $ok = $trainService->updateSession($orgId, $sessionId, $_POST, $userId);
        if ($ok) {
            header('Location: /training/' . $sessionId . '?success=' . urlencode('Training session updated successfully.'));
        } else {
            header('Location: /training/' . $sessionId . '/edit?error=' . urlencode('Failed to update training session.'));
        }
        exit;
    } catch (\Throwable $e) {
        header('Location: /training/' . $sessionId . '/edit?error=' . urlencode('Unable to update session: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/training/(\d+)/attendance$#', $uri, $m)) {
    $sessionId = (int)$m[1];
    $attendanceData = $_POST['attendance'] ?? [];

    $trainService = new \App\Services\Training\TrainingService();
    try {
        $ok = $trainService->recordAttendance($orgId, $sessionId, $attendanceData, $userId);
        header('Location: /training/' . $sessionId . '?success=' . urlencode('Attendance records saved successfully.'));
        exit;
    } catch (\Throwable $e) {
        header('Location: /training/' . $sessionId . '?error=' . urlencode('Unable to save attendance: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/training/(\d+)/delete$#', $uri, $m)) {
    $sessionId = (int)$m[1];
    $trainService = new \App\Services\Training\TrainingService();
    try {
        $ok = $trainService->deleteSession($orgId, $sessionId, $userId);
        header('Location: /training?success=' . urlencode('Training session removed successfully.'));
        exit;
    } catch (\Throwable $e) {
        header('Location: /training?error=' . urlencode('Unable to delete session: ' . $e->getMessage()));
        exit;
    }
}

// ==========================================
// 5. TOURNAMENT ACTIONS
// ==========================================
if ($uri === '/tournaments/create') {
    $name = trim($_POST['name'] ?? '');
    $sportId = (int)($_POST['sport_id'] ?? 0);

    if (empty($name) || empty($sportId)) {
        header('Location: /tournaments/create?error=' . urlencode('Tournament Name and Sport are required.'));
        exit;
    }

    $tournService = new \App\Services\Tournament\TournamentService();
    try {
        $created = $tournService->createTournament($orgId, $_POST, $userId);
        $tournId = $created['id'] ?? null;
        if ($tournId) {
            header('Location: /tournaments/' . $tournId . '?success=' . urlencode('Tournament created successfully.'));
        } else {
            header('Location: /tournaments?success=' . urlencode('Tournament created successfully.'));
        }
        exit;
    } catch (\Throwable $e) {
        header('Location: /tournaments/create?error=' . urlencode('Unable to create tournament: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/tournaments/(\d+)/edit$#', $uri, $m)) {
    $tournId = (int)$m[1];
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        header('Location: /tournaments/' . $tournId . '/edit?error=' . urlencode('Tournament Name is required.'));
        exit;
    }

    $tournService = new \App\Services\Tournament\TournamentService();
    try {
        $ok = $tournService->updateTournament($orgId, $tournId, $_POST, $userId);
        if ($ok) {
            header('Location: /tournaments/' . $tournId . '?success=' . urlencode('Tournament updated successfully.'));
        } else {
            header('Location: /tournaments/' . $tournId . '/edit?error=' . urlencode('Failed to update tournament.'));
        }
        exit;
    } catch (\Throwable $e) {
        header('Location: /tournaments/' . $tournId . '/edit?error=' . urlencode('Unable to update tournament: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/tournaments/(\d+)/fixtures/create$#', $uri, $m)) {
    $tournId = (int)$m[1];
    $homeId = (int)($_POST['home_team_id'] ?? 0);
    $awayId = (int)($_POST['away_team_id'] ?? 0);

    if (empty($homeId) || empty($awayId) || $homeId === $awayId) {
        header('Location: /tournaments/' . $tournId . '?error=' . urlencode('Please select two distinct teams for the fixture.'));
        exit;
    }

    $tournService = new \App\Services\Tournament\TournamentService();
    try {
        $tournService->createFixture($orgId, $tournId, $_POST, $userId);
        header('Location: /tournaments/' . $tournId . '?success=' . urlencode('Fixture and match scheduled successfully.'));
        exit;
    } catch (\Throwable $e) {
        header('Location: /tournaments/' . $tournId . '?error=' . urlencode('Unable to schedule fixture: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/tournaments/(\d+)/matches/(\d+)/result$#', $uri, $m)) {
    $tournId = (int)$m[1];
    $fixId = (int)$m[2];

    $tournService = new \App\Services\Tournament\TournamentService();
    try {
        $tournService->updateMatchResult($orgId, $fixId, $_POST, $userId);
        header('Location: /tournaments/' . $tournId . '?success=' . urlencode('Match result and standings updated successfully.'));
        exit;
    } catch (\Throwable $e) {
        header('Location: /tournaments/' . $tournId . '?error=' . urlencode('Unable to record score: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/tournaments/(\d+)/delete$#', $uri, $m)) {
    $tournId = (int)$m[1];
    $tournService = new \App\Services\Tournament\TournamentService();
    try {
        $ok = $tournService->deleteTournament($orgId, $tournId, $userId);
        header('Location: /tournaments?success=' . urlencode('Tournament removed successfully.'));
        exit;
    } catch (\Throwable $e) {
        header('Location: /tournaments?error=' . urlencode('Unable to delete tournament: ' . $e->getMessage()));
        exit;
    }
}

// ==========================================
// 6. VENUE & BOOKING ACTIONS
// ==========================================
if ($uri === '/venues/create') {
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['venue_type'] ?? '');

    if (empty($name) || empty($type)) {
        header('Location: /venues/create?error=' . urlencode('Venue Name and Type are required.'));
        exit;
    }

    $venueService = new \App\Services\Venue\VenueService();
    try {
        $created = $venueService->createVenue($orgId, $_POST, $userId);
        $venueId = $created['id'] ?? null;
        if ($venueId) {
            header('Location: /venues/' . $venueId . '?success=' . urlencode('Venue created successfully.'));
        } else {
            header('Location: /venues?success=' . urlencode('Venue created successfully.'));
        }
        exit;
    } catch (\Throwable $e) {
        header('Location: /venues/create?error=' . urlencode('Unable to create venue: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/venues/(\d+)/edit$#', $uri, $m)) {
    $venueId = (int)$m[1];
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        header('Location: /venues/' . $venueId . '/edit?error=' . urlencode('Venue Name is required.'));
        exit;
    }

    $venueService = new \App\Services\Venue\VenueService();
    try {
        $ok = $venueService->updateVenue($orgId, $venueId, $_POST, $userId);
        if ($ok) {
            header('Location: /venues/' . $venueId . '?success=' . urlencode('Venue updated successfully.'));
        } else {
            header('Location: /venues/' . $venueId . '/edit?error=' . urlencode('Failed to update venue.'));
        }
        exit;
    } catch (\Throwable $e) {
        header('Location: /venues/' . $venueId . '/edit?error=' . urlencode('Unable to update venue: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/venues/(\d+)/facilities/create$#', $uri, $m)) {
    $venueId = (int)$m[1];
    $facName = trim($_POST['name'] ?? '');

    if (empty($facName)) {
        header('Location: /venues/' . $venueId . '?error=' . urlencode('Facility Name is required.'));
        exit;
    }

    $venueService = new \App\Services\Venue\VenueService();
    try {
        $venueService->createFacility($orgId, $venueId, $_POST, $userId);
        header('Location: /venues/' . $venueId . '?success=' . urlencode('Facility slot created successfully.'));
        exit;
    } catch (\Throwable $e) {
        header('Location: /venues/' . $venueId . '?error=' . urlencode('Unable to add facility slot: ' . $e->getMessage()));
        exit;
    }
}

if ($uri === '/venues/bookings/create') {
    $venueId = (int)($_POST['venue_id'] ?? 0);
    $facilityId = (int)($_POST['facility_id'] ?? 0);
    $date = trim($_POST['booking_date'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');

    if (empty($venueId) || empty($facilityId) || empty($date) || empty($purpose)) {
        header('Location: /venues/bookings/create?error=' . urlencode('Please select Venue, Facility, Date, and Purpose.'));
        exit;
    }

    $venueService = new \App\Services\Venue\VenueService();
    try {
        $booking = $venueService->createBooking($orgId, $_POST, $userId);
        header('Location: /venues/' . $venueId . '?success=' . urlencode('Venue facility slot booked successfully.'));
        exit;
    } catch (\Throwable $e) {
        header('Location: /venues/bookings/create?venue_id=' . $venueId . '&facility_id=' . $facilityId . '&error=' . urlencode($e->getMessage()));
        exit;
    }
}

if (preg_match('#^/venues/(\d+)/delete$#', $uri, $m)) {
    $venueId = (int)$m[1];
    $venueService = new \App\Services\Venue\VenueService();
    try {
        $ok = $venueService->deleteVenue($orgId, $venueId, $userId);
        header('Location: /venues?success=' . urlencode('Venue removed successfully.'));
        exit;
    } catch (\Throwable $e) {
        header('Location: /venues?error=' . urlencode('Unable to delete venue: ' . $e->getMessage()));
        exit;
    }
}

// ==========================================
// 7. INVENTORY ACTIONS
// ==========================================
if ($uri === '/inventory/create') {
    $name = trim($_POST['item_name'] ?? '');

    if (empty($name)) {
        header('Location: /inventory/create?error=' . urlencode('Item Name is required.'));
        exit;
    }

    $invService = new \App\Services\Inventory\InventoryService();
    try {
        $created = $invService->createItem($orgId, $_POST, $userId);
        $itemId = $created['id'] ?? null;
        if ($itemId) {
            header('Location: /inventory/' . $itemId . '?success=' . urlencode('Inventory item created successfully.'));
        } else {
            header('Location: /inventory?success=' . urlencode('Inventory item created successfully.'));
        }
        exit;
    } catch (\Throwable $e) {
        header('Location: /inventory/create?error=' . urlencode('Unable to create item: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/inventory/(\d+)/edit$#', $uri, $m)) {
    $itemId = (int)$m[1];
    $name = trim($_POST['item_name'] ?? '');

    if (empty($name)) {
        header('Location: /inventory/' . $itemId . '/edit?error=' . urlencode('Item Name is required.'));
        exit;
    }

    $invService = new \App\Services\Inventory\InventoryService();
    try {
        $ok = $invService->updateItem($orgId, $itemId, $_POST, $userId);
        if ($ok) {
            header('Location: /inventory/' . $itemId . '?success=' . urlencode('Item updated successfully.'));
        } else {
            header('Location: /inventory/' . $itemId . '/edit?error=' . urlencode('Failed to update item.'));
        }
        exit;
    } catch (\Throwable $e) {
        header('Location: /inventory/' . $itemId . '/edit?error=' . urlencode('Unable to update item: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/inventory/(\d+)/transactions$#', $uri, $m)) {
    $itemId = (int)$m[1];
    $invService = new \App\Services\Inventory\InventoryService();
    try {
        $res = $invService->recordStockTransaction($orgId, $itemId, $_POST, $userId);
        header('Location: /inventory/' . $itemId . '?success=' . urlencode('Stock movement recorded. New quantity: ' . $res['new_quantity']));
        exit;
    } catch (\Throwable $e) {
        header('Location: /inventory/' . $itemId . '?error=' . urlencode('Stock transaction failed: ' . $e->getMessage()));
        exit;
    }
}

if (preg_match('#^/inventory/(\d+)/delete$#', $uri, $m)) {
    $itemId = (int)$m[1];
    $invService = new \App\Services\Inventory\InventoryService();
    try {
        $ok = $invService->deleteItem($orgId, $itemId, $userId);
        header('Location: /inventory?success=' . urlencode('Inventory item removed successfully.'));
        exit;
    } catch (\Throwable $e) {
        header('Location: /inventory?error=' . urlencode('Unable to delete item: ' . $e->getMessage()));
        exit;
    }
}

// ==========================================
// 8. LEAVE ACTIONS
// ==========================================
if ($uri === '/leave/create') {
    $leaveService = new \App\Services\Leave\LeaveService();
    try {
        $data = [
            'applicant_type' => $_POST['applicant_type'] ?? 'employee',
            'employee_id' => !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null,
            'athlete_id' => !empty($_POST['athlete_id']) ? (int)$_POST['athlete_id'] : null,
            'leave_type_id' => (int)($_POST['leave_type_id'] ?? 1),
            'start_date' => $_POST['start_date'] ?? '',
            'end_date' => $_POST['end_date'] ?? '',
            'total_days' => !empty($_POST['total_days']) ? (float)$_POST['total_days'] : 1,
            'reason' => trim($_POST['reason'] ?? ''),
            'attachment_path' => $_POST['attachment_path'] ?? null,
        ];
        $created = $leaveService->applyLeave($orgId, $data, $userId);
        header('Location: /leave/' . $created['id'] . '?success=' . urlencode('Leave application submitted successfully.'));
        exit;
    } catch (\Throwable $e) {
        header('Location: /leave/create?error=' . urlencode('Unable to submit leave: ' . $e->getMessage()));
        exit;
    }
}

