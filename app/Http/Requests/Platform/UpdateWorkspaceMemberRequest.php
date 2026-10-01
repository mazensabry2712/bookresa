<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkspaceMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(array_keys(config('bookresa.rbac.roles', [])))],
            'status' => ['required', Rule::in(['active', 'suspended'])],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }
}
