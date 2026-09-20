<?php

namespace Tests\Feature;

use App\Services\Organization\OrganizationSettingsService;

class OrganizationSettingsTest
{
    /**
     * Test typed settings storage and retrieval (Section 44)
     */
    public function testTypedSettingsCasting(): bool
    {
        $service = new OrganizationSettingsService();

        // 1. Integer
        $service->setSetting(1, 'test.max_participants', 25, 'integer');
        $intVal = $service->getSetting(1, 'test.max_participants');

        // 2. Boolean
        $service->setSetting(1, 'test.require_medical_cert', true, 'boolean');
        $boolVal = $service->getSetting(1, 'test.require_medical_cert');

        // 3. Decimal
        $service->setSetting(1, 'test.min_attendance_rate', '75.50', 'decimal');
        $decVal = $service->getSetting(1, 'test.min_attendance_rate');

        // 4. JSON
        $service->setSetting(1, 'test.sports_offered', ['badminton', 'cricket', 'football'], 'json');
        $jsonVal = $service->getSetting(1, 'test.sports_offered');

        return (
            $intVal === 25 &&
            $boolVal === true &&
            abs((float)$decVal - 75.50) < 0.001 &&
            is_array($jsonVal) &&
            in_array('badminton', $jsonVal)
        );
    }
}
