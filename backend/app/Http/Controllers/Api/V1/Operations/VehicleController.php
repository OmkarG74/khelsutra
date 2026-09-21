<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\BaseFormRequest;
use App\Services\Operations\VehicleService;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Exception;

class VehicleController extends Controller
{
    protected $service;

    public function __construct(VehicleService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $orgId = $request->attributes->get('organization_id');
        $vehicles = Vehicle::where('organization_id', $orgId)->paginate(15);
        return response()->json($vehicles);
    }

    public function store(BaseFormRequest $request)
    {
        $orgId = $request->attributes->get('organization_id');
        $data = $request->validate([
            'vehicle_number' => 'required|string',
            'vehicle_type' => 'required|string',
            'capacity' => 'nullable|integer',
            'driver_employee_id' => 'nullable|integer',
            'status' => 'nullable|string'
        ]);

        try {
            $vehicle = $this->service->createVehicle($orgId, $data);
            return response()->json($vehicle, 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
