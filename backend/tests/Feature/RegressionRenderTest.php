<?php

namespace Tests\Feature;

use App\Services\Leave\LeaveService;
use App\Services\Organization\OrganizationSettingsService;
use App\Services\Coach\CoachService;
use App\Services\Team\TeamService;
use App\Services\Tournament\TournamentService;
use App\Services\Venue\VenueService;
use App\Services\Inventory\InventoryService;
use App\Services\Athlete\AthleteService;
use App\Helpers\AuthContext;

require_once dirname(__DIR__, 2) . '/app/Helpers/AuthContext.php';

class RegressionRenderTest
{
    private array $rawDirectives = [
        '@php',
        '@foreach',
        '@endforeach',
        '@for',
        '@endfor',
        '@if',
        '@elseif',
        '@else',
        '@endif',
        '@include(',
        '@extends(',
        '@section(',
        '{{',
        '}}'
    ];

    private function setupSession(int $orgId = 1, int $userId = 2): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $_SESSION['auth'] = [
            'authenticated' => true,
            'user' => [
                'id' => $userId,
                'email' => 'sportsadmin@khelsutra.local',
                'first_name' => 'Sports',
                'last_name' => 'Admin',
                'role_id' => 2,
                'role' => 'Sports Administrator',
                'status' => 'active'
            ],
            'organization' => [
                'id' => $orgId,
                'name' => 'Apex Sports Academy',
                'code' => 'APEX'
            ]
        ];
    }

    private function renderView(string $viewPath, array $vars = []): string
    {
        $this->setupSession();
        extract($vars);
        ob_start();
        try {
            include $viewPath;
            return ob_get_clean() ?: '';
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    private function assertNoRawDirectives(string $content, string $pageName): bool
    {
        foreach ($this->rawDirectives as $directive) {
            if (str_contains($content, $directive)) {
                echo " [FAIL: {$pageName} contained raw directive: '{$directive}'] ";
                return false;
            }
        }
        return true;
    }

    /**
     * TEST 1: Coach page renders without raw Blade directives
     */
    public function testCoachPageRendersWithoutRawDirectives(): bool
    {
        $viewFile = dirname(__DIR__, 2) . '/resources/views/sports/coaches.blade.php';
        $output = $this->renderView($viewFile);
        return !empty($output) &&
               str_contains($output, 'Coaches') &&
               $this->assertNoRawDirectives($output, 'Coaches');
    }

    /**
     * TEST 2: Team page renders without raw Blade directives
     */
    public function testTeamPageRendersWithoutRawDirectives(): bool
    {
        $viewFile = dirname(__DIR__, 2) . '/resources/views/sports/teams.blade.php';
        $output = $this->renderView($viewFile);
        return !empty($output) &&
               str_contains($output, 'Teams') &&
               $this->assertNoRawDirectives($output, 'Teams');
    }

    /**
     * TEST 3: Tournament page renders without raw Blade directives
     */
    public function testTournamentPageRendersWithoutRawDirectives(): bool
    {
        $viewFile = dirname(__DIR__, 2) . '/resources/views/competitions/tournaments.blade.php';
        $output = $this->renderView($viewFile);
        return !empty($output) &&
               str_contains($output, 'Tournaments') &&
               $this->assertNoRawDirectives($output, 'Tournaments');
    }

    /**
     * TEST 4: Venue page renders without raw Blade directives
     */
    public function testVenuePageRendersWithoutRawDirectives(): bool
    {
        $viewFile = dirname(__DIR__, 2) . '/resources/views/venues/venues.blade.php';
        $output = $this->renderView($viewFile);
        return !empty($output) &&
               str_contains($output, 'Venues') &&
               $this->assertNoRawDirectives($output, 'Venues');
    }

    /**
     * TEST 5: Inventory page renders without raw Blade directives
     */
    public function testInventoryPageRendersWithoutRawDirectives(): bool
    {
        $viewFile = dirname(__DIR__, 2) . '/resources/views/inventory/inventory.blade.php';
        $output = $this->renderView($viewFile);
        return !empty($output) &&
               str_contains($output, 'Inventory') &&
               $this->assertNoRawDirectives($output, 'Inventory');
    }

    /**
     * TEST 6: Leave accepts missing/valid scalar status without error
     */
    public function testLeaveAcceptsValidScalarStatus(): bool
    {
        $service = new LeaveService();
        $resNull = $service->listLeaveRequests(1, null);
        $resPending = $service->listLeaveRequests(1, 'pending');
        $resEmpty = $service->listLeaveRequests(1, '');
        return is_array($resNull) && is_array($resPending) && is_array($resEmpty);
    }

    /**
     * TEST 7: Leave rejects/normalizes array status input without TypeError
     */
    public function testLeaveNormalizesArrayStatusInput(): bool
    {
        $service = new LeaveService();
        // Passing an arbitrary array simulating malformed query string or multi-select
        $resArray = $service->listLeaveRequests(1, ['status' => 'pending', 'foo' => 'bar']);
        $resDirectArray = $service->listLeaveRequests(1, ['malicious_array']);
        return is_array($resArray) && is_array($resDirectArray);
    }

    /**
     * TEST 8: Organisation Settings handles actual returned setting structure
     */
    public function testOrganizationSettingsHandlesReturnedSettingStructure(): bool
    {
        $service = new OrganizationSettingsService();
        $rows = $service->getSettingRows(1);
        $viewFile = dirname(__DIR__, 2) . '/resources/views/settings/organization.blade.php';

        // Render view with rows
        $output = $this->renderView($viewFile, ['settings' => $rows]);
        return !empty($output) &&
               str_contains($output, 'Tenant Key-Value Store') &&
               $this->assertNoRawDirectives($output, 'Organization Settings');
    }

    /**
     * TEST 9: Organisation Settings handles empty settings safely
     */
    public function testOrganizationSettingsHandlesEmptySettings(): bool
    {
        $viewFile = dirname(__DIR__, 2) . '/resources/views/settings/organization.blade.php';
        $output = $this->renderView($viewFile, ['settings' => []]);
        return !empty($output) &&
               str_contains($output, 'No settings defined yet.') &&
               $this->assertNoRawDirectives($output, 'Empty Organization Settings');
    }

    /**
     * TEST 10: Sports Administrator tenant context is dynamically resolved
     */
    public function testTenantContextIsDynamicallyResolved(): bool
    {
        $this->setupSession(101, 55);
        $orgId1 = AuthContext::getOrganizationId();
        $currOrg1 = \current_organization_id();
        $currUser1 = \current_user_id();

        if ($orgId1 !== 101 || $currOrg1 !== 101 || $currUser1 !== 55) {
            return false;
        }

        // Dynamically switch session to another organization
        $this->setupSession(202, 77);
        $orgId2 = AuthContext::getOrganizationId();
        $currOrg2 = \current_organization_id();
        $currUser2 = \current_user_id();

        // Restore default
        $this->setupSession(1, 2);

        return ($orgId2 === 202 && $currOrg2 === 202 && $currUser2 === 77);
    }

    /**
     * TEST 11: No organization ID is hardcoded in Sports Admin services
     */
    public function testNoOrganizationIdHardcodedInServices(): bool
    {
        $baseDir = dirname(__DIR__, 2) . '/app/Services';
        $serviceFiles = glob($baseDir . '/*/*.php');
        $forbiddenPatterns = [
            '/\$orgId\s*=\s*1\s*;/i',
            '/\$organizationId\s*=\s*1\s*;/i',
            '/[\'"]organization_id[\'"]\s*=>\s*1\b/i',
        ];

        foreach ($serviceFiles as $file) {
            $content = file_get_contents($file);
            foreach ($forbiddenPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    echo " [FAIL: Hardcoded org ID in {$file}] ";
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * TEST 12: Unauthorized organization access remains blocked
     */
    public function testUnauthorizedOrganizationAccessRemainsBlocked(): bool
    {
        $athleteService = new AthleteService();
        $teamService = new TeamService();
        $coachService = new CoachService();

        // Org 1 entities should NOT be accessible via Org 999 queries
        $athleteCross = $athleteService->getAthlete(999, 1);
        $teamCross = $teamService->getTeam(999, 1);
        $coachCross = $coachService->getCoach(999, 1);

        return ($athleteCross === null && $teamCross === null && $coachCross === null);
    }
}
