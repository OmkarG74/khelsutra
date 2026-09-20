-- KhelSutra Reference Data Seeder

USE khelsutra;

-- Tournament Levels
INSERT INTO tournament_levels (id, name, description, status) VALUES
(1, 'School', 'Inter-school or intra-school competitions', 'active'),
(2, 'District', 'District level championships', 'active'),
(3, 'State', 'State level tournaments and trials', 'active'),
(4, 'National', 'National federation championships', 'active'),
(5, 'International', 'International invitationals and series', 'active')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Tournament Formats
INSERT INTO tournament_formats (id, name, description, status) VALUES
(1, 'Knockout', 'Single elimination tournament bracket', 'active'),
(2, 'League', 'Double round-robin or single league table', 'active'),
(3, 'Round Robin', 'All teams play every other team in group', 'active'),
(4, 'Group + Knockout', 'Group stage followed by playoff knockouts', 'active')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Base Global Sports
INSERT INTO sports (id, organization_id, name, code, description, status, is_global, created_at, updated_at) VALUES
(1, NULL, 'Football', 'FTB', 'Association Football', 'active', TRUE, NOW(), NOW()),
(2, NULL, 'Cricket', 'CRK', 'Cricket (T20, One-day, Multi-day)', 'active', TRUE, NOW(), NOW()),
(3, NULL, 'Basketball', 'BSK', '5v5 and 3x3 Basketball', 'active', TRUE, NOW(), NOW()),
(4, NULL, 'Badminton', 'BDM', 'Singles and Doubles Badminton', 'active', TRUE, NOW(), NOW()),
(5, NULL, 'Athletics', 'ATH', 'Track and Field disciplines', 'active', TRUE, NOW(), NOW()),
(6, NULL, 'Swimming', 'SWM', 'Competitive Aquatics and Swimming', 'active', TRUE, NOW(), NOW())
ON DUPLICATE KEY UPDATE description = VALUES(description);
