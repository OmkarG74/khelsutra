<?php

namespace App\Http\Controllers\Api\V1\Athletes;

use App\Http\Controllers\Controller;
use App\Services\Athlete\AthleteService;
use App\Http\Requests\Athletes\StoreAthleteRequest;
use App\Helpers\ApiResponse;

class AthleteController extends Controller
{
    protected AthleteService $athleteService;

    public function __construct(?AthleteService $athleteService = null)
    {
        $this->athleteService = $athleteService ?? new AthleteService();
    }

    public function index(int $organizationId, int $page = 1, int $limit = 15): array
    {
        $athletes = $this->athleteService->listAthletes($organizationId, $page, $limit);
        return ApiResponse::success($athletes, 'Athletes retrieved successfully', 200);
    }

    public function show(int $organizationId, int $id, array $requestData = []): array
    {
        $authError = $this->authorizeAthleteAccess($organizationId, $id, $requestData);
        if ($authError !== null) {
            return $authError;
        }

        $athlete = $this->athleteService->getAthlete($organizationId, $id);
        if (!$athlete) {
            return ApiResponse::error('Athlete not found', null, 404);
        }
        return ApiResponse::success($athlete, 'Athlete retrieved successfully', 200);
    }

    public function documents(int $organizationId, int $id, array $requestData = []): array
    {
        $authError = $this->authorizeAthleteAccess($organizationId, $id, $requestData);
        if ($authError !== null) {
            return $authError;
        }

        $docService = new \App\Services\Athlete\AthleteDocumentService();
        $docs = $docService->listDocuments($organizationId, $id);
        return ApiResponse::success($docs, 'Athlete documents retrieved successfully', 200);
    }

    protected function authorizeAthleteAccess(int $organizationId, int $id, array $requestData): ?array
    {
        $currentUser = $requestData['user'] ?? null;
        if (!$currentUser) return null;

        $roleSlug = $currentUser['role']['slug'] ?? '';
        $isAthlete = ($roleSlug === 'athlete') || !empty($currentUser['athlete_id']);
        $isCoach = ($roleSlug === 'coach') || !empty($currentUser['coach_id']);

        if ($isAthlete) {
            $myAthleteId = (int)($currentUser['athlete_id'] ?? 0);
            if ($myAthleteId !== $id) {
                return ApiResponse::error('Access denied: Athletes can only view their own profile and documents.', null, 403);
            }
            return null;
        }

        if ($isCoach) {
            $coachId = (int)($currentUser['coach_id'] ?? 0);
            $pdo = \App\Services\BaseService::getDatabaseConnection();
            if (!$coachId && !empty($currentUser['id']) && $pdo) {
                $cStmt = $pdo->prepare("
                    SELECT cp.id 
                    FROM coach_profiles cp 
                    JOIN employees e ON cp.employee_id = e.id 
                    WHERE e.user_id = :uid AND cp.organization_id = :oid AND cp.deleted_at IS NULL 
                    LIMIT 1
                ");
                $cStmt->execute([':uid' => (int)$currentUser['id'], ':oid' => $organizationId]);
                $coachId = (int)($cStmt->fetchColumn() ?: 0);
            }

            if ($coachId && $pdo) {
                $tcStmt = $pdo->prepare("
                    SELECT 1 
                    FROM team_coaches tc
                    JOIN team_members tm ON tc.team_id = tm.team_id AND tm.is_current = 1
                    WHERE tc.coach_id = :cid AND tm.athlete_id = :aid AND tc.organization_id = :oid
                    LIMIT 1
                ");
                $tcStmt->execute([':cid' => $coachId, ':aid' => $id, ':oid' => $organizationId]);
                if (!$tcStmt->fetchColumn()) {
                    return ApiResponse::error('Access denied: Athlete is not in your assigned squads.', null, 403);
                }
            }
            return null;
        }

        return null;
    }

    public function store(int $organizationId, array $requestData): array
    {
        $request = new StoreAthleteRequest($requestData);
        $errors = $request->validate();
        if (!empty($errors)) {
            return ApiResponse::error('Validation failed', $errors, 422);
        }

        $created = $this->athleteService->registerAthlete($organizationId, $requestData);
        return ApiResponse::success($created, 'Athlete registered successfully', 201);
    }

    public function update(int $organizationId, int $id, array $requestData): array
    {
        $updated = $this->athleteService->updateAthlete($organizationId, $id, $requestData);
        if (!$updated) {
            return ApiResponse::error('Failed to update athlete or record not found', null, 400);
        }
        return ApiResponse::success(null, 'Athlete updated successfully', 200);
    }

    public function destroy(int $organizationId, int $id): array
    {
        $deleted = $this->athleteService->deleteAthlete($organizationId, $id);
        if (!$deleted) {
            return ApiResponse::error('Failed to delete athlete or record not found', null, 400);
        }
        return ApiResponse::success(null, 'Athlete deleted successfully', 200);
    }

    public function medical(int $organizationId, int $id, array $requestData): array
    {
        $authError = $this->authorizeAthleteAccess($organizationId, $id, $requestData);
        if ($authError) {
            return $authError;
        }

        $pdo = $this->athleteService->getPdo() ?? \App\Services\BaseService::getDatabaseConnection();
        $injStmt = $pdo->prepare("
            SELECT id, injury_date, injury_type, body_part, severity, treatment,
                   expected_recovery_date, actual_recovery_date, status, notes
            FROM athlete_injuries
            WHERE athlete_id = :id AND organization_id = :org_id AND deleted_at IS NULL
            ORDER BY injury_date DESC
        ");
        $injStmt->execute([':id' => $id, ':org_id' => $organizationId]);
        $injuries = $injStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $clrStmt = $pdo->prepare("
            SELECT id, clearance_date, valid_until, doctor_name, clearance_status,
                   restrictions, certificate_path, notes
            FROM athlete_medical_clearances
            WHERE athlete_id = :id AND organization_id = :org_id AND deleted_at IS NULL
            ORDER BY clearance_date DESC
        ");
        $clrStmt->execute([':id' => $id, ':org_id' => $organizationId]);
        $clearances = $clrStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $fitness = 'fit';
        foreach ($injuries as $inj) {
            if ($inj['status'] === 'active' || $inj['status'] === 'recovering') {
                $fitness = 'unfit';
                break;
            }
        }
        if ($fitness === 'fit' && !empty($clearances)) {
            $latest = $clearances[0];
            if ($latest['clearance_status'] === 'unfit') {
                $fitness = 'unfit';
            } elseif ($latest['clearance_status'] === 'fit_with_restrictions') {
                $fitness = 'fit_with_restrictions';
            }
        }

        return ApiResponse::success([
            'athlete_id' => $id,
            'fitness_status' => $fitness,
            'injuries' => $injuries,
            'clearances' => $clearances,
        ], 'Athlete medical records retrieved successfully', 200);
    }
}
