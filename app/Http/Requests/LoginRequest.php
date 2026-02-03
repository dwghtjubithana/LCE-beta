<?php

namespace App\Http\Requests;

class LoginRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'nullable|email|max:150|required_without:phone',
            'phone' => 'nullable|string|max:30|required_without:email',
            'password' => 'required|string|max:255',
        ];
    }
}
