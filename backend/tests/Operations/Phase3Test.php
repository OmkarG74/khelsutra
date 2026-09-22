<?php

namespace Tests\Operations;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as DB;
use App\Models\Venue;
use App\Models\Facility;
use App\Models\VenueBooking;
use App\Services\Operations\VenueService;
use App\Services\Operations\FacilityService;
use Exception;

class Phase3Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::beginTransaction();
        
        DB::table('organizations')->insertOrIgnore([
            ['id' => 1, 'name' => 'Org 1']
        ]);
        if (!defined('CURRENT_ORGANIZATION_ID')) {
            define('CURRENT_ORGANIZATION_ID', 1);
        }
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        DB::statement("SET FOREIGN_KEY_CHECKS=1;");
        parent::tearDown();
    }

    public function test_venue_crud()
    {
        $service = new VenueService();
        $venue = $service->create(1, [
            'name' => 'Main Stadium',
            'venue_type' => 'Stadium',
            'capacity' => 50000
        ]);

        $this->assertNotNull($venue->id);
        $this->assertEquals('Main Stadium', $venue->name);
        $this->assertNotNull($venue->venue_code); // generated

        $venue = $service->update(1, $venue->id, [
            'capacity' => 55000
        ]);
        $this->assertEquals(55000, $venue->capacity);

        $service->delete(1, $venue->id);
        $this->assertNull(Venue::find($venue->id));
    }

    public function test_facility_crud()
    {
        $vService = new VenueService();
        $venue = $vService->create(1, ['name' => 'Complex']);

        $fService = new FacilityService();
        $facility = $fService->create(1, $venue->id, [
            'name' => 'Court 1',
            'facility_type' => 'Indoor'
        ]);

        $this->assertNotNull($facility->id);
        $this->assertEquals($venue->id, $facility->venue_id);

        $facility = $fService->update(1, $venue->id, $facility->id, ['capacity' => 200]);
        $this->assertEquals(200, $facility->capacity);

        $fService->delete(1, $venue->id, $facility->id);
        $this->assertNull(Facility::find($facility->id));
    }

    public function test_venue_deletion_guarded_when_active_bookings_exist()
    {
        $service = new VenueService();
        $venue = $service->create(1, ['name' => 'Booked Venue']);

        VenueBooking::create([
            'organization_id' => 1,
            'venue_id' => $venue->id,
            'booked_by_user_id' => 1,
            'purpose' => 'Test',
            'booking_reference' => 'BK-1',
            'booking_date' => date('Y-m-d', strtotime('+1 day')),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'approved'
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(409);
        
        $service->delete(1, $venue->id);
    }
}
