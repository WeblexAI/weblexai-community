<?php

namespace App\Http\Requests\Translation;

use Illuminate\Foundation\Http\FormRequest;

class ExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page_id' => ['required', 'integer', 'exists:pages,id'],
            'target_lang_id' => ['required', 'integer', 'exists:languages,id'],
        ];
    }
}
