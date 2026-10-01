<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlatformWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'owner_name' => ['required', 'string', 'max:160'],
            'owner_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:12', 'confirmed'],
            'business_name_en' => ['required', 'string', 'max:160'],
            'business_name_ar' => ['nullable', 'string', 'max:160'],
            'business_type_id' => ['required', 'integer', 'exists:business_types,id'],
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:tenants,slug'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'timezone' => ['required', 'timezone'],
            'locale' => ['required', Rule::in(config('bookresa.locales', ['en', 'ar']))],
            'status' => ['required', Rule::in(['active', 'suspended', 'archived'])],
        ];
    }
}
