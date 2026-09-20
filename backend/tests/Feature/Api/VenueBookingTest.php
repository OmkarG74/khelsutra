<?php

namespace Tests\Feature\Api;

use App\Services\Venue\VenueService;

class VenueBookingTest
{
    public function testListVenuesReturnsArray(): bool
    {
        $service = new VenueService();
        return is_array($service->listVenues(1));
    }
}
