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
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx|max:10240|required_without:front_file',
            'front_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240|required_without:file',
            'back_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'category_selected' => 'required|string|max:255',
            'id_subtype' => 'nullable|string|in:paspoort,id_kaart,rijbewijs',
            'company_id' => 'nullable|integer',
            'ocr_confidence' => 'nullable|numeric|min:0|max:100',
            'ocr_text' => 'nullable|string',
            'ocr_text_front' => 'nullable|string',
            'ocr_text_back' => 'nullable|string',
            'ocr_confidence_front' => 'nullable|numeric|min:0|max:100',
            'ocr_confidence_back' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
