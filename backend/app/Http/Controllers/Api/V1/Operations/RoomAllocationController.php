<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\BaseFormRequest;
use App\Services\Operations\RoomAllocationService;
use App\Models\AccommodationAllocation;
use Illuminate\Http\Request;
use Exception;

class RoomAllocationController extends Controller
{
    protected $service;

    public function __construct(RoomAllocationService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $orgId = $request->attributes->get('organization_id');
        $alloc = AccommodationAllocation::where('organization_id', $orgId)->paginate(15);
        return response()->json($alloc);
    }

    public function store(BaseFormRequest $request)
    {
        $orgId = $request->attributes->get('organization_id');
        $data = $request->validate([
            'accommodation_id' => 'required|integer',
            'room_id' => 'required|integer',
            'check_in_date' => 'required|date',
            'check_out_date' => 'nullable|date',
            'athlete_id' => 'nullable|integer',
            'employee_id' => 'nullable|integer',
            'coach_id' => 'nullable|integer'
        ]);

        try {
            $alloc = $this->service->allocateRoom($orgId, $data);
            return response()->json($alloc, 201);
        } catch (Exception $e) {
            $status = $e->getCode() == 409 ? 409 : 400;
            return response()->json(['message' => $e->getMessage()], $status);
        }
    }
}
