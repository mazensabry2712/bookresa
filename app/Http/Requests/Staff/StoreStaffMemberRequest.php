<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('staff.manage') === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'display_name' => trim((string) $this->input('display_name')),
            'phone' => trim((string) $this->input('phone')),
            'job_title' => trim((string) $this->input('job_title')),
        ]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'exists:users,email'],
            'display_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'role' => ['required', Rule::in($this->staffRoles())],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'min:1'],
        ];
    }

    private function staffRoles(): array
    {
        return array_values(array_filter(
            array_keys(config('bookresa.rbac.roles', [])),
            static fn (string $role): bool => $role !== 'owner',
        ));
    }
}
