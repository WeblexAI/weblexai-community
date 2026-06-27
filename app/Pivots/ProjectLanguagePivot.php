<?php

namespace App\Pivots;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProjectLanguagePivot extends Pivot
{
    protected $casts = [
        'is_public' => 'boolean',
        'should_display_automatics' => 'boolean',
        'is_disabled' => 'boolean',
        'disabled_at' => 'datetime',
    ];

    #[Scope]
    protected function enabled($query)
    {
        return $query->where('is_disabled', false);
    }

    #[Scope]
    protected function disabled($query)
    {
        return $query->where('is_disabled', true);
    }
}
