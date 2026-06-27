<?php

namespace App\Http\Requests\ExcludedBlock;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExcludedBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('block'));
    }

    public function rules(): array
    {
        return [
            'selector' => ['required', 'string', 'max:30', 'regex:/^(#|\.).+/'],
            'description' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'selector.regex' => 'The selector must start with "#" for IDs or "." for classes.',
        ];
    }
}
