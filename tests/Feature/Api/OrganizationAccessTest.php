<?php

namespace Tests\Feature\Api;

use App\Http\Middleware\EnsureOrganizationAccess;

class OrganizationAccessTest
{
    public function testSuperAdminBypassesOrganizationRestriction(): bool
    {
        $middleware = new EnsureOrganizationAccess();
        $request = (object)[
            'user' => ['id' => 1, 'role_id' => 1],
            'headers' => ['x-organization-id' => '2']
        ];
        $passed = false;
        $middleware->handle($request, function($req) use (&$passed) {
            $passed = true;
            return true;
        });
        return $passed;
    }
}
