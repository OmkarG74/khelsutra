<?php

namespace Tests\Feature\Api;

use App\Services\Tournament\TournamentService;

class TournamentTest
{
    public function testListTournamentsReturnsArray(): bool
    {
        $service = new TournamentService();
        return is_array($service->listTournaments(1));
    }
}
