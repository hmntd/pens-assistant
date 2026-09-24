<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required_without:document', 'nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'],
            'document' => ['required_without:file', 'nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'],
            'document_type' => ['nullable', 'string', 'max:100'],
        ];
    }
}
