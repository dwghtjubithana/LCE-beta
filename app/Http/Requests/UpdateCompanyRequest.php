<?php

namespace App\Http\Requests;

class UpdateCompanyRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => 'sometimes|required|string|max:255',
            'sector' => 'sometimes|required|string|max:255',
            'experience' => 'nullable|string',
            'contact' => 'nullable|array',
            'contact.email' => 'nullable|email|max:255',
            'contact.phone' => 'nullable|string|max:30',
            'contact.address' => 'nullable|string|max:255',
            'bluewave_status' => 'nullable|boolean',
            'verification_level' => 'nullable|in:unverified,email_verified,physical_verified',
            'public_slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'display_name' => 'nullable|string|max:160',
            'address' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ];
    }
}
