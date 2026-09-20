<?php

namespace Tests\Feature\Api;

use App\Services\Athlete\AthleteService;

class AthleteTest
{
    public function testListAthletesReturnsArray(): bool
    {
        $service = new AthleteService();
        $athletes = $service->listAthletes(1);
        return is_array($athletes);
    }
}
