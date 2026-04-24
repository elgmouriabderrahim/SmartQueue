<?php

namespace App\Services;

use App\Models\Rating;

class RatingService
{
    public function create(array $data): Rating
    {
        return Rating::query()->create($data);
    }

    public function update(Rating $rating, array $data): Rating
    {
        $rating->update($data);

        return $rating->fresh();
    }

    public function delete(Rating $rating): void
    {
        $rating->delete();
    }
}
