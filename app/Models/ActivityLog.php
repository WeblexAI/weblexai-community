<?php

namespace App\Models;

use App\Observers\ActivityLogObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * @property int|null $project_id
 * @property string $log_name
 * @property string $description
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property string|null $causer_type
 * @property string|null $causer_id
 * @property Collection|null $properties
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
#[ObservedBy(ActivityLogObserver::class)]
class ActivityLog extends Activity
{
    protected $appends = [
        'formated_created_at',
        'causer_label',
        'subject_label',
        'subject_type_label',
        'target_label',
        'target_type_label',
    ];

    protected function formatedCreatedAt(): Attribute
    {
        return Attribute::get(
            fn () => Carbon::parse($this->created_at)
                ->setTimezone(config('app.timezone'))
                ->format('Y-m-d H:i (T)')
        );
    }

    protected function causerLabel(): Attribute
    {
        return Attribute::get(fn () => $this->getExtraProperty('causer.label')
            ?? $this->getActivityModelLabel($this->causer, $this->causer_type, $this->causer_id)
            ?? 'System');
    }

    protected function subjectLabel(): Attribute
    {
        return Attribute::get(fn () => $this->getExtraProperty('subject.label')
            ?? $this->getActivityModelLabel($this->subject, $this->subject_type, $this->subject_id));
    }

    protected function subjectTypeLabel(): Attribute
    {
        return Attribute::get(fn () => $this->formatModelTypeLabel(
            $this->getExtraProperty('subject.type') ?? $this->subject_type
        ));
    }

    protected function targetLabel(): Attribute
    {
        return Attribute::get(fn () => $this->subject_label ?? $this->getExtraProperty('settings.label'));
    }

    protected function targetTypeLabel(): Attribute
    {
        return Attribute::get(fn () => $this->subject_type_label
            ?? ($this->getExtraProperty('settings.label') ? 'Settings' : null));
    }

    protected function getActivityModelLabel(?Model $model, ?string $type, mixed $id): ?string
    {
        if ($model !== null) {
            foreach (['name', 'email', 'title', 'slug'] as $attribute) {
                $value = $model->getAttribute($attribute);

                if (filled($value)) {
                    return (string) $value;
                }
            }

            return class_basename($model).' #'.$model->getKey();
        }

        if ($type !== null && $id !== null) {
            return class_basename($type).' #'.$id;
        }

        return null;
    }

    protected function formatModelTypeLabel(?string $type): ?string
    {
        return $type ? Str::headline(class_basename($type)) : null;
    }
}
