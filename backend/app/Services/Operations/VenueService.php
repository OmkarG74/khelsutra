<?php

namespace App\Services\Operations;

use App\Models\Venue;
use App\Models\Facility;
use App\Models\VenueBooking;
use Illuminate\Database\Capsule\Manager as DB;
use Exception;

class VenueService
{
    /**
     * Create a new venue
     */
    public function create(int $orgId, array $data): Venue
    {
        return DB::transaction(function () use ($orgId, $data) {
            $data['organization_id'] = $orgId;
            if (empty($data['venue_code'])) {
                $data['venue_code'] = ReferenceGenerator::generate('VN', 'venues', 'venue_code', $orgId);
            }
            if (empty($data['status'])) {
                $data['status'] = 'active';
            }
            if (empty($data['country'])) {
                $data['country'] = 'India';
            }
            
            $venue = Venue::create($data);

            if (!empty($venue->latitude) && !empty($venue->longitude)) {
                $trackingService = new VehicleTrackingService();
                $trackingService->syncGeofence($orgId, [
                    'name' => $venue->name,
                    'latitude' => $venue->latitude,
                    'longitude' => $venue->longitude,
                    'radius_meters' => 100
                ], $venue->id);
            }

            return $venue;
        });
    }

    /**
     * Update a venue
     */
    public function update(int $orgId, int $venueId, array $data): Venue
    {
        return DB::transaction(function () use ($orgId, $venueId, $data) {
            $venue = Venue::where('organization_id', $orgId)->findOrFail($venueId);
            $venue->update($data);

            if (!empty($venue->latitude) && !empty($venue->longitude)) {
                $trackingService = new VehicleTrackingService();
                $trackingService->syncGeofence($orgId, [
                    'name' => $venue->name,
                    'latitude' => $venue->latitude,
                    'longitude' => $venue->longitude,
                    'radius_meters' => 100
                ], $venue->id);
            }

            return $venue;
        });
    }

    /**
     * Delete a venue (soft delete if no active bookings)
     */
    public function delete(int $orgId, int $venueId): void
    {
        DB::transaction(function () use ($orgId, $venueId) {
            $venue = Venue::where('organization_id', $orgId)->findOrFail($venueId);

            // Check for active bookings
            $hasActiveBookings = VenueBooking::where('organization_id', $orgId)
                ->where('venue_id', $venueId)
                ->whereIn('status', ['pending', 'approved'])
                ->where('booking_date', '>=', date('Y-m-d'))
                ->exists();

            if ($hasActiveBookings) {
                throw new Exception("Cannot delete venue with future active bookings.", 409);
            }

            $venue->delete();
        });
    }
}
