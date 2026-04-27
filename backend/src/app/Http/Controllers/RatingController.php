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
            ->with(['user', 'appointment', 'service', 'institution'])
            ->when($request->filled('service_id'), fn ($query) => $query->where('service_id', $request->integer('service_id')))
            ->when($request->filled('institution_id'), fn ($query) => $query->where('institution_id', $request->integer('institution_id')))
            ->when($request->boolean('mine') && $request->user(), fn ($query) => $query->where('user_id', (int) $request->user()?->id))
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
                    new OA\Property(property: 'appointment_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'service_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'institution_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'score', type: 'integer', example: 5),
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

        $serviceId = $request->integer('service_id');
        $institutionId = $request->integer('institution_id');
        $appointmentId = $request->integer('appointment_id');

        if (($serviceId > 0 && $institutionId > 0) || ($serviceId <= 0 && $institutionId <= 0)) {
            return $this->error('Choose exactly one rating target: service or institution.', 422);
        }

        if ($appointmentId > 0) {
            $appointment = Appointment::query()->findOrFail($appointmentId);
            if ((int) $appointment->user_id !== (int) $user->id) {
                return $this->error('You can only rate your own appointments.', 403);
            }

            if ($appointment->status !== 'completed') {
                return $this->error('Only completed appointments can be rated.', 422);
            }

            if ($serviceId > 0 && (int) $appointment->service_id !== $serviceId) {
                return $this->error('Service must match the appointment service.', 422);
            }

            if ($institutionId > 0) {
                $matchesInstitution = $appointment->service()
                    ->where('institution_id', $institutionId)
                    ->exists();

                if (! $matchesInstitution) {
                    return $this->error('Institution must match the appointment institution.', 422);
                }
            }
        }

        $attributes = ['user_id' => (int) $user->id];
        if ($serviceId > 0) {
            $attributes['service_id'] = $serviceId;
        }
        if ($institutionId > 0) {
            $attributes['institution_id'] = $institutionId;
        }

        $rating = Rating::query()->updateOrCreate($attributes, [
            'appointment_id' => $appointmentId > 0 ? $appointmentId : null,
            'service_id' => $serviceId > 0 ? $serviceId : null,
            'institution_id' => $institutionId > 0 ? $institutionId : null,
            'score' => $request->integer('score'),
        ]);

        return $this->success($rating->load(['user', 'appointment', 'service', 'institution']), 'Rating saved successfully.', 201);
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
        return $this->success($rating->load(['user', 'appointment', 'service', 'institution']), 'Rating fetched successfully.');
    }

    public function update(UpdateRatingRequest $request, Rating $rating): JsonResponse
    {
        if ($rating->user_id !== $request->user()?->id) {
            return $this->error('Forbidden.', 403);
        }

        $updated = $this->ratingService->update($rating, $request->validated());

        return $this->success($updated->load(['user', 'appointment', 'service', 'institution']), 'Rating updated successfully.');
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
