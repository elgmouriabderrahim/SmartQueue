<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

class SyncAnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
        ];
    }
}
