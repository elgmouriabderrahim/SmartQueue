<?php

namespace App\Services;

use App\Models\ServiceCounter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ServiceCounterService
{
    public function create(array $data): ServiceCounter
    {
        return DB::transaction(function () use ($data): ServiceCounter {
            $serviceIds = Arr::pull($data, 'service_ids', []);

            $serviceCounter = ServiceCounter::query()->create($data);
            $serviceCounter->services()->sync($serviceIds);

            return $serviceCounter->fresh(['services']);
        });
    }

    public function update(ServiceCounter $serviceCounter, array $data): ServiceCounter
    {
        return DB::transaction(function () use ($serviceCounter, $data): ServiceCounter {
            $serviceIds = array_key_exists('service_ids', $data)
                ? Arr::pull($data, 'service_ids', [])
                : null;

            $serviceCounter->update($data);

            if ($serviceIds !== null) {
                $serviceCounter->services()->sync($serviceIds);
            }

            return $serviceCounter->fresh(['services']);
        });
    }

    public function delete(ServiceCounter $serviceCounter): void
    {
        $serviceCounter->delete();
    }
}
