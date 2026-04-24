<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogService
{
    public function create(array $data): ActivityLog
    {
        return ActivityLog::query()->create($data);
    }

    public function delete(ActivityLog $activityLog): void
    {
        $activityLog->delete();
    }
}
