<?php

namespace App\Services\Operations;

use App\Models\Vehicle;
use App\Models\TransportTrip;
use Illuminate\Database\Capsule\Manager as DB;
use Exception;

class TransportAssignmentService
{
    public function planAssignment(int $orgId, array $params): array
    {
        $travellers = $params['travellers'] ?? 0;
        $seatBuffer = $params['seat_buffer'] ?? 0;
        $tripDate = $params['trip_date'];
        
        $N = $travellers + $seatBuffer;
        
        if ($N <= 0) {
            throw new Exception("Number of passengers must be greater than zero.", 400);
        }

        // Get available vehicles
        // A vehicle is available if it's 'available' or 'assigned', has a capacity, and doesn't have overlapping trips
        // (For simplicity, we check if there are no overlapping trips on that exact day)
        $availableVehicles = Vehicle::where('organization_id', $orgId)
            ->whereIn('status', ['available', 'assigned'])
            ->whereNotNull('capacity')
            ->where(function ($query) use ($tripDate) {
                $query->whereNull('registration_expiry_date')
                      ->orWhere('registration_expiry_date', '>=', $tripDate);
            })
            ->where(function ($query) use ($tripDate) {
                $query->whereNull('insurance_expiry_date')
                      ->orWhere('insurance_expiry_date', '>=', $tripDate);
            })
            ->whereNotIn('id', function($query) use ($orgId, $tripDate) {
                $query->select('vehicle_id')
                      ->from('transport_trips')
                      ->where('organization_id', $orgId)
                      ->where('trip_date', $tripDate)
                      ->whereIn('status', ['planned', 'in_progress'])
                      ->whereNotNull('vehicle_id');
            })
            // We use lockForUpdate in create, but for planning we just select
            ->orderBy('capacity', 'asc')
            ->get();

        if ($availableVehicles->isEmpty()) {
            return [
                'success' => false,
                'shortfall' => $N,
                'assigned_vehicles' => [],
                'message' => 'No vehicles available.'
            ];
        }

        $vehicles = $availableVehicles->toArray();
        
        // Strategy 1: Find a single vehicle that fits
        foreach ($vehicles as $vehicle) {
            if ($vehicle['capacity'] >= $N) {
                return [
                    'success' => true,
                    'shortfall' => 0,
                    'assigned_vehicles' => [$vehicle],
                    'message' => 'Single vehicle fits perfectly.'
                ];
            }
        }

        // Strategy 2: DP or greedy search for combination
        // To minimize vehicles, and then excess seats, we can sort by capacity desc
        usort($vehicles, function($a, $b) {
            return $b['capacity'] <=> $a['capacity'];
        });

        $assigned = [];
        $remaining = $N;
        
        // DP approach for exact match or smallest excess is better, 
        // but greedy is simpler: take largest vehicle until remaining is small enough to fit a single smaller vehicle
        foreach ($vehicles as $vehicle) {
            if ($remaining <= 0) break;
            
            // If remaining can fit in one of the smaller available vehicles perfectly, pick that one
            // We find the smallest vehicle that can fit remaining
            $bestSmall = null;
            foreach (array_reverse($vehicles) as $v) {
                if (!in_array($v, $assigned) && $v['capacity'] >= $remaining) {
                    $bestSmall = $v;
                    break;
                }
            }
            if ($bestSmall) {
                $assigned[] = $bestSmall;
                $remaining -= $bestSmall['capacity'];
                break;
            }
            
            // Otherwise, take the largest
            if (!in_array($vehicle, $assigned)) {
                $assigned[] = $vehicle;
                $remaining -= $vehicle['capacity'];
            }
        }
        
        if ($remaining > 0) {
            return [
                'success' => false,
                'shortfall' => $remaining,
                'assigned_vehicles' => $assigned,
                'message' => 'Not enough total capacity.'
            ];
        }

        return [
            'success' => true,
            'shortfall' => 0,
            'assigned_vehicles' => $assigned,
            'message' => 'Combination of vehicles found.'
        ];
    }
}
