<?php

namespace Tests\Unit\Services;

use App\Services\Athlete\AthleteService;

class AthleteServiceTest
{
    public function testAthleteServiceInstantiates(): bool
    {
        $service = new AthleteService();
        return ($service instanceof AthleteService);
    }
}
