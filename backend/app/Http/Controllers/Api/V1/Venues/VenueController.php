<?php

namespace App\Http\Controllers\Api\V1\Venues;

use App\Http\Controllers\Controller;
use App\Services\Venue\VenueService;
use App\Http\Requests\Venues\StoreVenueRequest;
use App\Helpers\ApiResponse;

class VenueController extends Controller
{
    protected VenueService $venueService;

    public function __construct(?VenueService $venueService = null)
    {
        $this->venueService = $venueService ?? new VenueService();
    }

    public function index(int $organizationId, int $page = 1, int $limit = 15): array
    {
        $venues = $this->venueService->listVenues($organizationId, $page, $limit);
        return ApiResponse::success($venues, 'Venues retrieved successfully', 200);
    }

    public function show(int $organizationId, int $id): array
    {
        $venue = $this->venueService->getVenue($organizationId, $id);
        if (!$venue) {
            return ApiResponse::error('Venue not found', null, 404);
        }
        return ApiResponse::success($venue, 'Venue retrieved successfully', 200);
    }

    public function store(int $organizationId, array $requestData): array
    {
        $request = new StoreVenueRequest($requestData);
        $errors = $request->validate();
        if (!empty($errors)) {
            return ApiResponse::error('Validation failed', $errors, 422);
        }

        $created = $this->venueService->createVenue($organizationId, $requestData);
        return ApiResponse::success($created, 'Venue created successfully', 201);
    }
}
