<?php

namespace App\Services\Team;

use App\Repositories\Contracts\TeamRepositoryInterface;
use App\Repositories\Eloquent\TeamRepository;

class TeamService
{
    protected TeamRepositoryInterface $repository;

    public function __construct(?TeamRepositoryInterface $repository = null)
    {
        $this->repository = $repository ?? new TeamRepository();
    }

    public function listTeams(int $organizationId, int $page = 1, int $limit = 15): array
    {
        return $this->repository->getPaginated($organizationId, $page, $limit);
    }

    public function getTeam(int $organizationId, int $id): ?array
    {
        return $this->repository->findById($organizationId, $id);
    }

    public function createTeam(int $organizationId, array $data): array
    {
        $data['organization_id'] = $organizationId;
        return $this->repository->create($data);
    }
}
