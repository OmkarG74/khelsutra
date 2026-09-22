<?php

namespace App\Services\Operations;

use App\Models\VehiclePosition;
use App\Models\Geofence;
use App\Models\Vehicle;
use App\Models\TransportTrip;
use Illuminate\Database\Capsule\Manager as DB;
use Exception;

class VehicleTrackingService
{
    /**
     * Record a new GPS position for a vehicle.
     */
    public function recordPosition(int $orgId, array $data): VehiclePosition
    {
        $vehicle = Vehicle::where('organization_id', $orgId)->findOrFail($data['vehicle_id']);

        $data['organization_id'] = $orgId;
        $data['recorded_at'] = $data['recorded_at'] ?? date('Y-m-d H:i:s');
        $data['created_at'] = date('Y-m-d H:i:s');

        return VehiclePosition::create($data);
    }

    /**
     * Get the latest known position for a vehicle.
     */
    public function getLivePosition(int $orgId, int $vehicleId): ?VehiclePosition
    {
        return VehiclePosition::where('organization_id', $orgId)
            ->where('vehicle_id', $vehicleId)
            ->orderBy('recorded_at', 'desc')
            ->first();
    }

    /**
     * Get the route history for a given transport trip.
     */
    public function getRouteHistoryForTrip(int $orgId, int $tripId): array
    {
        $trip = TransportTrip::where('organization_id', $orgId)->findOrFail($tripId);
        if (!$trip->vehicle_id) {
            throw new Exception("No vehicle assigned to this trip.", 400);
        }

        $date = $trip->trip_date;
        $from = $date . ' ' . ($trip->departure_time ?: '00:00:00');
        $to = $date . ' ' . ($trip->return_time ?: '23:59:59');

        return VehiclePosition::where('organization_id', $orgId)
            ->where('vehicle_id', $trip->vehicle_id)
            ->whereBetween('recorded_at', [$from, $to])
            ->orderBy('recorded_at', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Sync a geofence (e.g. for a venue).
     */
    public function syncGeofence(int $orgId, array $data, ?int $venueId = null): Geofence
    {
        $data['organization_id'] = $orgId;
        if ($venueId) {
            $data['venue_id'] = $venueId;
            $geofence = Geofence::where('organization_id', $orgId)
                ->where('venue_id', $venueId)
                ->first();
                
            if ($geofence) {
                $geofence->update($data);
                return $geofence;
            }
        }
        
        return Geofence::create($data);
    }

    /**
     * Check if a vehicle is currently inside any geofence.
     */
    public function checkGeofenceBreach(int $orgId, int $vehicleId): array
    {
        $position = $this->getLivePosition($orgId, $vehicleId);
        if (!$position) {
            return [];
        }

        $geofences = Geofence::where('organization_id', $orgId)->get();
        $inside = [];

        foreach ($geofences as $fence) {
            $distance = $this->calculateDistance(
                $position->latitude, $position->longitude,
                $fence->latitude, $fence->longitude
            );

            if ($distance <= $fence->radius_meters) {
                $inside[] = $fence->toArray();
            }
        }

        return $inside;
    }

    /**
     * Haversine formula to calculate distance in meters.
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
            
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
