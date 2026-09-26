<?php

namespace App\Http\Requests\Staff;

use App\Domain\Staff\Enums\StaffStatus;
use Illuminate\Validation\Rule;

final class UpdateStaffMemberRequest extends StoreStaffMemberRequest
{
    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'role' => ['required', Rule::in($this->staffRoles())],
            'status' => ['required', Rule::enum(StaffStatus::class)],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'display_name' => trim((string) $this->input('display_name')),
            'phone' => trim((string) $this->input('phone')),
            'job_title' => trim((string) $this->input('job_title')),
        ]);
    }

    private function staffRoles(): array
    {
        return array_values(array_filter(
            array_keys(config('bookresa.rbac.roles', [])),
            static fn (string $role): bool => $role !== 'owner',
        ));
    }
}
