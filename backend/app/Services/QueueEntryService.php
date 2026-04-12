<?php

namespace App\Services;

use App\Models\QueueEntry;

class QueueEntryService
{
    public function create(array $data): QueueEntry
    {
        return QueueEntry::query()->create($data);
    }

    public function update(QueueEntry $queueEntry, array $data): QueueEntry
    {
        $queueEntry->update($data);

        return $queueEntry->fresh();
    }

    public function delete(QueueEntry $queueEntry): void
    {
        $queueEntry->delete();
    }
}
