<?php

namespace App\Models;

use App\Observers\ProjectAcceptedOriginObserver;
use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $project_id
 * @property string $origin
 * @property string $normalized_origin
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
#[ObservedBy(ProjectAcceptedOriginObserver::class)]
class ProjectAcceptedOrigin extends Model
{
    use BaseModelTrait;

    protected $guarded = ['id', 'uuid', 'created_at', 'updated_at'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
