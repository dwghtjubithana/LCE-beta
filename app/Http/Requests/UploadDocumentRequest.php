<?php

namespace App\Http\Requests;

class UploadDocumentRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf,docx|max:10240',
            'category_selected' => 'required|string|max:255',
            'company_id' => 'nullable|integer',
            'ocr_confidence' => 'nullable|numeric|min:0|max:100',
            'ocr_text' => 'nullable|string',
        ];
    }
}
