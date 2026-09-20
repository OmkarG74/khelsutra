<?php

/**
 * KhelSutra Web Routes
 * Maps URL paths to Master UI/UX Views
 */

return [
    '/' => function() {
        return ['view' => 'dashboard/index'];
    },
    '/dashboard' => function() {
        return ['view' => 'dashboard/index'];
    },
    '/athletes' => function() {
        return ['view' => 'sports/athletes'];
    },
    '/coaches' => function() {
        return ['view' => 'sports/coaches'];
    },
    '/teams' => function() {
        return ['view' => 'sports/teams'];
    },
    '/tournaments' => function() {
        return ['view' => 'competitions/tournaments'];
    },
    '/training' => function() {
        return ['view' => 'sports/training'];
    },
    '/venues' => function() {
        return ['view' => 'venues/venues'];
    },
    '/inventory' => function() {
        return ['view' => 'inventory/inventory'];
    },
    '/hr-finance' => function() {
        return ['view' => 'hr/hr-finance'];
    },
    '/reports' => function() {
        return ['view' => 'reports/reports'];
    },
    '/settings' => function() {
        return ['view' => 'settings/settings'];
    },
    '/login' => function() {
        return ['view' => 'auth/login'];
    },
];
