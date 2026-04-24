<?php

namespace App\Http\Controllers;

use App\Http\Requests\Rating\StoreRatingRequest;
use App\Http\Requests\Rating\UpdateRatingRequest;
use App\Models\Appointment;
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
                required: ['user_id', 'appointment_id', 'service_id', 'score'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', example: 1),
                    new OA\Property(property: 'appointment_id', type: 'integer', example: 1),
                    new OA\Property(property: 'service_id', type: 'integer', example: 1),
                    new OA\Property(property: 'score', type: 'integer', example: 5),
                    new OA\Property(property: 'comment', type: 'string', nullable: true, example: 'Fast and professional service'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Rating created')]
    )]
    public function store(StoreRatingRequest $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return $this->error('Unauthenticated.', 401);
        }

        $appointment = Appointment::query()->findOrFail($request->integer('appointment_id'));
        if ($appointment->user_id !== $user->id) {
            return $this->error('You can only rate your own appointments.', 403);
        }

        if ($appointment->status !== 'completed') {
            return $this->error('Only completed appointments can be rated.', 422);
        }

        if ((int) $appointment->service_id !== $request->integer('service_id')) {
            return $this->error('Service must match the appointment service.', 422);
        }

        $rating = $this->ratingService->create([
            ...$request->validated(),
            'user_id' => $user->id,
        ]);

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
        if ($rating->user_id !== $request->user()?->id) {
            return $this->error('Forbidden.', 403);
        }

        $updated = $this->ratingService->update($rating, $request->validated());

        return $this->success($updated->load(['user', 'appointment', 'service']), 'Rating updated successfully.');
    }

    public function destroy(Rating $rating): JsonResponse
    {
        if ($rating->user_id !== request()->user()?->id) {
            return $this->error('Forbidden.', 403);
        }

        $this->ratingService->delete($rating);

        return $this->success(null, 'Rating deleted successfully.');
    }
}
