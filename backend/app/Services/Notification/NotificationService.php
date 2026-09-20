<?php

namespace App\Services\Notification;

class NotificationService
{
    public function sendNotification(int $organizationId, int $userId, string $title, string $message): array
    {
        return [
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
}
