<?php

namespace App\Http\Requests;

class UploadPaymentProofRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
            'company_id' => ['nullable', 'integer'],
        ];
    }
}
