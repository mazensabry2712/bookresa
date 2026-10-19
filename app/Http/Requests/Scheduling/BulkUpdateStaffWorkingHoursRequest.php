<?php

namespace App\Http\Requests\Scheduling;

use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateStaffWorkingHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->idOrFail();

        return [
            'staff_ids' => ['required', 'array', 'min:1'],
            'staff_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('staff_profiles', 'id')->where('tenant_id', $tenantId),
            ],
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.day_of_week' => ['required', 'integer', 'between:1,7', 'distinct'],
            'hours.*.opens_at' => ['required_unless:hours.*.is_closed,1', 'nullable', 'date_format:H:i'],
            'hours.*.closes_at' => ['required_unless:hours.*.is_closed,1', 'nullable', 'date_format:H:i', 'after:hours.*.opens_at'],
            'hours.*.is_closed' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'staff_ids.required' => __('app.scheduling_ui.choose_staff_for_bulk'),
            'staff_ids.min' => __('app.scheduling_ui.choose_staff_for_bulk'),
            'staff_ids.*.exists' => __('app.scheduling_ui.choose_staff_for_bulk'),
        ];
    }
}
