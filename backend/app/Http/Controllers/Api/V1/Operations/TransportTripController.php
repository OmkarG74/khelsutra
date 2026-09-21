<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\BaseFormRequest;
use App\Services\Operations\TransportTripService;
use App\Models\TransportTrip;
use Illuminate\Http\Request;
use Exception;

class TransportTripController extends Controller
{
    protected $service;

    public function __construct(TransportTripService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $orgId = $request->attributes->get('organization_id');
        $trips = TransportTrip::where('organization_id', $orgId)->paginate(15);
        return response()->json($trips);
    }

    public function store(BaseFormRequest $request)
    {
        $orgId = $request->attributes->get('organization_id');
        $data = $request->validate([
            'vehicle_id' => 'required|integer',
            'driver_employee_id' => 'nullable|integer',
            'trip_date' => 'required|date',
            'origin' => 'required|string',
            'destination' => 'required|string',
            'purpose' => 'nullable|string'
        ]);

        try {
            $trip = $this->service->createTrip($orgId, $data);
            return response()->json($trip, 201);
        } catch (Exception $e) {
            $status = $e->getCode() == 409 ? 409 : 400;
            return response()->json(['message' => $e->getMessage()], $status);
        }
    }

    public function addPassenger(BaseFormRequest $request, $id)
    {
        $orgId = $request->attributes->get('organization_id');
        $data = $request->validate([
            'athlete_id' => 'nullable|integer',
            'employee_id' => 'nullable|integer',
            'coach_id' => 'nullable|integer',
            'passenger_name' => 'nullable|string'
        ]);

        try {
            $passenger = $this->service->addPassenger($orgId, $id, $data);
            return response()->json($passenger, 201);
        } catch (Exception $e) {
            $status = $e->getCode() == 409 ? 409 : 400;
            return response()->json(['message' => $e->getMessage()], $status);
        }
    }
}
