<?php

namespace App\Repositories\Contracts;

interface VenueRepositoryInterface
{
    public function getPaginated(int $organizationId, int $page = 1, int $limit = 15): array;
    public function findById(int $organizationId, int $id): ?array;
    public function create(array $data): array;
}
