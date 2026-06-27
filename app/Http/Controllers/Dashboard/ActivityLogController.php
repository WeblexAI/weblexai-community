<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

use App\Models\ActivityLog;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Project $project): Response
    {
        $this->authorize('viewActivityLogs', $project);

        $activities = ActivityLog::query()
            ->where('project_id', $project->id)
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->latest()
            ->paginate(10);

        return Inertia::render('Project/ActivityLogs', [
            'activities' => $activities,
        ]);
    }
}

