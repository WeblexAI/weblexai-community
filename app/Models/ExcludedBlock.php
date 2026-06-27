<?php

namespace App\Models;

use App\Enums\ModelStatus;
use App\Observers\ExcludedBlockObserver;
use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property string $project_id
 * @property ModelStatus $is_active
 * @property string $created_at
 * @property string $updated_at
 * @property Project $project
 * @property int $created_by_id
 * @property User $creator
 * @property string $selector
 * @property string $description
 */
#[ObservedBy(ExcludedBlockObserver::class)]
class ExcludedBlock extends Model
{
    use BaseModelTrait, HasFactory, LogsActivity;

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->setDescriptionForEvent(function ($event) {
                return auth()->user()->name." has {$event} excluded block {$this->selector}";
            });
    }
}
