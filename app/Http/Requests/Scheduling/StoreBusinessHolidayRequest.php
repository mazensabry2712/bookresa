<?php

namespace App\Http\Requests\Scheduling;

use App\Http\Requests\Scheduling\Concerns\UsesTenantLocalToday;
use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessHolidayRequest extends FormRequest
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
            'holiday_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$this->tenantLocalToday()],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
