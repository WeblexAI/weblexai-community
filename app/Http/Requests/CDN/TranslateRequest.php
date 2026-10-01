<?php

namespace App\Http\Requests\CDN;

use App\Enums\TranslationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TranslateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'translatables' => ['required', 'array', 'max:'.config('translation.batch_items', 100)],
            'translatables.*.id' => [
                'required',
                'distinct:strict',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! is_string($value) && ! is_int($value)) {
                        $fail("The {$attribute} must be a string or integer.");
                    }
                },
            ],
            'translatables.*.text' => ['required', 'string', 'max:10000'],
            'translatables.*.type' => ['sometimes', 'string', Rule::in(array_column(TranslationType::cases(), 'value'))],
            'translatables.*.attr' => [
                'sometimes',
                'string',
                Rule::in(['', 'placeholder', 'alt', 'title', 'aria-label', 'aria-description']),
            ],
            'translatables.*.context' => ['sometimes', 'string', 'max:10000'],
            'source' => ['required', 'string', 'size:2'],
            'target' => ['required', 'string', 'size:2'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'translatables' => collect($this->input('translatables', []))
                ->map(function (mixed $item): mixed {
                    if (! is_array($item)) {
                        return $item;
                    }

                    return [
                        ...$item,
                        'id' => is_string($item['id'] ?? null) || is_int($item['id'] ?? null) ? (string) $item['id'] : ($item['id'] ?? null),
                        'type' => $item['type'] ?? TranslationType::INNER_TEXT->value,
                        'attr' => $item['attr'] ?? '',
                        'context' => $item['context'] ?? '',
                    ];
                })->all(),
        ]);
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $characters = 0;
            foreach ($this->input('translatables', []) as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (($item['type'] ?? 'text') === TranslationType::ATTRIBUTE->value && ($item['attr'] ?? '') === '') {
                    $validator->errors()->add("translatables.{$index}.attr", 'An attribute translation requires an attribute name.');
                }

                if (($item['type'] ?? 'text') === TranslationType::INNER_TEXT->value && ($item['attr'] ?? '') !== '') {
                    $validator->errors()->add("translatables.{$index}.attr", 'Text translations cannot include an attribute name.');
                }

                $characters += mb_strlen((string) ($item['text'] ?? ''));
            }

            if ($characters > config('translation.batch_characters', 20000)) {
                $validator->errors()->add('translatables', 'The translation batch is too large.');
            }
        }];
    }
}
