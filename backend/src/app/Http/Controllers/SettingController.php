<?php

namespace App\Http\Controllers;

use App\Http\Requests\Setting\StoreSettingRequest;
use App\Http\Requests\Setting\UpdateSettingRequest;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(private readonly SettingService $settingService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $settings = Setting::query()
            ->with(['institution'])
            ->latest()
            ->paginate($perPage);

        return $this->success($settings, 'Settings fetched successfully.');
    }

    public function store(StoreSettingRequest $request): JsonResponse
    {
        $setting = $this->settingService->create($request->validated());

        return $this->success($setting->load('institution'), 'Setting created successfully.', 201);
    }

    public function show(Setting $setting): JsonResponse
    {
        return $this->success($setting->load('institution'), 'Setting fetched successfully.');
    }

    public function update(UpdateSettingRequest $request, Setting $setting): JsonResponse
    {
        $updated = $this->settingService->update($setting, $request->validated());

        return $this->success($updated->load('institution'), 'Setting updated successfully.');
    }

    public function destroy(Setting $setting): JsonResponse
    {
        $this->settingService->delete($setting);

        return $this->success(null, 'Setting deleted successfully.');
    }
}
