<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\BaseFormRequest;
use App\Services\Operations\AccommodationService;
use App\Models\Accommodation;
use App\Models\AccommodationRoom;
use Illuminate\Http\Request;
use Exception;

class AccommodationController extends Controller
{
    protected $service;

    public function __construct(AccommodationService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $orgId = $request->attributes->get('organization_id');
        $acc = Accommodation::where('organization_id', $orgId)->paginate(15);
        return response()->json($acc);
    }

    public function store(BaseFormRequest $request)
    {
        $orgId = $request->attributes->get('organization_id');
        $data = $request->validate([
            'name' => 'required|string',
            'address_line1' => 'nullable|string',
            'status' => 'nullable|string'
        ]);

        try {
            $acc = $this->service->createAccommodation($orgId, $data);
            return response()->json($acc, 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function storeRoom(BaseFormRequest $request, $id)
    {
        $orgId = $request->attributes->get('organization_id');
        $data = $request->validate([
            'room_number' => 'required|string',
            'capacity' => 'required|integer',
            'status' => 'nullable|string'
        ]);

        try {
            $room = $this->service->createRoom($orgId, $id, $data);
            return response()->json($room, 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
