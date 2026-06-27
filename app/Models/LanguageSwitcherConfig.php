<?php

namespace App\Models;

use App\Enums\ModelStatus;
use App\Enums\SwitcherDeviceType;
use App\Observers\LanguageSwitcherConfigObserver;
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
 * @property string $target_parent_selector
 * @property bool $should_display_name
 * @property bool $should_display_full_name
 * @property bool $should_display_flag
 * @property int $size
 * @property bool $should_open_on_hover
 * @property bool $should_close_on_outside_click
 * @property bool $should_show_by_device
 * @property SwitcherDeviceType $preferred_device
 * @property int $device_pixel_breakpoint
 */

#[ObservedBy(LanguageSwitcherConfigObserver::class)]
class LanguageSwitcherConfig extends Model
{
    use BaseModelTrait, HasFactory, LogsActivity;

    protected $casts = [
        'preferred_device' => SwitcherDeviceType::class,
        'should_display_name' => 'boolean',
        'should_display_full_name' => 'boolean',
        'should_display_flag' => 'boolean',
        'should_open_on_hover' => 'boolean',
        'should_close_on_outside_click' => 'boolean',
        'should_show_by_device' => 'boolean',
    ];

    protected static $recordEvents = ['updated'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->setDescriptionForEvent(function ($event) {
                return auth()->user()->name." has {$event} language switcher";
            });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
