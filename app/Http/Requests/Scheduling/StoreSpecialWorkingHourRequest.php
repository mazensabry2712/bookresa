<?php

namespace App\Http\Requests\Scheduling;

use App\Http\Requests\Scheduling\Concerns\UsesTenantLocalToday;
use Illuminate\Foundation\Http\FormRequest;

class StoreSpecialWorkingHourRequest extends FormRequest
{
    use UsesTenantLocalToday;

    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => trim((string) $this->input('reason')),
        ]);
    }

    public function rules(): array
    {
        return [
            'work_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$this->tenantLocalToday()],
            'opens_at' => ['required_unless:is_closed,1', 'nullable', 'date_format:H:i'],
            'closes_at' => ['required_unless:is_closed,1', 'nullable', 'date_format:H:i', 'after:opens_at'],
            'is_closed' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
