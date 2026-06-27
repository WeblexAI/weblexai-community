<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidProjectName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (preg_match('/\s/', $value)) {
            $fail('The :attribute must not contain spaces.');
        }
        if (! preg_match('/^[A-Za-z0-9-]+$/', $value)) {
            $fail('The :attribute may only contain letters, numbers, and hyphens.');
        }
        if (preg_match('/^\d+$/', $value)) {
            $fail('The :attribute cannot be numbers only.');
        }
    }
}
