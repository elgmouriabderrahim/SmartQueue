<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RatingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $ratings = Rating::query()
            ->with(['user', 'appointment', 'service'])
            ->latest()
            ->paginate($perPage);

        return response()->json($ratings);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'appointment_id' => ['required', 'exists:appointments,id', 'unique:ratings,appointment_id'],
            'service_id' => ['required', 'exists:services,id'],
            'overall_rating' => ['required', 'integer', 'between:1,5'],
            'status' => ['sometimes', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        $rating = Rating::create($validated);

        return response()->json($rating->load(['user', 'appointment', 'service']), 201);
    }

    public function show(Rating $rating): JsonResponse
    {
        return response()->json($rating->load(['user', 'appointment', 'service']));
    }

    public function update(Request $request, Rating $rating): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['sometimes', 'required', 'exists:users,id'],
            'appointment_id' => [
                'sometimes',
                'required',
                'exists:appointments,id',
                Rule::unique('ratings', 'appointment_id')->ignore($rating->id),
            ],
            'service_id' => ['sometimes', 'required', 'exists:services,id'],
            'overall_rating' => ['sometimes', 'required', 'integer', 'between:1,5'],
            'status' => ['sometimes', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        $rating->update($validated);

        return response()->json($rating->fresh()->load(['user', 'appointment', 'service']));
    }

    public function destroy(Rating $rating): JsonResponse
    {
        $rating->delete();

        return response()->json(['message' => 'Rating deleted successfully.']);
    }
}
