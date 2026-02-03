<?php

namespace App\Http\Requests;

class BulkUploadDocumentRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|mimes:jpg,jpeg,png,pdf,docx|max:10240',
            'category_selected' => 'required|string|max:255',
            'company_id' => 'nullable|integer',
        ];
    }
}
