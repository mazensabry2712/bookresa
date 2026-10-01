<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlatformWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');
        $tenantId = is_object($tenant) ? $tenant->getKey() : $tenant;

        return [
            'business_name_en' => ['required', 'string', 'max:160'],
            'business_name_ar' => ['nullable', 'string', 'max:160'],
            'business_type_id' => ['required', 'integer', 'exists:business_types,id'],
            'slug' => [
                'required',
                'string',
                'max:160',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('tenants', 'slug')->ignore($tenantId),
            ],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'timezone' => ['required', 'timezone'],
            'locale' => ['required', Rule::in(config('bookresa.locales', ['en', 'ar']))],
            'status' => ['required', Rule::in(['active', 'suspended', 'archived'])],
        ];
    }
}
