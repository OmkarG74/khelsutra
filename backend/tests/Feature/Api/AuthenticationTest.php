<?php

namespace Tests\Feature\Api;

use App\Services\Auth\AuthService;
use App\Http\Controllers\Api\V1\HealthController;

class AuthenticationTest
{
    public function testHealthCheckReturnsOk(): bool
    {
        $controller = new HealthController();
        $response = $controller->check();
        return ($response['success'] === true && $response['data']['status'] === 'ok');
    }

    public function testLoginWithValidCredentials(): bool
    {
        $service = new AuthService();
        $result = $service->login('admin@khelsutra.com', 'SecretPassword123', 'ORG-DEMO');
        return ($result !== null && !empty($result['token']));
    }

    public function testLoginWithInvalidPasswordFails(): bool
    {
        $service = new AuthService();
        $result = $service->login('admin@khelsutra.com', 'WrongPasswordXYZ', 'ORG-DEMO');
        return ($result === null);
    }
}
