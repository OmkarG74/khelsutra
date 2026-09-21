<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\BaseFormRequest;
use App\Services\Operations\EventService;
use App\Models\SchoolActivity;
use Illuminate\Http\Request;
use Exception;

class SchoolActivityController extends Controller
{
    protected $service;

    public function __construct(EventService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $orgId = $request->attributes->get('organization_id');
        $activities = SchoolActivity::where('organization_id', $orgId)->paginate(15);
        return response()->json($activities);
    }

    public function store(BaseFormRequest $request)
    {
        $orgId = $request->attributes->get('organization_id');
        $data = $request->validate([
            'school_name' => 'required|string',
            'activity_name' => 'required|string',
            'activity_date' => 'required|date',
            'participant_count' => 'nullable|integer'
        ]);

        try {
            $activity = $this->service->createSchoolActivity($orgId, $data);
            return response()->json($activity, 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
