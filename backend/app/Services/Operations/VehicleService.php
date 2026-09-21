<?php

namespace App\Services\Operations;

use App\Models\Vehicle;
use Illuminate\Database\Capsule\Manager as DB;
use Exception;

class VehicleService
{
    public function createVehicle(int $orgId, array $data): Vehicle
    {
        return DB::transaction(function () use ($orgId, $data) {
            $data['organization_id'] = $orgId;
            $data['status'] = $data['status'] ?? 'available';
            
            return Vehicle::create($data);
        });
    }

    public function deleteVehicle(int $orgId, int $id): bool
    {
        return DB::transaction(function () use ($orgId, $id) {
            $vehicle = Vehicle::where('organization_id', $orgId)->lockForUpdate()->findOrFail($id);

            // check active trips
            $activeTrips = DB::table('transport_trips')
                ->where('organization_id', $orgId)
                ->where('vehicle_id', $id)
                ->whereIn('status', ['planned', 'in_progress'])
                ->exists();

            if ($activeTrips) {
                throw new Exception("Cannot delete vehicle with active trips.", 409);
            }

            return $vehicle->delete();
        });
    }
}
