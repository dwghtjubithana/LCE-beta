<?php

namespace App\Http\Requests;

class CreateCompanyRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => 'required|string|max:255',
            'sector' => 'required|string|max:255',
            'experience' => 'nullable|string',
            'contact' => 'nullable|array',
            'contact.email' => 'nullable|email|max:255',
            'contact.phone' => 'nullable|string|max:30',
            'contact.address' => 'nullable|string|max:255',
        ];
    }
}
