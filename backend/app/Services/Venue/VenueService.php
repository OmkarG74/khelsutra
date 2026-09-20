<?php

namespace App\Services\Venue;

use App\Repositories\Contracts\VenueRepositoryInterface;
use App\Repositories\Eloquent\VenueRepository;

class VenueService
{
    protected VenueRepositoryInterface $repository;

    public function __construct(?VenueRepositoryInterface $repository = null)
    {
        $this->repository = $repository ?? new VenueRepository();
    }

    public function listVenues(int $organizationId, int $page = 1, int $limit = 15): array
    {
        return $this->repository->getPaginated($organizationId, $page, $limit);
    }

    public function getVenue(int $organizationId, int $id): ?array
    {
        return $this->repository->findById($organizationId, $id);
    }

    public function createVenue(int $organizationId, array $data): array
    {
        $data['organization_id'] = $organizationId;
        return $this->repository->create($data);
    }
}
