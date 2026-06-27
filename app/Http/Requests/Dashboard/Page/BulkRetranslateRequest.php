<?php

namespace App\Http\Requests\Page;

use Illuminate\Foundation\Http\FormRequest;

class BulkRetranslateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page_ids' => ['required', 'array'],
        ];
    }
}
