<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RescheduleBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('bookings.update') ?? false;
    }

    public function rules(): array
    {
        $booking = $this->route('booking');

        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'staff_id' => [
                'nullable',
                'integer',
                Rule::exists('staff_profiles', 'id')->where(
                    fn ($query) => $query->where('tenant_id', data_get($booking, 'tenant_id')),
                ),
            ],
        ];
    }
}
