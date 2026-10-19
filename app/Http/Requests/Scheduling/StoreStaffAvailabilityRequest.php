<?php

namespace App\Http\Requests\Scheduling;

use App\Http\Requests\Scheduling\Concerns\UsesTenantLocalToday;
use Illuminate\Foundation\Http\FormRequest;

class StoreStaffAvailabilityRequest extends FormRequest
{
    use UsesTenantLocalToday;

    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'available_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$this->tenantLocalToday()],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
        ];
    }
}
