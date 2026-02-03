<?php

namespace App\Http\Requests;

class RegisterRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'nullable|email|max:150|unique:users,email|required_without:phone',
            'phone' => 'nullable|string|max:30|unique:users,phone|required_without:email',
            'password' => 'required|string|min:8|max:255',
            'username' => 'nullable|string|max:100|unique:users,username',
        ];
    }
}
