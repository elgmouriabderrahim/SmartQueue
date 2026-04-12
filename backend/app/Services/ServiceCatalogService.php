<?php

namespace App\Services;

use App\Models\Service;

class ServiceCatalogService
{
    public function create(array $data): Service
    {
        $normalized = $this->normalizeData($data);

        return Service::query()->create($normalized);
    }

    public function update(Service $service, array $data): Service
    {
        $service->update($this->normalizeData($data));

        return $service->fresh();
    }

    private function normalizeData(array $data): array
    {
        if (isset($data['duration'])) {
            $data['estimated_duration'] = $data['duration'];
            unset($data['duration']);
        }

        if (isset($data['capacity'])) {
            $data['max_daily_capacity'] = $data['capacity'];
            unset($data['capacity']);
        }

        return $data;
    }
}
