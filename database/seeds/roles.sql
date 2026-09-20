-- KhelSutra System Roles Seeder
-- System Roles fixed by the application specifications

USE khelsutra;

INSERT INTO roles (id, name, description, is_system_role, created_at, updated_at) VALUES
(1, 'Super Admin', 'Platform provider administrator with global multi-organization access', TRUE, NOW(), NOW()),
(2, 'Sports Administrator', 'Full administrative control over a single sports organization', TRUE, NOW(), NOW()),
(3, 'HR & Finance', 'Manages staff, coaches, payroll, leave, budgets, expenses, and accounts', TRUE, NOW(), NOW()),
(4, 'Coach', 'Manages assigned teams, athletes, training sessions, attendance, and match tactics', TRUE, NOW(), NOW()),
(5, 'Athlete', 'Accesses personal training schedules, performance stats, attendance, and leave requests', TRUE, NOW(), NOW()),
(6, 'Venue & Tournament Manager', 'Oversees ground/facility bookings, maintenance, housekeeping, and tournament operations', TRUE, NOW(), NOW()),
(7, 'Inventory Manager', 'Manages equipment, physical inventory, vendor relations, and purchase orders', TRUE, NOW(), NOW())
ON DUPLICATE KEY UPDATE 
    description = VALUES(description),
    updated_at = NOW();
