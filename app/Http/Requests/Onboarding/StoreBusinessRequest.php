<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'slug' => [
                'nullable',
                'string',
                'min:2',
                'max:120',
                'alpha_dash',
                Rule::unique('tenants', 'slug'),
            ],
            'business_type_id' => [
                'required',
                'integer',
                Rule::exists('business_types', 'id')->where('is_active', true),
            ],
            'name_en' => ['nullable', 'string', 'max:120'],
            'name_ar' => ['nullable', 'string', 'max:120'],
            'description_en' => ['nullable', 'string', 'max:5000'],
            'description_ar' => ['nullable', 'string', 'max:5000'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'timezone' => ['nullable', 'timezone:all'],
            'locale' => ['nullable', Rule::in(config('bookresa.locales', ['en', 'ar']))],
        ];
    }
}
