<?php

namespace Database\Seeders;

use PDO;

class SportsAdminSeeder
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function run(): void
    {
        $orgId = 1; // Apex Sports Academy

        echo "Seeding Sports Administrator live foundation data for Organization #{$orgId}...\n";

        // 1. Seed Additional Athletes & Guardians
        $athletesData = [
            [
                'code' => 'ATH-2026-002',
                'fname' => 'Rohan',
                'lname' => 'Verma',
                'dob' => '2008-04-12',
                'gender' => 'male',
                'blood' => 'O+',
                'phone' => '+91 98201 11223',
                'email' => 'rohan.v@example.com',
                'sport_id' => 2, // Cricket
                'guardian_name' => 'Sanjay Verma',
                'guardian_relation' => 'Father',
                'guardian_phone' => '+91 98201 99887'
            ],
            [
                'code' => 'ATH-2026-003',
                'fname' => 'Ananya',
                'lname' => 'Desai',
                'dob' => '2009-08-25',
                'gender' => 'female',
                'blood' => 'B+',
                'phone' => '+91 98202 22334',
                'email' => 'ananya.d@example.com',
                'sport_id' => 4, // Badminton
                'guardian_name' => 'Kavita Desai',
                'guardian_relation' => 'Mother',
                'guardian_phone' => '+91 98202 88776'
            ],
            [
                'code' => 'ATH-2026-004',
                'fname' => 'Kabir',
                'lname' => 'Singh',
                'dob' => '2007-11-14',
                'gender' => 'male',
                'blood' => 'AB+',
                'phone' => '+91 98203 33445',
                'email' => 'kabir.s@example.com',
                'sport_id' => 1, // Football
                'guardian_name' => 'Harpreet Singh',
                'guardian_relation' => 'Father',
                'guardian_phone' => '+91 98203 77665'
            ],
            [
                'code' => 'ATH-2026-005',
                'fname' => 'Sneha',
                'lname' => 'Patil',
                'dob' => '2008-01-30',
                'gender' => 'female',
                'blood' => 'A+',
                'phone' => '+91 98204 44556',
                'email' => 'sneha.p@example.com',
                'sport_id' => 5, // Athletics
                'guardian_name' => 'Mahesh Patil',
                'guardian_relation' => 'Father',
                'guardian_phone' => '+91 98204 66554'
            ],
        ];

        foreach ($athletesData as $ad) {
            $check = $this->pdo->prepare("SELECT id FROM athletes WHERE organization_id = :org AND (athlete_code = :code OR email = :email) LIMIT 1");
            $check->execute([':org' => $orgId, ':code' => $ad['code'], ':email' => $ad['email']]);
            $existingId = $check->fetchColumn();

            if (!$existingId) {
                $ins = $this->pdo->prepare("
                    INSERT INTO athletes (organization_id, athlete_code, first_name, last_name, date_of_birth, gender, blood_group, current_sport_id, phone, email, status, registration_date, joining_date, created_at, updated_at)
                    VALUES (:org, :code, :fname, :lname, :dob, :gender, :blood, :sport_id, :phone, :email, 'active', '2026-01-10', '2026-01-15', NOW(), NOW())
                ");
                $ins->execute([
                    ':org' => $orgId,
                    ':code' => $ad['code'],
                    ':fname' => $ad['fname'],
                    ':lname' => $ad['lname'],
                    ':dob' => $ad['dob'],
                    ':gender' => $ad['gender'],
                    ':blood' => $ad['blood'],
                    ':sport_id' => $ad['sport_id'],
                    ':phone' => $ad['phone'],
                    ':email' => $ad['email'],
                ]);
                $athId = (int)$this->pdo->lastInsertId();

                $gIns = $this->pdo->prepare("
                    INSERT INTO athlete_guardians (organization_id, athlete_id, full_name, relationship, phone, is_primary, is_emergency_contact, created_at, updated_at)
                    VALUES (:org, :ath, :name, :rel, :phone, 1, 1, NOW(), NOW())
                ");
                $gIns->execute([
                    ':org' => $orgId,
                    ':ath' => $athId,
                    ':name' => $ad['guardian_name'],
                    ':rel' => $ad['guardian_relation'],
                    ':phone' => $ad['guardian_phone'],
                ]);
            }
        }

        // 2. Seed Venues & Facilities
        $venuesData = [
            [
                'code' => 'VEN-APX-01',
                'name' => 'Apex Main Sports Complex',
                'type' => 'Outdoor Stadium Complex',
                'capacity' => 12000,
                'facilities' => [
                    ['name' => 'Main Football Pitch A', 'type' => 'Natural Turf Football Pitch', 'cap' => 8000],
                    ['name' => 'Cricket Oval Ground', 'type' => 'Turf Cricket Oval', 'cap' => 4000],
                    ['name' => 'Synthetic Athletics Track', 'type' => '8-Lane 400m Track', 'cap' => 2000],
                ]
            ],
            [
                'code' => 'VEN-APX-02',
                'name' => 'Apex Elite Indoor Arena',
                'type' => 'Indoor Sports Facility',
                'capacity' => 2500,
                'facilities' => [
                    ['name' => 'Badminton Court 1 (Championship)', 'type' => 'Wooden Sprung Court', 'cap' => 500],
                    ['name' => 'Badminton Court 2 (Training)', 'type' => 'Vinyl Mat Court', 'cap' => 300],
                    ['name' => 'High-Performance Conditioning Gym', 'type' => 'Gymnasium', 'cap' => 100],
                ]
            ]
        ];

        $venueMap = [];
        $facilityMap = [];
        foreach ($venuesData as $vd) {
            $stmt = $this->pdo->prepare("SELECT id FROM venues WHERE organization_id = :org AND venue_code = :code LIMIT 1");
            $stmt->execute([':org' => $orgId, ':code' => $vd['code']]);
            $venueId = $stmt->fetchColumn();

            if (!$venueId) {
                $vIns = $this->pdo->prepare("
                    INSERT INTO venues (organization_id, venue_code, name, venue_type, city, state, country, capacity, opening_time, closing_time, status, created_at, updated_at)
                    VALUES (:org, :code, :name, :type, 'Pune', 'Maharashtra', 'India', :cap, '06:00:00', '22:00:00', 'active', NOW(), NOW())
                ");
                $vIns->execute([
                    ':org' => $orgId,
                    ':code' => $vd['code'],
                    ':name' => $vd['name'],
                    ':type' => $vd['type'],
                    ':cap' => $vd['capacity'],
                ]);
                $venueId = (int)$this->pdo->lastInsertId();
            }
            $venueMap[$vd['code']] = $venueId;

            foreach ($vd['facilities'] as $fac) {
                $fCheck = $this->pdo->prepare("SELECT id FROM venue_facilities WHERE venue_id = :vid AND name = :name LIMIT 1");
                $fCheck->execute([':vid' => $venueId, ':name' => $fac['name']]);
                $facId = $fCheck->fetchColumn();
                if (!$facId) {
                    $fIns = $this->pdo->prepare("
                        INSERT INTO venue_facilities (organization_id, venue_id, name, facility_type, capacity, status, created_at, updated_at)
                        VALUES (:org, :vid, :name, :type, :cap, 'active', NOW(), NOW())
                    ");
                    $fIns->execute([
                        ':org' => $orgId,
                        ':vid' => $venueId,
                        ':name' => $fac['name'],
                        ':type' => $fac['type'],
                        ':cap' => $fac['cap'],
                    ]);
                    $facId = (int)$this->pdo->lastInsertId();
                }
                $facilityMap[$fac['name']] = $facId;
            }
        }

        // 3. Seed Teams
        $teamsData = [
            [
                'code' => 'TEAM-FB-01',
                'name' => 'Apex Titans Football U-18',
                'sport_id' => 1,
                'age_group' => 'U-18',
                'gender' => 'male',
                'coach_id' => 1, // Coach Rajesh
            ],
            [
                'code' => 'TEAM-CR-01',
                'name' => 'Apex Phoenix Cricket XI',
                'sport_id' => 2,
                'age_group' => 'U-19',
                'gender' => 'male',
                'coach_id' => 2, // Coach Amit
            ],
            [
                'code' => 'TEAM-BD-01',
                'name' => 'Apex Smashers Badminton Squad',
                'sport_id' => 4,
                'age_group' => 'Senior',
                'gender' => 'mixed',
                'coach_id' => 3, // Coach Priya
            ]
        ];

        $teamMap = [];
        foreach ($teamsData as $td) {
            $tCheck = $this->pdo->prepare("SELECT id FROM teams WHERE organization_id = :org AND team_code = :code LIMIT 1");
            $tCheck->execute([':org' => $orgId, ':code' => $td['code']]);
            $teamId = $tCheck->fetchColumn();

            if (!$teamId) {
                $tIns = $this->pdo->prepare("
                    INSERT INTO teams (organization_id, team_code, name, sport_id, age_group, gender, status, created_at, updated_at)
                    VALUES (:org, :code, :name, :sport, :age, :gender, 'active', NOW(), NOW())
                ");
                $tIns->execute([
                    ':org' => $orgId,
                    ':code' => $td['code'],
                    ':name' => $td['name'],
                    ':sport' => $td['sport_id'],
                    ':age' => $td['age_group'],
                    ':gender' => $td['gender'],
                ]);
                $teamId = (int)$this->pdo->lastInsertId();

                // Assign coach
                $tcIns = $this->pdo->prepare("
                    INSERT INTO team_coaches (organization_id, team_id, coach_id, coach_role, start_date, is_primary, created_at, updated_at)
                    VALUES (:org, :team, :coach, 'head_coach', '2026-01-01', 1, NOW(), NOW())
                ");
                $tcIns->execute([
                    ':org' => $orgId,
                    ':team' => $teamId,
                    ':coach' => $td['coach_id'],
                ]);
            }
            $teamMap[$td['code']] = $teamId;
        }

        // 4. Assign athletes to teams
        $athletes = $this->pdo->query("SELECT id, current_sport_id FROM athletes WHERE organization_id = 1 AND deleted_at IS NULL")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($athletes as $ath) {
            $targetTeamId = null;
            if ($ath['current_sport_id'] == 1 && isset($teamMap['TEAM-FB-01'])) $targetTeamId = $teamMap['TEAM-FB-01'];
            if ($ath['current_sport_id'] == 2 && isset($teamMap['TEAM-CR-01'])) $targetTeamId = $teamMap['TEAM-CR-01'];
            if ($ath['current_sport_id'] == 4 && isset($teamMap['TEAM-BD-01'])) $targetTeamId = $teamMap['TEAM-BD-01'];

            if ($targetTeamId) {
                $mCheck = $this->pdo->prepare("SELECT id FROM team_members WHERE team_id = :t AND athlete_id = :a LIMIT 1");
                $mCheck->execute([':t' => $targetTeamId, ':a' => $ath['id']]);
                if (!$mCheck->fetchColumn()) {
                    $tmIns = $this->pdo->prepare("
                        INSERT INTO team_members (organization_id, team_id, athlete_id, start_date, is_current, member_role, created_at, updated_at)
                        VALUES (:org, :team, :ath, '2026-01-15', 1, 'player', NOW(), NOW())
                    ");
                    $tmIns->execute([':org' => $orgId, ':team' => $targetTeamId, ':ath' => $ath['id']]);
                }
            }
        }

        // 5. Seed Tournaments, Fixtures & Matches
        $tournamentsData = [
            [
                'ref' => 'TRN-2026-01',
                'name' => 'Maharashtra Youth Football League 2026',
                'sport_id' => 1,
                'start' => date('Y-m-d', strtotime('-5 days')),
                'end' => date('Y-m-d', strtotime('+20 days')),
                'status' => 'ongoing',
            ],
            [
                'ref' => 'TRN-2026-02',
                'name' => 'All-India Inter-Academy Cricket Trophy 2026',
                'sport_id' => 2,
                'start' => date('Y-m-d', strtotime('+10 days')),
                'end' => date('Y-m-d', strtotime('+35 days')),
                'status' => 'registration_open',
            ]
        ];

        $tournMap = [];
        foreach ($tournamentsData as $trd) {
            $trCheck = $this->pdo->prepare("SELECT id FROM tournaments WHERE organization_id = :org AND tournament_reference = :ref LIMIT 1");
            $trCheck->execute([':org' => $orgId, ':ref' => $trd['ref']]);
            $tId = $trCheck->fetchColumn();

            if (!$tId) {
                $trIns = $this->pdo->prepare("
                    INSERT INTO tournaments (organization_id, tournament_reference, name, sport_id, start_date, end_date, city, state, status, created_at, updated_at)
                    VALUES (:org, :ref, :name, :sport, :start, :end, 'Pune', 'Maharashtra', :status, NOW(), NOW())
                ");
                $trIns->execute([
                    ':org' => $orgId,
                    ':ref' => $trd['ref'],
                    ':name' => $trd['name'],
                    ':sport' => $trd['sport_id'],
                    ':start' => $trd['start'],
                    ':end' => $trd['end'],
                    ':status' => $trd['status'],
                ]);
                $tId = (int)$this->pdo->lastInsertId();
            }
            $tournMap[$trd['ref']] = $tId;
        }

        // Add fixtures & matches if tournament 1 exists
        if (isset($tournMap['TRN-2026-01']) && isset($teamMap['TEAM-FB-01'])) {
            $t1Id = $tournMap['TRN-2026-01'];
            $team1 = $teamMap['TEAM-FB-01'];
            $pitchA = $facilityMap['Main Football Pitch A'] ?? null;
            $mainVenue = $venueMap['VEN-APX-01'] ?? null;

            // Check if fixture exists
            $fCheck = $this->pdo->prepare("SELECT id FROM fixtures WHERE tournament_id = :t LIMIT 1");
            $fCheck->execute([':t' => $t1Id]);
            if (!$fCheck->fetchColumn()) {
                $fIns = $this->pdo->prepare("
                    INSERT INTO fixtures (organization_id, tournament_id, fixture_reference, round_name, home_team_id, away_team_id, venue_id, facility_id, scheduled_date, scheduled_start_time, scheduled_end_time, status, created_at, updated_at)
                    VALUES (:org, :t, 'FIX-2026-01', 'Quarter Final', :home, :away, :venue, :fac, :sdate, '16:00:00', '18:00:00', 'scheduled', NOW(), NOW())
                ");
                $fIns->execute([
                    ':org' => $orgId,
                    ':t' => $t1Id,
                    ':home' => $team1,
                    ':away' => $team1, // In demo representation or self-club internal
                    ':venue' => $mainVenue,
                    ':fac' => $pitchA,
                    ':sdate' => date('Y-m-d', strtotime('+2 days')),
                ]);
                $fixId = (int)$this->pdo->lastInsertId();

                $mIns = $this->pdo->prepare("
                    INSERT INTO matches (organization_id, fixture_id, match_reference, status, created_at, updated_at)
                    VALUES (:org, :fix, 'MAT-2026-01', 'scheduled', NOW(), NOW())
                ");
                $mIns->execute([':org' => $orgId, ':fix' => $fixId]);
            }
        }

        // 6. Seed Training Sessions for Today and Upcoming
        if (isset($teamMap['TEAM-FB-01'])) {
            $sCheck = $this->pdo->prepare("SELECT id FROM training_sessions WHERE organization_id = :org AND training_date = CURDATE() LIMIT 1");
            $sCheck->execute([':org' => $orgId]);
            if (!$sCheck->fetchColumn()) {
                $sessIns = $this->pdo->prepare("
                    INSERT INTO training_sessions (organization_id, training_reference, team_id, coach_id, venue_id, facility_id, training_type, title, objectives, training_date, start_time, end_time, status, created_at, updated_at)
                    VALUES (:org, 'TRN-SESS-TODAY-01', :team, 1, :venue, :fac, 'Tactical & Tactical Drill', 'High-Press Striker Transition Practice', 'Improve box penetration and counter-pressing', CURDATE(), '07:30:00', '09:30:00', 'scheduled', NOW(), NOW())
                ");
                $sessIns->execute([
                    ':org' => $orgId,
                    ':team' => $teamMap['TEAM-FB-01'],
                    ':venue' => $venueMap['VEN-APX-01'] ?? null,
                    ':fac' => $facilityMap['Main Football Pitch A'] ?? null,
                ]);
                $sessId = (int)$this->pdo->lastInsertId();

                // Attendance records for athletes
                $ath1 = $athletes[0]['id'] ?? null;
                if ($ath1) {
                    $attIns = $this->pdo->prepare("
                        INSERT INTO training_attendance (organization_id, training_session_id, athlete_id, attendance_status, check_in_time, created_at, updated_at)
                        VALUES (:org, :sess, :ath, 'present', '07:25:00', NOW(), NOW())
                    ");
                    $attIns->execute([':org' => $orgId, ':sess' => $sessId, ':ath' => $ath1]);
                }
            }
        }

        // 7. Seed Venue Bookings
        if (isset($venueMap['VEN-APX-01'])) {
            $bCheck = $this->pdo->prepare("SELECT id FROM venue_bookings WHERE organization_id = :org LIMIT 1");
            $bCheck->execute([':org' => $orgId]);
            if (!$bCheck->fetchColumn()) {
                $bIns = $this->pdo->prepare("
                    INSERT INTO venue_bookings (organization_id, venue_id, facility_id, booking_reference, booked_by_user_id, purpose, booking_date, start_time, end_time, status, created_at, updated_at)
                    VALUES (:org, :v, :f, 'BKG-2026-001', 5, 'U-18 Academy League Preparation', CURDATE(), '15:00:00', '18:00:00', 'approved', NOW(), NOW())
                ");
                $bIns->execute([
                    ':org' => $orgId,
                    ':v' => $venueMap['VEN-APX-01'],
                    ':f' => $facilityMap['Main Football Pitch A'] ?? null,
                ]);
            }
        }

        // 8. Seed Inventory Categories & Items
        $invCats = ['Footballs & Balls', 'Cricket Equipment', 'Protective & Medical', 'Training Cones & Markers'];
        $catMap = [];
        foreach ($invCats as $cname) {
            $cCheck = $this->pdo->prepare("SELECT id FROM inventory_categories WHERE organization_id = :org AND name = :name LIMIT 1");
            $cCheck->execute([':org' => $orgId, ':name' => $cname]);
            $cId = $cCheck->fetchColumn();
            if (!$cId) {
                $cIns = $this->pdo->prepare("INSERT INTO inventory_categories (organization_id, name, status, created_at, updated_at) VALUES (:org, :name, 'active', NOW(), NOW())");
                $cIns->execute([':org' => $orgId, ':name' => $cname]);
                $cId = (int)$this->pdo->lastInsertId();
            }
            $catMap[$cname] = $cId;
        }

        $invItems = [
            [
                'code' => 'INV-FTB-001',
                'name' => 'FIFA Quality Pro Match Footballs (Size 5)',
                'cat' => 'Footballs & Balls',
                'qty' => 45,
                'min' => 20,
                'cost' => 2800.00,
            ],
            [
                'code' => 'INV-CRK-002',
                'name' => 'Kookaburra Turf Leather Cricket Balls',
                'cat' => 'Cricket Equipment',
                'qty' => 8, // Low stock!
                'min' => 25,
                'cost' => 1950.00,
            ],
            [
                'code' => 'INV-MED-003',
                'name' => 'Field Trauma First-Aid Response Kits',
                'cat' => 'Protective & Medical',
                'qty' => 2, // Low stock!
                'min' => 10,
                'cost' => 4500.00,
            ],
            [
                'code' => 'INV-CON-004',
                'name' => 'High-Visibility Agility Training Cones (Set of 50)',
                'cat' => 'Training Cones & Markers',
                'qty' => 120,
                'min' => 40,
                'cost' => 850.00,
            ],
        ];

        foreach ($invItems as $item) {
            $iCheck = $this->pdo->prepare("SELECT id FROM inventory_items WHERE organization_id = :org AND item_code = :code LIMIT 1");
            $iCheck->execute([':org' => $orgId, ':code' => $item['code']]);
            $iId = $iCheck->fetchColumn();
            if (!$iId && isset($catMap[$item['cat']])) {
                $iIns = $this->pdo->prepare("
                    INSERT INTO inventory_items (organization_id, category_id, item_code, item_name, quantity, minimum_stock_level, reorder_level, unit_cost, status, created_at, updated_at)
                    VALUES (:org, :cat, :code, :name, :qty, :min, :min, :cost, 'active', NOW(), NOW())
                ");
                $iIns->execute([
                    ':org' => $orgId,
                    ':cat' => $catMap[$item['cat']],
                    ':code' => $item['code'],
                    ':name' => $item['name'],
                    ':qty' => $item['qty'],
                    ':min' => $item['min'],
                    ':cost' => $item['cost'],
                ]);
                $iId = (int)$this->pdo->lastInsertId();

                // Insert opening stock transaction
                $txIns = $this->pdo->prepare("
                    INSERT INTO stock_transactions (organization_id, inventory_item_id, transaction_type, quantity, unit_cost, remarks, created_at)
                    VALUES (:org, :item, 'opening', :qty, :cost, 'Initial stock setup', NOW())
                ");
                $txIns->execute([
                    ':org' => $orgId,
                    ':item' => $iId,
                    ':qty' => $item['qty'],
                    ':cost' => $item['cost'],
                ]);
            }
        }

        echo "Sports Administrator live foundation data seeded successfully!\n";
    }
}

// If executed directly from CLI
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=khelsutra;charset=utf8mb4', 'root', '');
    $seeder = new SportsAdminSeeder($pdo);
    $seeder->run();
}
