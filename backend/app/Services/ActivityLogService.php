<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogService
{
    public function create(array $data): ActivityLog
    {
        return ActivityLog::query()->create($data);
    }

    public function update(ActivityLog $activityLog, array $data): ActivityLog
    {
        $activityLog->update($data);

        return $activityLog->fresh();
    }

    public function delete(ActivityLog $activityLog): void
    {
        $activityLog->delete();
    }
}
