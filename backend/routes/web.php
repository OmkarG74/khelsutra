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

    // ==========================================
    // Member 1: Super Admin & Organisations
    // ==========================================
    '/super-admin/organizations' => function() {
        return ['view' => 'super-admin/organizations-index'];
    },
    '/super-admin/organizations/create' => function() {
        return ['view' => 'super-admin/organizations-create'];
    },
    '/super-admin/organizations/{id}' => function($id) {
        return ['view' => 'super-admin/organizations-show', 'data' => ['id' => $id]];
    },
    '/super-admin/organizations/{id}/edit' => function($id) {
        return ['view' => 'super-admin/organizations-edit', 'data' => ['id' => $id]];
    },

    // ==========================================
    // Member 1: Users & RBAC
    // ==========================================
    '/users' => function() {
        return ['view' => 'users/index'];
    },
    '/users/create' => function() {
        return ['view' => 'users/create'];
    },
    '/users/{id}' => function($id) {
        return ['view' => 'users/show', 'data' => ['id' => $id]];
    },
    '/users/{id}/edit' => function($id) {
        return ['view' => 'users/edit', 'data' => ['id' => $id]];
    },

    '/roles' => function() {
        return ['view' => 'rbac/roles'];
    },
    '/roles/{id}' => function($id) {
        return ['view' => 'rbac/role-details', 'data' => ['id' => $id]];
    },
    '/permissions' => function() {
        return ['view' => 'rbac/permissions'];
    },

    // ==========================================
    // Member 1: HR & Staff
    // ==========================================
    '/hr/employees' => function() {
        return ['view' => 'hr/employees-index'];
    },
    '/hr/employees/create' => function() {
        return ['view' => 'hr/employees-create'];
    },
    '/hr/employees/{id}' => function($id) {
        return ['view' => 'hr/employees-show', 'data' => ['id' => $id]];
    },
    '/hr/employees/{id}/edit' => function($id) {
        return ['view' => 'hr/employees-edit', 'data' => ['id' => $id]];
    },
    '/hr/departments' => function() {
        return ['view' => 'hr/departments-index'];
    },
    '/hr/employee-categories' => function() {
        return ['view' => 'hr/categories-index'];
    },
    '/hr/employees/{id}/documents' => function($id) {
        return ['view' => 'hr/documents-index', 'data' => ['id' => $id]];
    },

    // ==========================================
    // Member 1: Attendance
    // ==========================================
    '/attendance/training' => function() {
        return ['view' => 'attendance/training'];
    },
    '/attendance/matches' => function() {
        return ['view' => 'attendance/matches'];
    },
    '/attendance/history' => function() {
        return ['view' => 'attendance/history'];
    },

    // ==========================================
    // Member 1: Leave Management
    // ==========================================
    '/leave' => function() {
        return ['view' => 'leave/index'];
    },
    '/leave/create' => function() {
        return ['view' => 'leave/create'];
    },
    '/leave/{id}' => function($id) {
        return ['view' => 'leave/show', 'data' => ['id' => $id]];
    },

    // ==========================================
    // Member 1: Payroll Management
    // ==========================================
    '/payroll' => function() {
        return ['view' => 'payroll/index'];
    },
    '/payroll/salary-structures' => function() {
        return ['view' => 'payroll/salary-structures'];
    },
    '/payroll/periods' => function() {
        return ['view' => 'payroll/periods'];
    },
    '/payroll/periods/{id}' => function($id) {
        return ['view' => 'payroll/periods', 'data' => ['id' => $id]];
    },
    '/payroll/{id}' => function($id) {
        return ['view' => 'payroll/show', 'data' => ['id' => $id]];
    },

    // ==========================================
    // Member 1: Settings & Audit Logs
    // ==========================================
    '/settings/organization' => function() {
        return ['view' => 'settings/organization'];
    },
    '/audit-logs' => function() {
        return ['view' => 'audit/index'];
    },

    // ==========================================
    // Member 4: Operations & Logistics
    // ==========================================
    '/operations/venues' => function() { return ['view' => 'operations/venues/index']; },
    '/operations/venues/create' => function() { return ['view' => 'operations/venues/create']; },
    '/operations/venues/{id}' => function($id) { return ['view' => 'operations/venues/show', 'data' => ['id' => $id]]; },
    '/operations/venues/{id}/edit' => function($id) { return ['view' => 'operations/venues/edit', 'data' => ['id' => $id]]; },
    '/operations/venues/{venueId}/facilities' => function($venueId) { return ['view' => 'operations/facilities/index', 'data' => ['venueId' => $venueId]]; },
    '/operations/venues/{venueId}/facilities/create' => function($venueId) { return ['view' => 'operations/facilities/create', 'data' => ['venueId' => $venueId]]; },
    '/operations/venues/{venueId}/facilities/{id}/edit' => function($venueId, $id) { return ['view' => 'operations/facilities/edit', 'data' => ['venueId' => $venueId, 'id' => $id]]; },
];

