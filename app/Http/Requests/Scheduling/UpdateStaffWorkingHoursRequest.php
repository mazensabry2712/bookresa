<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffWorkingHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.day_of_week' => ['required', 'integer', 'between:1,7', 'distinct'],
            'hours.*.opens_at' => ['required_unless:hours.*.is_closed,1', 'nullable', 'date_format:H:i'],
            'hours.*.closes_at' => ['required_unless:hours.*.is_closed,1', 'nullable', 'date_format:H:i', 'after:hours.*.opens_at'],
            'hours.*.is_closed' => ['nullable', 'boolean'],
        ];
    }
}
