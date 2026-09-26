<?php

namespace AppHttpRequestsBooking;

use IlluminateFoundationHttpFormRequest;
use IlluminateValidationRule;

final class RescheduleBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('bookings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'staff_id' => [
                'nullable',
                'integer',
                Rule::exists('staff_profiles', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $this->user()?->currentTenantId()),
                ),
            ],
        ];
    }
}
