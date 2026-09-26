<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('business.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name_en' => trim((string) $this->input('name_en')),
            'name_ar' => trim((string) $this->input('name_ar')),
            'phone' => trim((string) $this->input('phone')),
            'email' => trim((string) $this->input('email')),
            'location' => trim((string) $this->input('location')),
            'address' => trim((string) $this->input('address')),
            'website' => trim((string) $this->input('website')),
            'facebook' => trim((string) $this->input('facebook')),
            'instagram' => trim((string) $this->input('instagram')),
            'remove_logo' => filter_var($this->input('remove_logo'), FILTER_VALIDATE_BOOL),
            'remove_cover' => filter_var($this->input('remove_cover'), FILTER_VALIDATE_BOOL),
        ]);
    }

    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:160'],
            'name_ar' => ['nullable', 'string', 'max:160'],
            'description_en' => ['nullable', 'string', 'max:5000'],
            'description_ar' => ['nullable', 'string', 'max:5000'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'url:http,https', 'max:500'],
            'facebook' => ['nullable', 'url:http,https', 'max:500'],
            'instagram' => ['nullable', 'url:http,https', 'max:500'],
            'timezone' => ['required', 'timezone'],
            'locale' => ['required', Rule::in(config('bookresa.locales', ['en', 'ar']))],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'remove_logo' => ['boolean'],
            'remove_cover' => ['boolean'],
        ];
    }
}
