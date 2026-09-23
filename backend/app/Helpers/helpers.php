<?php

// KhelSutra Global Helper Functions

if (!function_exists('current_organization_id')) {
    function current_organization_id(): int
    {
        return \App\Helpers\AuthContext::getOrganizationId();
    }
}

if (!function_exists('current_user_id')) {
    function current_user_id(): ?int
    {
        return \App\Helpers\AuthContext::getUserId();
    }
}

if (!function_exists('format_coach_role')) {
    function format_coach_role(?string $role): string
    {
        $role = trim((string)$role);
        $map = [
            'head_coach' => 'Head Coach',
            'assistant_coach' => 'Assistant Coach',
            'fitness_coach' => 'Fitness Coach',
            'other' => 'Other',
        ];
        if (isset($map[$role])) {
            return $map[$role];
        }
        return ucwords(str_replace('_', ' ', $role ?: 'assistant_coach'));
    }
}
