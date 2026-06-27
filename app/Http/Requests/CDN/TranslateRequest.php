<?php

namespace App\Http\Requests\CDN;

use Illuminate\Foundation\Http\FormRequest;

class TranslateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'translatables' => ['required', 'array', 'max:100'],
            'translatables.*.id' => [
                'required',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! is_string($value) && ! is_int($value)) {
                        $fail("The {$attribute} must be a string or integer.");
                    }
                },
            ],
            'translatables.*.text' => ['required', 'string', 'max:10000'],
            'source' => ['required', 'string', 'size:2'],
            'target' => ['required', 'string', 'size:2'],
        ];
    }
}
