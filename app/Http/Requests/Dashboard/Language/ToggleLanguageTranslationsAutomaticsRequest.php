<?php

namespace App\Http\Requests\Language;

use App\Models\Language;
use Illuminate\Foundation\Http\FormRequest;

class ToggleLanguageTranslationsAutomaticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', [Language::class, $this->route('project')]);
    }

    public function rules(): array
    {
        return [
            'should_display_automatics' => ['required', 'boolean'],
        ];
    }
}
