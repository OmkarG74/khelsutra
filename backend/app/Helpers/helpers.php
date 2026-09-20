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
