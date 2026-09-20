<?php

/**
 * KhelSutra Web Routes
 */

return [
    '/' => function() {
        return ['view' => 'dashboard/index'];
    },
    '/dashboard' => function() {
        return ['view' => 'dashboard/index'];
    },
    '/login' => function() {
        return ['view' => 'auth/login'];
    },
];
