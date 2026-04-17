<?php

namespace App\Http\Requests\Rating;

use App\Models\Rating;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Rating|null $rating */
        $rating = $this->route('rating');

        return [
            'user_id' => ['sometimes', 'required', 'exists:users,id'],
            'appointment_id' => [
                'sometimes',
                'required',
                'exists:appointments,id',
                Rule::unique('ratings', 'appointment_id')->ignore($rating?->id),
            ],
            'service_id' => ['sometimes', 'required', 'exists:services,id'],
            'score' => ['sometimes', 'required', 'integer', 'between:1,5'],
            'comment' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
