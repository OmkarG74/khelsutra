<?php

namespace App\Services\Athlete;

use App\Repositories\Contracts\AthleteRepositoryInterface;
use App\Repositories\Eloquent\AthleteRepository;

class AthleteService
{
    protected AthleteRepositoryInterface $repository;

    public function __construct(?AthleteRepositoryInterface $repository = null)
    {
        $this->repository = $repository ?? new AthleteRepository();
    }

    public function listAthletes(int $organizationId, int $page = 1, int $limit = 15): array
    {
        return $this->repository->getPaginated($organizationId, $page, $limit);
    }

    public function getAthlete(int $organizationId, int $id): ?array
    {
        return $this->repository->findById($organizationId, $id);
    }

    public function registerAthlete(int $organizationId, array $data): array
    {
        $data['organization_id'] = $organizationId;
        $data['athlete_code'] = 'ATH-' . strtoupper(substr(uniqid(), -6));
        return $this->repository->create($data);
    }

    public function updateAthlete(int $organizationId, int $id, array $data): bool
    {
        return $this->repository->update($organizationId, $id, $data);
    }

    public function deleteAthlete(int $organizationId, int $id): bool
    {
        return $this->repository->delete($organizationId, $id);
    }
}
