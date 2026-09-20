<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;

class EnsureOrganizationAccess
{
    /**
     * Enforce tenant isolation and organization active status.
     *
     * @param mixed $request
     * @param callable $next
     * @return mixed
     */
    public function handle($request, callable $next)
    {
        // 1. Check if user is authenticated
        $user = $request->user ?? null;
        if (!$user) {
            return ApiResponse::send(ApiResponse::error('Unauthenticated.', null, 401));
        }

        // 2. Resolve requested or primary organization ID
        $requestedOrgId = $request->headers['x-organization-id'] 
            ?? $request->get['organization_id'] 
            ?? null;

        // 3. Super Admin bypass (Role ID 1)
        if (isset($user['role_id']) && (int)$user['role_id'] === 1) {
            if ($requestedOrgId) {
                if (!defined('CURRENT_ORGANIZATION_ID')) {
                    define('CURRENT_ORGANIZATION_ID', (int)$requestedOrgId);
                }
            }
            return $next($request);
        }

        // 4. For standard tenant users, verify organization membership
        $userOrgId = $user['organization_id'] ?? null;
        if (!$userOrgId) {
            return ApiResponse::send(ApiResponse::error('User is not assigned to any organization.', null, 403));
        }

        if ($requestedOrgId && (int)$requestedOrgId !== (int)$userOrgId) {
            return ApiResponse::send(ApiResponse::error('Access denied: Unauthorized organization context.', null, 403));
        }

        if (!defined('CURRENT_ORGANIZATION_ID')) {
            define('CURRENT_ORGANIZATION_ID', (int)$userOrgId);
        }

        return $next($request);
    }
}
