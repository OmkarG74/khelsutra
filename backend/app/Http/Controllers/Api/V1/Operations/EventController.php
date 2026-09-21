<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\BaseFormRequest;
use App\Services\Operations\EventService;
use App\Models\Event;
use Illuminate\Http\Request;
use Exception;

class EventController extends Controller
{
    protected $service;

    public function __construct(EventService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $orgId = $request->attributes->get('organization_id');
        $events = Event::where('organization_id', $orgId)->paginate(15);
        return response()->json($events);
    }

    public function store(BaseFormRequest $request)
    {
        $orgId = $request->attributes->get('organization_id');
        $data = $request->validate([
            'name' => 'required|string',
            'event_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'organizer_employee_id' => 'nullable|integer'
        ]);

        try {
            $event = $this->service->createEvent($orgId, $data);
            return response()->json($event, 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function addParticipant(BaseFormRequest $request, $id)
    {
        $orgId = $request->attributes->get('organization_id');
        $data = $request->validate([
            'participant_type' => 'required|string',
            'participant_id' => 'required|integer'
        ]);

        try {
            $participant = $this->service->addParticipant($orgId, $id, $data);
            return response()->json($participant, 201);
        } catch (Exception $e) {
            $status = $e->getCode() == 409 ? 409 : 400;
            return response()->json(['message' => $e->getMessage()], $status);
        }
    }
}
