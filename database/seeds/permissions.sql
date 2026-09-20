-- KhelSutra Permissions and Role Permissions Seeder

USE khelsutra;

-- Permissions Catalog
INSERT INTO permissions (name, module, action, description, created_at, updated_at) VALUES
-- Tenancy & Organization
('organization.view', 'organization', 'view', 'View organization details', NOW(), NOW()),
('organization.manage', 'organization', 'manage', 'Manage organization settings and details', NOW(), NOW()),
('user.manage', 'user', 'manage', 'Manage organization users and role assignments', NOW(), NOW()),

-- Sports Catalog
('sport.view', 'sport', 'view', 'View sports and categories', NOW(), NOW()),
('sport.manage', 'sport', 'manage', 'Create and edit sports and categories', NOW(), NOW()),

-- Athletes
('athlete.view', 'athlete', 'view', 'View athlete records and profiles', NOW(), NOW()),
('athlete.create', 'athlete', 'create', 'Register new athletes', NOW(), NOW()),
('athlete.edit', 'athlete', 'edit', 'Update athlete details', NOW(), NOW()),
('athlete.delete', 'athlete', 'delete', 'Archive or remove athletes', NOW(), NOW()),

-- Coaches
('coach.view', 'coach', 'view', 'View coach profiles and specialties', NOW(), NOW()),
('coach.manage', 'coach', 'manage', 'Manage coach profiles and assignments', NOW(), NOW()),

-- Teams
('team.view', 'team', 'view', 'View teams and rosters', NOW(), NOW()),
('team.manage', 'team', 'manage', 'Create and modify teams and rosters', NOW(), NOW()),

-- Training & Attendance
('training.view', 'training', 'view', 'View training sessions and camps', NOW(), NOW()),
('training.manage', 'training', 'manage', 'Schedule training sessions and camps', NOW(), NOW()),
('attendance.record', 'attendance', 'record', 'Mark training and match attendance', NOW(), NOW()),

-- Performance & Medical
('performance.view', 'performance', 'view', 'View performance metrics and evaluation', NOW(), NOW()),
('performance.manage', 'performance', 'manage', 'Record and update athlete performance', NOW(), NOW()),
('medical.view', 'medical', 'view', 'View medical records and clearances', NOW(), NOW()),
('medical.manage', 'medical', 'manage', 'Manage medical consultations and injury records', NOW(), NOW()),

-- Tournaments & Matches
('tournament.view', 'tournament', 'view', 'View tournaments and standings', NOW(), NOW()),
('tournament.manage', 'tournament', 'manage', 'Create and administer tournaments', NOW(), NOW()),
('fixture.manage', 'fixture', 'manage', 'Create fixtures and record match results', NOW(), NOW()),

-- Venues & Bookings
('venue.view', 'venue', 'view', 'View venues and facilities', NOW(), NOW()),
('venue.manage', 'venue', 'manage', 'Manage venues and maintenance', NOW(), NOW()),
('booking.create', 'booking', 'create', 'Book venue facilities', NOW(), NOW()),
('housekeeping.manage', 'housekeeping', 'manage', 'Manage housekeeping tasks', NOW(), NOW()),

-- HR & Payroll
('employee.view', 'employee', 'view', 'View staff and employee records', NOW(), NOW()),
('employee.manage', 'employee', 'manage', 'Manage employee records and documents', NOW(), NOW()),
('leave.request', 'leave', 'request', 'Submit leave applications', NOW(), NOW()),
('leave.approve', 'leave', 'approve', 'Approve or reject leave applications', NOW(), NOW()),
('payroll.manage', 'payroll', 'manage', 'Manage salary structures and process payroll', NOW(), NOW()),

-- Inventory & Purchases
('inventory.view', 'inventory', 'view', 'View inventory levels and equipment', NOW(), NOW()),
('inventory.manage', 'inventory', 'manage', 'Manage inventory stock and equipment assignments', NOW(), NOW()),
('purchase.manage', 'purchase', 'manage', 'Create purchase orders and manage vendor invoices', NOW(), NOW()),

-- Finance & Budgets
('finance.view', 'finance', 'view', 'View financial budgets and reports', NOW(), NOW()),
('finance.manage', 'finance', 'manage', 'Manage budgets, record expenses and income', NOW(), NOW()),

-- Events & Transport
('event.view', 'event', 'view', 'View events and school activities', NOW(), NOW()),
('event.manage', 'event', 'manage', 'Organize events, transport, and accommodation', NOW(), NOW()),

-- Reports & Dashboard
('report.view', 'report', 'view', 'Generate and view analytical reports', NOW(), NOW())
ON DUPLICATE KEY UPDATE 
    description = VALUES(description),
    updated_at = NOW();

-- Assign Full Permissions to Super Admin (Role 1) and Sports Administrator (Role 2)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions;

-- Role 3: HR & Finance
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE module IN ('employee', 'leave', 'payroll', 'finance', 'report');

-- Role 4: Coach
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE name IN (
    'athlete.view', 'team.view', 'training.view', 'training.manage', 
    'attendance.record', 'performance.view', 'performance.manage', 
    'tournament.view', 'fixture.manage', 'leave.request'
);

-- Role 5: Athlete
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 5, id FROM permissions WHERE name IN (
    'team.view', 'training.view', 'performance.view', 'tournament.view', 'leave.request'
);

-- Role 6: Venue & Tournament Manager
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 6, id FROM permissions WHERE module IN ('venue', 'booking', 'housekeeping', 'tournament', 'fixture');

-- Role 7: Inventory Manager
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 7, id FROM permissions WHERE module IN ('inventory', 'purchase');
