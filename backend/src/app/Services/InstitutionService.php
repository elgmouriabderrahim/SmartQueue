<?php

namespace App\Services;

use App\Models\Institution;

class InstitutionService
{
    public function create(array $data): Institution
    {
        return Institution::query()->create($data);
    }

    public function update(Institution $institution, array $data): Institution
    {
        $institution->update($data);

        return $institution->fresh();
    }

    public function delete(Institution $institution): void
    {
        $institution->delete();
    }
}
