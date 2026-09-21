<?php

namespace App\Services\Operations;

use App\Models\VenueMaintenance;
use Illuminate\Database\Capsule\Manager as DB;

class MaintenanceService
{
    public function createTicket(int $orgId, array $data): VenueMaintenance
    {
        return DB::transaction(function () use ($orgId, $data) {
            $data['organization_id'] = $orgId;
            $data['maintenance_reference'] = ReferenceGenerator::generate('MN', 'venue_maintenance', 'maintenance_reference', $orgId);
            $data['status'] = 'reported';
            if (!isset($data['scheduled_date'])) $data['scheduled_date'] = date('Y-m-d');
            
            return VenueMaintenance::create($data);
        });
    }

    public function updateTicket(int $orgId, int $id, array $data): VenueMaintenance
    {
        return DB::transaction(function () use ($orgId, $id, $data) {
            $ticket = VenueMaintenance::where('organization_id', $orgId)->findOrFail($id);
            $ticket->update($data);
            return $ticket;
        });
    }
}
