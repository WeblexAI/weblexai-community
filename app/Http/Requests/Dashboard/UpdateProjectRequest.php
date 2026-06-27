<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\CollaboratorRole;
use App\Rules\ValidProjectName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');
        $user = $this->user();

        if (! $project || ! $user) {
            return false;
        }

        return CollaboratorRole::isAuthorized($project, $user, CollaboratorRole::canManageSettings());
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                new ValidProjectName,
                Rule::unique('projects', 'name')->ignore($this->route('project')),
            ],
            'should_display_automatics' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Project name',
        ];
    }
}
