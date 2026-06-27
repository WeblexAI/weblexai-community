<?php

namespace App\Http\Requests\Dashboard\ExcludedBlock;

use App\Models\ExcludedBlock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateExcludedBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [ExcludedBlock::class, $this->project]);
    }

    public function rules(): array
    {
        return [
            'selector' => [
                'required',
                'string',
                'max:30',
                'regex:/^(#|\.).+/',
                Rule::unique('excluded_blocks', 'selector')
                    ->where(function ($query) {
                        return $query->where('project_id', $this->project->id);
                    }),
            ],
            'description' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'selector.regex' => 'The selector must start with "#" for IDs or "." for classes.',
            'selector.unique' => 'This selector already exists for this project.',
        ];
    }
}
