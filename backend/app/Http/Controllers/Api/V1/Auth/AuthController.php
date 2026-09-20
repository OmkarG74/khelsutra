<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use App\Http\Requests\Auth\LoginRequest;
use App\Helpers\ApiResponse;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(?AuthService $authService = null)
    {
        $this->authService = $authService ?? new AuthService();
    }

    public function login(array $requestData): array
    {
        $validator = new LoginRequest($requestData);
        $errors = $validator->validate();
        if (!empty($errors)) {
            return ApiResponse::error('Validation failed', $errors, 422);
        }

        $session = $this->authService->login(
            $validator->email,
            $validator->password,
            $validator->organization_code
        );

        if (!$session) {
            return ApiResponse::error('Invalid credentials or inactive account.', null, 401);
        }

        return ApiResponse::success($session, 'Login successful', 200);
    }

    public function logout(): array
    {
        return ApiResponse::success(null, 'Successfully logged out', 200);
    }

    public function me(array $currentUser): array
    {
        return ApiResponse::success($currentUser, 'User profile retrieved', 200);
    }

    public function refresh(): array
    {
        $newToken = bin2hex(random_bytes(32));
        return ApiResponse::success(['token' => $newToken], 'Token refreshed successfully', 200);
    }
}
