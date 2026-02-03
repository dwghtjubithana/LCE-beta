<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

abstract class BaseRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        $response = response()->json([
            'code' => 'VALIDATION_ERROR',
            'message' => 'Validation failed.',
            'fieldErrors' => $validator->errors(),
        ], 422);

        throw new HttpResponseException($response);
    }
}
