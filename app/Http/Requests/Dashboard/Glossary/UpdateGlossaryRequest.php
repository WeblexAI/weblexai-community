<?php

namespace App\Http\Requests\Dashboard\Glossary;

use App\Enums\GlossaryRule;
use App\Models\Glossary;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGlossaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', [Glossary::class, $this->project]);
    }

    public function rules(): array
    {
        return [
            'is_case_sensitive' => ['required', 'boolean'],
            'rule' => ['required', Rule::enum(GlossaryRule::class)],
            'translated' => [Rule::requiredIf($this->rule === GlossaryRule::ALWAYS_TRANSLATE)],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['int', 'exists:languages,id'],
        ];
    }
}
