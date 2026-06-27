<?php

namespace App\Observers;

use App\Models\ActivityLog;

class ActivityLogObserver
{
    public function creating(ActivityLog $activity): void
    {
        if ($activity->log_name === 'admin') {
            $activity->project_id = null;

            return;
        }
        $activity->project_id = $activity->project_id ?? request()->project->id ?? null;
    }
}
