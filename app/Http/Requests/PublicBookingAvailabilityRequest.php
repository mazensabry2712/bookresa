<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicBookingAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tenant = $this->route('tenant');

        return $tenant !== null && $tenant->status->value === 'active';
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'service_id' => [
                'required',
                'integer',
                Rule::exists('services', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenant->getKey()),
                ),
            ],
            'staff_id' => [
                'nullable',
                'integer',
                Rule::exists('staff_profiles', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenant->getKey()),
                ),
            ],
            'date' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
