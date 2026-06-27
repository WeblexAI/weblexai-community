<?php

namespace App\Traits;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

trait BaseModelTrait
{
    protected static function bootBaseModelTrait(): void
    {
        static::creating(function ($model) {
            $model->created_by_id = auth()?->id() ?? null;
            $model->uuid = Str::uuid()->toString();
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    protected function formattedCreatedAt(): Attribute
    {
        return Attribute::make(
            get: fn () => Carbon::make($this->created_at)->format(formatted_date_str(true))
        );
    }
}
