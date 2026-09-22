<?php

namespace Tests\Operations;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as DB;
use App\Models\Venue;
use App\Models\Facility;
use App\Models\VenueBooking;
use App\Services\Operations\VenueService;
use App\Services\Operations\FacilityService;
use App\Services\Operations\VenueBookingService;
use Exception;

class Phase4Test extends TestCase
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

    public function test_booking_conflict_logic()
    {
        $vService = new VenueService();
        $venue = $vService->create(1, ['name' => 'Booking Venue Test']);

        $fService = new FacilityService();
        $facility1 = $fService->create(1, $venue->id, ['name' => 'F1']);
        $facility2 = $fService->create(1, $venue->id, ['name' => 'F2']);

        $bService = new VenueBookingService();
        
        // 1. Create a whole-venue booking 10am-12pm
        $booking1 = $bService->createBooking(1, [
            'venue_id' => $venue->id,
            'booked_by_user_id' => 1,
            'booking_date' => date('Y-m-d', strtotime('+2 days')),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00'
        ], true);

        $this->assertEquals('approved', $booking1->status);

        // 2. Try to book Facility 1 overlapping (11am-1pm) -> should conflict with whole venue
        try {
            $bService->createBooking(1, [
                'venue_id' => $venue->id,
                'facility_id' => $facility1->id,
                'booked_by_user_id' => 1,
                'booking_date' => date('Y-m-d', strtotime('+2 days')),
                'start_time' => '11:00:00',
                'end_time' => '13:00:00'
            ], true);
            $this->fail('Expected conflict with whole venue booking');
        } catch (Exception $e) {
            $this->assertEquals(409, $e->getCode());
        }

        // 3. Try to book Facility 1 adjacent (12pm-2pm) -> should succeed (half-open)
        $booking2 = $bService->createBooking(1, [
            'venue_id' => $venue->id,
            'facility_id' => $facility1->id,
            'booked_by_user_id' => 1,
            'booking_date' => date('Y-m-d', strtotime('+2 days')),
            'start_time' => '12:00:00',
            'end_time' => '14:00:00'
        ], true);
        $this->assertNotNull($booking2->id);

        // 4. Try to book Facility 2 overlapping with Booking 2 (1pm-3pm) -> should succeed because different facility
        $booking3 = $bService->createBooking(1, [
            'venue_id' => $venue->id,
            'facility_id' => $facility2->id,
            'booked_by_user_id' => 1,
            'booking_date' => date('Y-m-d', strtotime('+2 days')),
            'start_time' => '13:00:00',
            'end_time' => '15:00:00'
        ], true);
        $this->assertNotNull($booking3->id);

        // 5. Cancel booking 2, then re-book it -> should succeed because cancelled bookings don't block
        $bService->cancelBooking(1, $booking2->id, 1, 'Test cancel');
        
        $booking4 = $bService->createBooking(1, [
            'venue_id' => $venue->id,
            'facility_id' => $facility1->id,
            'booked_by_user_id' => 1,
            'booking_date' => date('Y-m-d', strtotime('+2 days')),
            'start_time' => '12:00:00',
            'end_time' => '14:00:00'
        ], true);
        $this->assertNotNull($booking4->id);
    }
}
