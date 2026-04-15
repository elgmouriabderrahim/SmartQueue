<?php

namespace App\Http\Controllers;

use App\Http\Requests\Rating\StoreRatingRequest;
use App\Http\Requests\Rating\UpdateRatingRequest;
use App\Models\Rating;
use App\Services\RatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class RatingController extends Controller
{
    public function __construct(private readonly RatingService $ratingService)
    {
    }

    #[OA\Get(
        path: '/ratings',
        tags: ['Ratings'],
        summary: 'List ratings',
        responses: [new OA\Response(response: 200, description: 'Ratings fetched')]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $ratings = Rating::query()
            ->with(['user', 'appointment', 'service'])
            ->latest()
            ->paginate($perPage);

        return $this->success($ratings, 'Ratings fetched successfully.');
    }

    #[OA\Post(
        path: '/ratings',
        tags: ['Ratings'],
        summary: 'Create rating',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'appointment_id', 'service_id', 'overall_rating'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', example: 1),
                    new OA\Property(property: 'appointment_id', type: 'integer', example: 1),
                    new OA\Property(property: 'service_id', type: 'integer', example: 1),
                    new OA\Property(property: 'overall_rating', type: 'integer', example: 5),
                    new OA\Property(property: 'comment', type: 'string', nullable: true, example: 'Fast and professional service'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Rating created')]
    )]
    public function store(StoreRatingRequest $request): JsonResponse
    {
        $rating = $this->ratingService->create($request->validated());

        return $this->success($rating->load(['user', 'appointment', 'service']), 'Rating created successfully.', 201);
    }

    #[OA\Get(
        path: '/ratings/{rating}',
        tags: ['Ratings'],
        summary: 'Get rating details',
        parameters: [new OA\Parameter(name: 'rating', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Rating fetched')]
    )]
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
