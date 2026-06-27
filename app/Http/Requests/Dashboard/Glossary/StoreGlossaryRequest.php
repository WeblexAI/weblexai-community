<?php

namespace App\Http\Requests\Glossary;

use App\Enums\GlossaryRule;
use App\Models\Glossary;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGlossaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', [Glossary::class, $this->project]);
    }

    public function rules(): array
    {
        $project = request()->project;

        return [
            'text' => [
                'required',
                'string',
                'unique:glossaries,text',
                'max:20',
                function ($attribute, $value, $fail) {
                    if (str_word_count($value) > 5) {
                        $fail('The glossary must not have more than 4 words.');
                    }
                },
                function ($attribute, $value, $fail) use ($project) {
                    $duplicate = $project->glossaries()->whereAppliesTo($this->text)->exists();
                    if ($duplicate) {
                        $fail('You cannot create a glossary rule that is contained in another rule.');
                    }
                },
            ],
            'is_case_sensitive' => ['required', 'boolean'],
            'rule' => ['required', Rule::enum(GlossaryRule::class)],
            'translated' => [Rule::requiredIf($this->rule === GlossaryRule::ALWAYS_TRANSLATE)],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['int', 'exists:languages,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'text.unique' => 'Glossary already exists.',
        ];
    }
}
