<?php

namespace App\Http\Controllers\Api\V1\Training;

use App\Http\Controllers\Controller;
use App\Services\Training\TrainingService;
use App\Helpers\ApiResponse;
use PDO;

class TrainingController extends Controller
{
    protected TrainingService $trainingService;

    public function __construct(?TrainingService $trainingService = null)
    {
        $this->trainingService = $trainingService ?? new TrainingService();
    }

    public function index(int $organizationId, array $requestData): array
    {
        $page = (int)($requestData['page'] ?? 1);
        $limit = (int)($requestData['limit'] ?? 20);
        $search = $requestData['search'] ?? null;
        $date = $requestData['date'] ?? null;
        $status = $requestData['status'] ?? null;
        $teamId = !empty($requestData['team_id']) ? (int)$requestData['team_id'] : null;

        // Resolve coach identity from authenticated context
        $coachId = null;
        $currentUser = $requestData['user'] ?? null;
        $roleSlug = $currentUser['role']['slug'] ?? '';
        $isCoachUser = ($roleSlug === 'coach') || !empty($currentUser['coach_id']);

        if ($isCoachUser) {
            $coachId = (int)($currentUser['coach_id'] ?? 0);
            if (!$coachId && !empty($currentUser['id'])) {
                $pdo = $this->trainingService->getPdo();
                if ($pdo) {
                    $cStmt = $pdo->prepare("
                        SELECT cp.id 
                        FROM coach_profiles cp 
                        JOIN employees e ON cp.employee_id = e.id 
                        WHERE e.user_id = :uid AND cp.organization_id = :oid AND cp.deleted_at IS NULL 
                        LIMIT 1
                    ");
                    $cStmt->execute([':uid' => (int)$currentUser['id'], ':oid' => $organizationId]);
                    $foundCoach = $cStmt->fetchColumn();
                    if ($foundCoach) {
                        $coachId = (int)$foundCoach;
                    }
                }
            }
        } elseif (!empty($requestData['coach_id'])) {
            // Admin filtering by a specific coach
            $coachId = (int)$requestData['coach_id'];
        }

        // If athlete_id is supplied, resolve the athlete's team(s)
        if (!empty($requestData['athlete_id'])) {
            $pdo = $this->trainingService->getPdo();
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT team_id FROM team_members WHERE athlete_id = :aid AND organization_id = :oid AND is_current = 1 LIMIT 1");
                $stmt->execute([':aid' => (int)$requestData['athlete_id'], ':oid' => $organizationId]);
                $athTeamId = $stmt->fetchColumn();
                if ($athTeamId) {
                    $teamId = (int)$athTeamId;
                }
            }
        }

        $sessions = $this->trainingService->listSessions($organizationId, $page, $limit, $search, $date, $status, $teamId, $coachId);

        return ApiResponse::success($sessions, 'Training sessions retrieved successfully', 200);
    }

    public function show(int $organizationId, int $id, array $requestData = []): array
    {
        $session = $this->trainingService->getSession($organizationId, $id);
        if (!$session) {
            return ApiResponse::error('Training session not found', null, 404);
        }

        // Authorize coach access
        $currentUser = $requestData['user'] ?? null;
        $roleSlug = $currentUser['role']['slug'] ?? '';
        $isCoachUser = ($roleSlug === 'coach') || !empty($currentUser['coach_id']);
        if ($isCoachUser) {
            $coachId = (int)($currentUser['coach_id'] ?? 0);
            if (!$coachId && !empty($currentUser['id'])) {
                $pdo = $this->trainingService->getPdo();
                if ($pdo) {
                    $cStmt = $pdo->prepare("
                        SELECT cp.id 
                        FROM coach_profiles cp 
                        JOIN employees e ON cp.employee_id = e.id 
                        WHERE e.user_id = :uid AND cp.organization_id = :oid AND cp.deleted_at IS NULL 
                        LIMIT 1
                    ");
                    $cStmt->execute([':uid' => (int)$currentUser['id'], ':oid' => $organizationId]);
                    $foundCoach = $cStmt->fetchColumn();
                    if ($foundCoach) $coachId = (int)$foundCoach;
                }
            }

            if ($coachId) {
                $sessionCoachId = (int)($session['coach_id'] ?? 0);
                $isCoachOfSession = ($sessionCoachId === $coachId);
                if (!$isCoachOfSession && !empty($session['team_id'])) {
                    $pdo = $this->trainingService->getPdo();
                    if ($pdo) {
                        $tcStmt = $pdo->prepare("SELECT 1 FROM team_coaches WHERE team_id = :t_id AND coach_id = :c_id AND organization_id = :oid LIMIT 1");
                        $tcStmt->execute([':t_id' => $session['team_id'], ':c_id' => $coachId, ':oid' => $organizationId]);
                        $isCoachOfSession = (bool)$tcStmt->fetchColumn();
                    }
                }
                if (!$isCoachOfSession) {
                    return ApiResponse::error('Access denied: You are not assigned to coach this training session.', null, 403);
                }
            }
        }

        return ApiResponse::success($session, 'Training session details retrieved', 200);
    }

    public function store(int $organizationId, array $requestData, ?int $performedBy = null): array
    {
        if (empty($requestData['title'])) {
            return ApiResponse::error('Validation failed: Training title is required.', ['title' => ['Training title is required']], 422);
        }

        $pdo = $this->trainingService->getPdo();

        // 1. Resolve Coach ID
        $coachId = !empty($requestData['coach_id']) ? (int)$requestData['coach_id'] : null;
        if (!$coachId && !empty($requestData['user']['coach_id'])) {
            $coachId = (int)$requestData['user']['coach_id'];
        }
        if (!$coachId && $pdo && $performedBy) {
            $cStmt = $pdo->prepare("
                SELECT cp.id 
                FROM coach_profiles cp 
                JOIN employees e ON cp.employee_id = e.id 
                WHERE e.user_id = :uid AND cp.organization_id = :oid AND cp.deleted_at IS NULL 
                LIMIT 1
            ");
            $cStmt->execute([':uid' => $performedBy, ':oid' => $organizationId]);
            $foundCoach = $cStmt->fetchColumn();
            if ($foundCoach) {
                $coachId = (int)$foundCoach;
            }
        }

        // 2. Resolve Team ID (Mandatory database constraint in training_sessions)
        $teamId = !empty($requestData['team_id']) && is_numeric($requestData['team_id']) ? (int)$requestData['team_id'] : null;

        // If teamId not explicitly numeric, search by team name if provided
        $teamNameInput = $requestData['team_name'] ?? ($requestData['team'] ?? (!is_numeric($requestData['team_id'] ?? null) ? ($requestData['team_id'] ?? null) : null));
        if (!$teamId && $pdo && !empty($teamNameInput)) {
            $tStmt = $pdo->prepare("SELECT id FROM teams WHERE organization_id = :oid AND (name = :name OR name LIKE :like_name) AND deleted_at IS NULL LIMIT 1");
            $tStmt->execute([
                ':oid' => $organizationId,
                ':name' => trim((string)$teamNameInput),
                ':like_name' => '%' . trim((string)$teamNameInput) . '%'
            ]);
            $foundTeam = $tStmt->fetchColumn();
            if ($foundTeam) {
                $teamId = (int)$foundTeam;
            }
        }

        // If still not resolved, resolve from coach's assigned teams
        if (!$teamId && $pdo && $coachId) {
            $tcStmt = $pdo->prepare("SELECT team_id FROM team_coaches WHERE coach_id = :cid AND organization_id = :oid ORDER BY is_primary DESC, id ASC LIMIT 1");
            $tcStmt->execute([':cid' => $coachId, ':oid' => $organizationId]);
            $foundTeam = $tcStmt->fetchColumn();
            if ($foundTeam) {
                $teamId = (int)$foundTeam;
            }
        }

        // Fallback: first active team in this organization
        if (!$teamId && $pdo) {
            $tDefStmt = $pdo->prepare("SELECT id FROM teams WHERE organization_id = :oid AND status = 'active' AND deleted_at IS NULL ORDER BY id ASC LIMIT 1");
            $tDefStmt->execute([':oid' => $organizationId]);
            $foundTeam = $tDefStmt->fetchColumn();
            if ($foundTeam) {
                $teamId = (int)$foundTeam;
            }
        }

        if (!$teamId) {
            return ApiResponse::error(
                'Validation failed: A valid team is required to schedule a training session.',
                ['team_id' => ['Please assign or select a valid team within your organization.']],
                422
            );
        }

        // 3. Resolve Venue ID
        $venueId = !empty($requestData['venue_id']) && is_numeric($requestData['venue_id']) ? (int)$requestData['venue_id'] : null;
        $venueNameInput = $requestData['venue_name'] ?? ($requestData['venue'] ?? null);
        if (!$venueId && $pdo && !empty($venueNameInput)) {
            $vStmt = $pdo->prepare("SELECT id FROM venues WHERE organization_id = :oid AND (name = :name OR name LIKE :like_name) AND deleted_at IS NULL LIMIT 1");
            $vStmt->execute([
                ':oid' => $organizationId,
                ':name' => trim((string)$venueNameInput),
                ':like_name' => '%' . trim((string)$venueNameInput) . '%'
            ]);
            $foundVenue = $vStmt->fetchColumn();
            if ($foundVenue) {
                $venueId = (int)$foundVenue;
            }
        }
        if (!$venueId && $pdo) {
            $vDefStmt = $pdo->prepare("SELECT id FROM venues WHERE organization_id = :oid AND status = 'active' AND deleted_at IS NULL ORDER BY id ASC LIMIT 1");
            $vDefStmt->execute([':oid' => $organizationId]);
            $foundVenue = $vDefStmt->fetchColumn();
            if ($foundVenue) {
                $venueId = (int)$foundVenue;
            }
        }

        // 4. Sanitize Timings & Dates
        $trainingDate = $requestData['training_date'] ?? ($requestData['session_date'] ?? date('Y-m-d'));
        $startTime = $requestData['start_time'] ?? '06:00:00';
        $endTime = $requestData['end_time'] ?? '08:00:00';

        $sessionData = [
            'title' => trim($requestData['title']),
            'training_date' => $trainingDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'team_id' => $teamId,
            'coach_id' => $coachId,
            'venue_id' => $venueId,
            'facility_id' => !empty($requestData['facility_id']) ? (int)$requestData['facility_id'] : null,
            'training_type' => $requestData['training_type'] ?? ($requestData['type'] ?? 'Tactical Drill'),
            'objectives' => $requestData['objectives'] ?? ($requestData['instructions'] ?? null),
            'notes' => $requestData['notes'] ?? 'Created via KhelSutra Coach Mobile App',
            'status' => $requestData['status'] ?? 'scheduled',
        ];

        try {
            $created = $this->trainingService->createSession($organizationId, $sessionData, $performedBy);
            $fullSession = $this->trainingService->getSession($organizationId, $created['id']) ?? $created;
            return ApiResponse::success($fullSession, 'Training session created successfully', 201);
        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to create training session: ' . $e->getMessage(), null, 400);
        }
    }

    public function update(int $organizationId, int $id, array $requestData, ?int $performedBy = null): array
    {
        $updated = $this->trainingService->updateSession($organizationId, $id, $requestData, $performedBy);
        if (!$updated) {
            return ApiResponse::error('Training session not found or update failed', null, 400);
        }
        return ApiResponse::success($updated, 'Training session updated successfully', 200);
    }

    public function destroy(int $organizationId, int $id, ?int $performedBy = null): array
    {
        $deleted = $this->trainingService->deleteSession($organizationId, $id, $performedBy);
        if (!$deleted) {
            return ApiResponse::error('Training session not found or delete failed', null, 400);
        }
        return ApiResponse::success(null, 'Training session deleted successfully', 200);
    }
}
