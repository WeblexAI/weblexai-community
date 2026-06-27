<?php

namespace App\Pivots;

use App\Enums\CollaboratorRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CollaboratorProjectPivot extends Pivot
{
    protected $table = 'project_user';

    protected $casts = [
        'role' => CollaboratorRole::class,
    ];
}
