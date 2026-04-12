<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $settings = Setting::query()
            ->with(['institution'])
            ->latest()
            ->paginate($perPage);

        return response()->json($settings);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'institution_id' => ['nullable', 'exists:institutions,id'],
            'key' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string'],
            'type' => ['sometimes', Rule::in(['string', 'integer', 'boolean', 'json'])],
        ]);

        $setting = Setting::create($validated);

        return response()->json($setting->load('institution'), 201);
    }

    public function show(Setting $setting): JsonResponse
    {
        return response()->json($setting->load('institution'));
    }

    public function update(Request $request, Setting $setting): JsonResponse
    {
        $validated = $request->validate([
            'institution_id' => ['sometimes', 'nullable', 'exists:institutions,id'],
            'key' => ['sometimes', 'required', 'string', 'max:255'],
            'value' => ['sometimes', 'required', 'string'],
            'type' => ['sometimes', Rule::in(['string', 'integer', 'boolean', 'json'])],
        ]);

        $setting->update($validated);

        return response()->json($setting->fresh()->load('institution'));
    }

    public function destroy(Setting $setting): JsonResponse
    {
        $setting->delete();

        return response()->json(['message' => 'Setting deleted successfully.']);
    }
}
