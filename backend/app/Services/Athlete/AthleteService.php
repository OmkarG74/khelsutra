<?php

namespace App\Services\Athlete;

use App\Repositories\Contracts\AthleteRepositoryInterface;
use App\Repositories\Eloquent\AthleteRepository;
use App\Services\Audit\AuditLogService;

class AthleteService
{
    protected AthleteRepositoryInterface $repository;
    protected AuditLogService $auditLog;

    public function __construct(?AthleteRepositoryInterface $repository = null, ?AuditLogService $auditLog = null)
    {
        $this->repository = $repository ?? new AthleteRepository();
        $this->auditLog = $auditLog ?? new AuditLogService();
    }

    public function listAthletes(int $organizationId, int $page = 1, int $limit = 15, ?string $search = null, ?int $sportId = null, ?string $status = null): array
    {
        return $this->repository->getPaginated($organizationId, $page, $limit, $search, $sportId, $status);
    }

    public function getAthlete(int $organizationId, int $id): ?array
    {
        return $this->repository->findById($organizationId, $id);
    }

    public function registerAthlete(int $organizationId, array $data, ?int $performedBy = null): array
    {
        $data['organization_id'] = $organizationId;
        $athlete = $this->repository->create($data);

        // Audit log
        $this->auditLog->log(
            $organizationId,
            $performedBy,
            'ATHLETE_CREATE',
            'Athletes',
            'athletes',
            $athlete['id'] ?? null,
            null,
            ['athlete_code' => $athlete['athlete_code'] ?? '', 'name' => ($athlete['first_name'] ?? '') . ' ' . ($athlete['last_name'] ?? '')],
            "Registered athlete {$athlete['first_name']} {$athlete['last_name']}"
        );

        return $athlete;
    }

    public function updateAthlete(int $organizationId, int $id, array $data, ?int $performedBy = null): bool
    {
        $existing = $this->repository->findById($organizationId, $id);
        if (!$existing) return false;

        $updated = $this->repository->update($organizationId, $id, $data);
        if ($updated) {
            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'ATHLETE_UPDATE',
                'Athletes',
                'athletes',
                $id,
                $existing,
                $data,
                "Updated athlete #{$id} ({$existing['first_name']} {$existing['last_name']})"
            );
        }
        return $updated;
    }

    public function deleteAthlete(int $organizationId, int $id, ?int $performedBy = null): bool
    {
        $existing = $this->repository->findById($organizationId, $id);
        if (!$existing) return false;

        $deleted = $this->repository->delete($organizationId, $id);
        if ($deleted) {
            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'ATHLETE_DELETE',
                'Athletes',
                'athletes',
                $id,
                $existing,
                null,
                "Soft deleted athlete #{$id}"
            );
        }
        return $deleted;
    }
}
