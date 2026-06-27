<?php

namespace App\Http\Requests\Dashboard\Translation;

use Illuminate\Foundation\Http\FormRequest;

class ImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'mimes:xls,xlsx',
                'mimetypes:application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'max:10240',
            ],
            'page_id' => ['required', 'integer', 'exists:pages,id'],
            'target_lang_id' => ['required', 'integer', 'exists:languages,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'The file must be a valid Excel file.',
            'file.max' => 'The file must not be greater than 10MB.',
            'file.required' => 'You must choose a file to upload.',
        ];
    }
}
