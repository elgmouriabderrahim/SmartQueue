<?php

namespace App\Http\Controllers;

use App\Http\Requests\Rating\StoreRatingRequest;
use App\Http\Requests\Rating\UpdateRatingRequest;
use App\Models\Rating;
use App\Services\RatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function __construct(private readonly RatingService $ratingService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $ratings = Rating::query()
            ->with(['user', 'appointment', 'service'])
            ->latest()
            ->paginate($perPage);

        return $this->success($ratings, 'Ratings fetched successfully.');
    }

    public function store(StoreRatingRequest $request): JsonResponse
    {
        $rating = $this->ratingService->create($request->validated());

        return $this->success($rating->load(['user', 'appointment', 'service']), 'Rating created successfully.', 201);
    }

    public function show(Rating $rating): JsonResponse
    {
        return $this->success($rating->load(['user', 'appointment', 'service']), 'Rating fetched successfully.');
    }

    public function update(UpdateRatingRequest $request, Rating $rating): JsonResponse
    {
        $updated = $this->ratingService->update($rating, $request->validated());

        return $this->success($updated->load(['user', 'appointment', 'service']), 'Rating updated successfully.');
    }

    public function destroy(Rating $rating): JsonResponse
    {
        $this->ratingService->delete($rating);

        return $this->success(null, 'Rating deleted successfully.');
    }
}
