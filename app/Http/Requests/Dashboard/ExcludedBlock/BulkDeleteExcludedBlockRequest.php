<?php

namespace App\Http\Requests\ExcludedBlock;

use App\Models\ExcludedBlock;
use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteExcludedBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('bulkDelete', [ExcludedBlock::class, $this->project]);
    }

    public function rules(): array
    {
        return [
            'block_ids' => ['required', 'array'],
            'block_ids.*' => ['integer'],
        ];
    }
}
