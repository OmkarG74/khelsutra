<?php

namespace Tests\Feature\Api;

use App\Services\Team\TeamService;

class TeamTest
{
    public function testListTeamsReturnsArray(): bool
    {
        $service = new TeamService();
        return is_array($service->listTeams(1));
    }
}
