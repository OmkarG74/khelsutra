<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;

class RequirePermission
{
    /**
     * Check if authenticated user holds the required permission.
     *
     * @param mixed $request
     * @param callable $next
     * @param string $permission
     * @return mixed
     */
    public function handle($request, callable $next, string $permission)
    {
        $user = $request->user ?? null;
        if (!$user) {
            return ApiResponse::send(ApiResponse::error('Unauthenticated.', null, 401));
        }

        // Super Admin has all permissions
        if (isset($user['role_id']) && (int)$user['role_id'] === 1) {
            return $next($request);
        }

        // Sports Administrator has full organization permissions
        if (isset($user['role_id']) && (int)$user['role_id'] === 2) {
            return $next($request);
        }

        $userPermissions = $user['permissions'] ?? [];

        if (!in_array($permission, $userPermissions, true)) {
            return ApiResponse::send(ApiResponse::error("You do not have permission to perform this action [{$permission}].", null, 403));
        }

        return $next($request);
    }
}
