<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

trait LogsAdminActivity
{
    protected function logAdminActivity(
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?string $event = null,
    ): void {
        $causer = auth()->user();

        $activity = activity('admin');

        if ($causer !== null) {
            $activity->causedBy($causer);
        }

        if ($subject !== null) {
            $activity->performedOn($subject);
        }

        if ($event !== null) {
            $activity->event($event);
        }

        $activity->withProperties(array_merge(
            $this->getDefaultAdminActivityProperties($causer, $subject),
            $properties,
        ));

        $activity->log($description);
    }

    protected function getDefaultAdminActivityProperties(?Model $causer = null, ?Model $subject = null): array
    {
        return array_filter([
            'panel' => 'admin',
            'page' => static::class,
            'resource' => method_exists($this, 'getResource') ? static::getResource() : null,
            'causer' => $causer ? $this->getActivityModelSnapshot($causer) : null,
            'subject' => $subject ? $this->getActivityModelSnapshot($subject) : null,
        ], fn (mixed $value): bool => $value !== null);
    }

    protected function getActivityModelSnapshot(Model $model): array
    {
        return [
            'id' => $model->getKey(),
            'type' => $model::class,
            'label' => $this->getActivityModelLabel($model),
        ];
    }

    protected function getActivityModelLabel(Model $model): string
    {
        foreach (['name', 'email', 'title', 'slug', 'origin'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return class_basename($model).' #'.$model->getKey();
    }
}
