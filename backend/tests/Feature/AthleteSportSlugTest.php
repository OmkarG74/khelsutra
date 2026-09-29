<?php

namespace Tests\Feature;

use App\Services\Sport\SportService;

class AthleteSportSlugTest
{
    public function testSportSlugResolution(): bool
    {
        $service = new SportService();

        // 1. Resolve known sport
        $cricketId = $service->resolveSportId('cricket');
        if (!is_int($cricketId) || $cricketId <= 0) {
            return false;
        }

        // 2. Resolve case-insensitive name
        $footballId = $service->resolveSportId('Football');
        if (!is_int($footballId) || $footballId <= 0) {
            return false;
        }

        // 3. Resolve numeric ID directly
        $resolvedNumeric = $service->resolveSportId($cricketId);
        if ($resolvedNumeric !== $cricketId) {
            return false;
        }

        // 4. Resolve catalog list
        $catalog = $service->getSportsCatalog();
        if (empty($catalog) || !is_array($catalog)) {
            return false;
        }

        return true;
    }
}
