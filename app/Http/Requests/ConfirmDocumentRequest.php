<?php

namespace App\Http\Requests;

class ConfirmDocumentRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'category_selected' => ['required', 'string', 'max:255'],
        ];
    }
}
