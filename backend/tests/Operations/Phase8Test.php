<?php

namespace Tests\Operations;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as DB;
use App\Models\Accommodation;
use App\Services\Operations\AccommodationService;
use App\Services\Operations\RoomAllocationService;

class Phase8Test extends TestCase
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
        parent::tearDown();
    }

    public function test_room_allocation_logic()
    {
        $accService = new AccommodationService();
        $allocService = new RoomAllocationService();

        $acc = $accService->createAccommodation(1, [
            'name' => 'Campus Hostel',
            'status' => 'active'
        ]);
        $this->assertNotNull($acc->id);

        $room = $accService->createRoom(1, $acc->id, [
            'room_number' => '101',
            'capacity' => 2,
            'status' => 'available'
        ]);
        $this->assertNotNull($room->id);

        $alloc1 = $allocService->allocateRoom(1, [
            'accommodation_id' => $acc->id,
            'room_id' => $room->id,
            'check_in_date' => '2026-11-01',
            'check_out_date' => '2026-11-10',
            'athlete_id' => 1
        ]);
        $this->assertNotNull($alloc1->id);

        // Person already allocated overlapping
        try {
            $allocService->allocateRoom(1, [
                'accommodation_id' => $acc->id,
                'room_id' => $room->id,
                'check_in_date' => '2026-11-05',
                'check_out_date' => '2026-11-15',
                'athlete_id' => 1
            ]);
            $this->fail('Expected exception for same person overlapping allocation');
        } catch (\Exception $e) {
            $this->assertEquals(409, $e->getCode());
        }

        // Allocate second person
        $alloc2 = $allocService->allocateRoom(1, [
            'accommodation_id' => $acc->id,
            'room_id' => $room->id,
            'check_in_date' => '2026-11-05',
            'check_out_date' => '2026-11-15',
            'athlete_id' => 2
        ]);
        $this->assertNotNull($alloc2->id);

        // Capacity exceeded
        try {
            $allocService->allocateRoom(1, [
                'accommodation_id' => $acc->id,
                'room_id' => $room->id,
                'check_in_date' => '2026-11-08',
                'check_out_date' => '2026-11-12',
                'athlete_id' => 3
            ]);
            $this->fail('Expected exception for capacity exceeded');
        } catch (\Exception $e) {
            $this->assertEquals(409, $e->getCode());
        }
    }
}
