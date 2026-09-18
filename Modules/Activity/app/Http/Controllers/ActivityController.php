<?php

namespace Modules\Activity\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Activity\Http\Requests\ActivityFilterRequest;
use Modules\Activity\Http\Requests\ClearActivityRequest;
use Modules\Activity\Models\ActivityLog;
use Modules\Activity\Services\ActivityService;

class ActivityController extends Controller
{
    public function __construct(
        private readonly ActivityService $service,
    ) {}

    public function index(ActivityFilterRequest $request)
    {
        bpAuthorize('activities.view');
        $stats = $this->service->getStats();
        $activities = $this->service->list(
            $request->only(['search', 'log_name', 'event', 'causer_id', 'subject_type', 'from_date', 'to_date']),
        );
        $logNames = $stats['log_names'];
        $subjectTypes = $this->service->getSubjectTypes();
        $users = \App\Models\User::orderBy('name')->get(['id', 'name']);

        return view('activity::index', compact('stats', 'activities', 'logNames', 'subjectTypes', 'users'));
    }

    public function show(ActivityLog $activity)
    {
        bpAuthorize('activities.view');
        $activity = $this->service->find($activity->id);

        return view('activity::show', compact('activity'));
    }

    public function clear(ClearActivityRequest $request)
    {
        bpAuthorize('activities.delete');
        $count = $this->service->clear(
            $request->input('log_name'),
            $request->input('older_than_days', 90),
        );

        return back()->with('success', "{$count} activity logs cleared.");
    }
}
