<?php

namespace App\Services;

use App\Models\Setting;

class SettingService
{
    public function create(array $data): Setting
    {
        return Setting::query()->create($data);
    }

    public function update(Setting $setting, array $data): Setting
    {
        $setting->update($data);

        return $setting->fresh();
    }

    public function delete(Setting $setting): void
    {
        $setting->delete();
    }
}
