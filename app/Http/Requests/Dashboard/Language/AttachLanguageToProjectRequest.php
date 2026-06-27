<?php

namespace App\Http\Requests\Dashboard\Language;

use App\Models\Language;
use Illuminate\Foundation\Http\FormRequest;

class AttachLanguageToProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', [Language::class, $this->route('project')]);
    }

    public function rules(): array
    {
        return [
            'language_id' => ['required', 'exists:languages,id'],
            'is_public' => ['required', 'boolean'],
            'should_display_automatics' => ['required', 'boolean'],
        ];
    }
}
