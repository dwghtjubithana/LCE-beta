<?php

namespace App\Http\Requests;

class UploadProfilePhotoRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png'],
        ];
    }
}
