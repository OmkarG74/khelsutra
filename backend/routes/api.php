<?php

use App\Helpers\ApiResponse;

/**
 * KhelSutra API Route Definitions
 * Version: v1
 */

return function ($uri, $method, $requestData = []) {
    // 1. Health Check
    if ($uri === '/api/v1/health' && $method === 'GET') {
        $controller = new \App\Http\Controllers\Api\V1\HealthController();
        return $controller->check();
    }

    // 2. Authentication
    if ($uri === '/api/v1/auth/login' && $method === 'POST') {
        $controller = new \App\Http\Controllers\Api\V1\Auth\AuthController();
        return $controller->login($requestData);
    }
    if ($uri === '/api/v1/auth/logout' && $method === 'POST') {
        $controller = new \App\Http\Controllers\Api\V1\Auth\AuthController();
        return $controller->logout();
    }
    if ($uri === '/api/v1/auth/me' && $method === 'GET') {
        $controller = new \App\Http\Controllers\Api\V1\Auth\AuthController();
        $user = $requestData['user'] ?? [
            'id' => 1,
            'name' => 'Demo Admin',
            'email' => 'admin@khelsutra.com',
            'role' => 'Sports Administrator'
        ];
        return $controller->me($user);
    }
    if ($uri === '/api/v1/auth/refresh' && $method === 'POST') {
        $controller = new \App\Http\Controllers\Api\V1\Auth\AuthController();
        return $controller->refresh();
    }

    // Resolve tenant context (defaulting to organization 1 if unspecified for skeleton)
    $orgId = (int)($requestData['headers']['x-organization-id'] ?? $requestData['organization_id'] ?? 1);

    // 3. Athletes
    if ($uri === '/api/v1/athletes' && $method === 'GET') {
        $controller = new \App\Http\Controllers\Api\V1\Athletes\AthleteController();
        $page = (int)($requestData['page'] ?? 1);
        return $controller->index($orgId, $page);
    }
    if ($uri === '/api/v1/athletes' && $method === 'POST') {
        $controller = new \App\Http\Controllers\Api\V1\Athletes\AthleteController();
        return $controller->store($orgId, $requestData);
    }
    if (preg_match('#^/api/v1/athletes/(\d+)$#', $uri, $matches)) {
        $controller = new \App\Http\Controllers\Api\V1\Athletes\AthleteController();
        $id = (int)$matches[1];
        if ($method === 'GET') return $controller->show($orgId, $id);
        if ($method === 'PUT' || $method === 'PATCH') return $controller->update($orgId, $id, $requestData);
        if ($method === 'DELETE') return $controller->destroy($orgId, $id);
    }

    // 4. Teams
    if ($uri === '/api/v1/teams' && $method === 'GET') {
        $controller = new \App\Http\Controllers\Api\V1\Teams\TeamController();
        $page = (int)($requestData['page'] ?? 1);
        return $controller->index($orgId, $page);
    }
    if ($uri === '/api/v1/teams' && $method === 'POST') {
        $controller = new \App\Http\Controllers\Api\V1\Teams\TeamController();
        return $controller->store($orgId, $requestData);
    }
    if (preg_match('#^/api/v1/teams/(\d+)$#', $uri, $matches) && $method === 'GET') {
        $controller = new \App\Http\Controllers\Api\V1\Teams\TeamController();
        return $controller->show($orgId, (int)$matches[1]);
    }

    // 5. Tournaments
    if ($uri === '/api/v1/tournaments' && $method === 'GET') {
        $controller = new \App\Http\Controllers\Api\V1\Tournaments\TournamentController();
        $page = (int)($requestData['page'] ?? 1);
        return $controller->index($orgId, $page);
    }
    if ($uri === '/api/v1/tournaments' && $method === 'POST') {
        $controller = new \App\Http\Controllers\Api\V1\Tournaments\TournamentController();
        return $controller->store($orgId, $requestData);
    }
    if (preg_match('#^/api/v1/tournaments/(\d+)$#', $uri, $matches) && $method === 'GET') {
        $controller = new \App\Http\Controllers\Api\V1\Tournaments\TournamentController();
        return $controller->show($orgId, (int)$matches[1]);
    }

    // 6. Venues
    if ($uri === '/api/v1/venues' && $method === 'GET') {
        $controller = new \App\Http\Controllers\Api\V1\Venues\VenueController();
        $page = (int)($requestData['page'] ?? 1);
        return $controller->index($orgId, $page);
    }
    if ($uri === '/api/v1/venues' && $method === 'POST') {
        $controller = new \App\Http\Controllers\Api\V1\Venues\VenueController();
        return $controller->store($orgId, $requestData);
    }
    if (preg_match('#^/api/v1/venues/(\d+)$#', $uri, $matches) && $method === 'GET') {
        $controller = new \App\Http\Controllers\Api\V1\Venues\VenueController();
        return $controller->show($orgId, (int)$matches[1]);
    }

    // 7. Domain Route Skeletons for other groups
    $skeletonGroups = [
        'organizations', 'users', 'sports', 'coaches', 'staff', 'training',
        'performance', 'medical', 'fixtures', 'matches', 'bookings', 'maintenance',
        'housekeeping', 'attendance', 'leave', 'payroll', 'inventory', 'equipment',
        'vendors', 'purchases', 'events', 'school-activities', 'transport',
        'accommodation', 'finance', 'reports', 'notifications', 'settings'
    ];

    foreach ($skeletonGroups as $group) {
        if (str_starts_with($uri, "/api/v1/{$group}")) {
            return ApiResponse::success([
                'module' => $group,
                'action' => $method,
                'status' => 'skeleton_ready',
                'description' => "API route group /api/v1/{$group} initialized and awaiting business implementation."
            ], "Module [{$group}] endpoint acknowledged", 200);
        }
    }

    return ApiResponse::error('Endpoint not found', ['uri' => $uri, 'method' => $method], 404);
};
