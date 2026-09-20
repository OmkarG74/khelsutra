<?php

namespace App\Services\Tournament;

use App\Repositories\Contracts\TournamentRepositoryInterface;
use App\Repositories\Eloquent\TournamentRepository;

class TournamentService
{
    protected TournamentRepositoryInterface $repository;

    public function __construct(?TournamentRepositoryInterface $repository = null)
    {
        $this->repository = $repository ?? new TournamentRepository();
    }

    public function listTournaments(int $organizationId, int $page = 1, int $limit = 15): array
    {
        return $this->repository->getPaginated($organizationId, $page, $limit);
    }

    public function getTournament(int $organizationId, int $id): ?array
    {
        return $this->repository->findById($organizationId, $id);
    }

    public function createTournament(int $organizationId, array $data): array
    {
        $data['organization_id'] = $organizationId;
        return $this->repository->create($data);
    }
}
