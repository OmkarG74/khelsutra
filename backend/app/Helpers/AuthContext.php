<?php

namespace App\Helpers;

class AuthContext
{
    /**
     * Get the dynamic authenticated organization ID.
     * Always resolves from active session or environment/constant.
     */
    public static function getOrganizationId(): int
    {
        if (isset($_SESSION['auth']['organization']['id']) && !empty($_SESSION['auth']['organization']['id'])) {
            return (int)$_SESSION['auth']['organization']['id'];
        }

        if (isset($_SESSION['current_organization_id']) && !empty($_SESSION['current_organization_id'])) {
            return (int)$_SESSION['current_organization_id'];
        }

        if (defined('CURRENT_ORGANIZATION_ID') && CURRENT_ORGANIZATION_ID !== null) {
            return (int)CURRENT_ORGANIZATION_ID;
        }

        // Default local tenant fallback for CLI/unauthenticated test environments
        return 1;
    }

    /**
     * Get the current authenticated user ID.
     */
    public static function getUserId(): ?int
    {
        return isset($_SESSION['auth']['user']['id']) ? (int)$_SESSION['auth']['user']['id'] : null;
    }

    /**
     * Get the current authenticated user profile array.
     */
    public static function getUser(): ?array
    {
        return $_SESSION['auth']['user'] ?? null;
    }

    /**
     * Get the current authenticated user role slug.
     */
    public static function getRoleSlug(): string
    {
        return $_SESSION['auth']['role']['slug'] ?? ($_SESSION['role_slug'] ?? 'sports_admin');
    }
}

require_once __DIR__ . '/helpers.php';
