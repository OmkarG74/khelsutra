<?php

namespace Tests\Unit\Services;

use App\Services\Team\TeamService;
use App\Services\Tournament\TournamentService;
use App\Services\Venue\VenueService;

class TeamServiceTest
{
    public function testTeamServiceInstantiates(): bool
    {
        $service = new TeamService();
        return ($service instanceof TeamService);
    }
}
