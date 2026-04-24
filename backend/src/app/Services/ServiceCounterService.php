<?php

namespace App\Services;

use App\Models\ServiceCounter;

class ServiceCounterService
{
    public function create(array $data): ServiceCounter
    {
        return ServiceCounter::query()->create($data);
    }

    public function update(ServiceCounter $serviceCounter, array $data): ServiceCounter
    {
        $serviceCounter->update($data);

        return $serviceCounter->fresh();
    }

    public function delete(ServiceCounter $serviceCounter): void
    {
        $serviceCounter->delete();
    }
}
