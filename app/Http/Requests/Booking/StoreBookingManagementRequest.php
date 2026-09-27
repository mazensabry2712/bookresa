<?php

namespace App\Http\Requests\Booking;

use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreBookingManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('bookings.create') ?? false;
    }

    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->idOrFail();

        return [
            'service_id' => [
                'required',
                'integer',
                Rule::exists('services', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId),
                ),
            ],
            'staff_id' => [
                'nullable',
                'integer',
                Rule::exists('staff_profiles', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId),
                ),
            ],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'name' => ['required', 'string', 'min:2', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
