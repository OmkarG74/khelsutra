USE khelsutra;

-- Demo Organization
INSERT INTO organizations (id, organization_code, name, legal_name, email, phone, city, state, country, status, plan_name, created_at, updated_at) VALUES
(1, 'ORG-DEMO', 'Apex Sports Academy', 'Apex Sports Foundation Ltd', 'contact@apexsports.org', '+919876543210', 'Pune', 'Maharashtra', 'India', 'active', 'Enterprise', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Demo Users
-- Password is 'SecretPassword123'
INSERT INTO users (id, uuid, username, email, password, first_name, last_name, phone, status, created_at, updated_at) VALUES
(1, UUID(), 'admin_demo', 'admin@khelsutra.com', '$2y$12$e0NZB7.O0gVv9bZ.hN8KTuE0t7N.e9W3oX1zM5yK9p7r8q0s1t2u3', 'Sports', 'Admin', '+919876543211', 'active', NOW(), NOW()),
(2, UUID(), 'coach_rajesh', 'coach@khelsutra.com', '$2y$12$e0NZB7.O0gVv9bZ.hN8KTuE0t7N.e9W3oX1zM5yK9p7r8q0s1t2u3', 'Rajesh', 'Sharma', '+919876543212', 'active', NOW(), NOW()),
(3, UUID(), 'athlete_aarav', 'athlete@khelsutra.com', '$2y$12$e0NZB7.O0gVv9bZ.hN8KTuE0t7N.e9W3oX1zM5yK9p7r8q0s1t2u3', 'Aarav', 'Patel', '+919876543213', 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE first_name = VALUES(first_name);

-- Organization User Pivot (Tenant memberships)
INSERT IGNORE INTO organization_users (organization_id, user_id, role_id, access_status, assigned_at, created_at, updated_at) VALUES
(1, 1, 2, 'active', NOW(), NOW(), NOW()), -- Sports Administrator
(1, 2, 4, 'active', NOW(), NOW(), NOW()), -- Coach
(1, 3, 5, 'active', NOW(), NOW(), NOW()); -- Athlete
