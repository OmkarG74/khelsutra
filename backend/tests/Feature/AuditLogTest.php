<?php

namespace Tests\Feature;

use App\Services\Audit\AuditLogService;

class AuditLogTest
{
    /**
     * Test audit logging logs events and redacts sensitive passwords (Section 42)
     */
    public function testAuditLogRedactsPasswords(): bool
    {
        $service = new AuditLogService();
        $service->log(
            1,
            1,
            'user.password_reset',
            'auth',
            'users',
            1,
            ['password' => '$2y$10$oldsecretpasswordhash'],
            ['password' => '$2y$10$newsecretpasswordhash', 'status' => 'active'],
            'User reset their password'
        );

        // Fetch recent logs
        $logs = $service->getLogs(1, 20);
        $found = false;
        foreach ($logs as $l) {
            if ($l['action'] === 'user.password_reset') {
                $newVals = json_decode($l['new_values'], true);
                if (isset($newVals['password']) && $newVals['password'] === '[REDACTED]') {
                    $found = true;
                    break;
                }
            }
        }

        return $found;
    }
}
