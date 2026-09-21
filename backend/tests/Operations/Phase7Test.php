<?php

namespace Tests\Operations;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as DB;
use App\Models\Vehicle;
use App\Services\Operations\VehicleService;
use App\Services\Operations\TransportTripService;

class Phase7Test extends TestCase
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

    public function test_vehicle_and_trip_logic()
    {
        $vehicleService = new VehicleService();
        $tripService = new TransportTripService();

        $vehicle = $vehicleService->createVehicle(1, [
            'vehicle_number' => 'AB12CD3456',
            'vehicle_type' => 'Bus',
            'capacity' => null,
            'status' => 'available',
            'registration_expiry_date' => '2026-12-31',
            'insurance_expiry_date' => '2026-12-31'
        ]);

        $this->assertNotNull($vehicle->id);

        $trip = $tripService->createTrip(1, [
            'vehicle_id' => $vehicle->id,
            'trip_date' => '2026-10-15',
            'origin' => 'Campus',
            'destination' => 'Stadium',
            'purpose' => 'Match'
        ]);

        $this->assertNotNull($trip->id);

        try {
            $tripService->addPassenger(1, $trip->id, [
                'passenger_name' => 'John Doe'
            ]);
            $this->fail('Expected exception for null vehicle capacity');
        } catch (\Exception $e) {
            $this->assertEquals(409, $e->getCode());
        }

        $vehicle->capacity = 1;
        $vehicle->save();

        $passenger = $tripService->addPassenger(1, $trip->id, [
            'athlete_id' => 100
        ]);
        $this->assertNotNull($passenger->id);

        try {
            $tripService->addPassenger(1, $trip->id, [
                'athlete_id' => 100
            ]);
            $this->fail('Expected exception for duplicate passenger');
        } catch (\Exception $e) {
            $this->assertEquals(409, $e->getCode());
        }

        try {
            $tripService->addPassenger(1, $trip->id, [
                'passenger_name' => 'Jane Doe'
            ]);
            $this->fail('Expected exception for capacity exceeded');
        } catch (\Exception $e) {
            $this->assertEquals(409, $e->getCode());
        }
    }

    public function test_overlap_logic()
    {
        $vehicleService = new VehicleService();
        $tripService = new TransportTripService();

        $vehicle = $vehicleService->createVehicle(1, [
            'vehicle_number' => 'XY98ZZ1111',
            'vehicle_type' => 'Van',
            'status' => 'available',
            'registration_expiry_date' => '2026-12-31',
            'insurance_expiry_date' => '2026-12-31'
        ]);

        $trip1 = $tripService->createTrip(1, [
            'vehicle_id' => $vehicle->id,
            'trip_date' => '2026-11-01',
            'origin' => 'Campus',
            'destination' => 'Stadium',
            'purpose' => 'Match'
        ]);

        try {
            $tripService->createTrip(1, [
                'vehicle_id' => $vehicle->id,
                'trip_date' => '2026-11-01',
                'origin' => 'Campus',
                'destination' => 'Stadium',
                'purpose' => 'Match 2'
            ]);
            $this->fail('Expected exception for overlap on same date');
        } catch (\Exception $e) {
            $this->assertEquals(409, $e->getCode());
        }
    }
}
