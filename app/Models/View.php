<?php

namespace App\Models;

use App\Observers\ViewObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $ip_address
 * @property string|null $country
 * @property string|null $city
 * @property string|null $lat
 * @property string|null $lon
 * @property int|null $target_lang_id
 * @property int|null $project_id
 * @property int|null $browser_lang_id
 */
#[ObservedBy(ViewObserver::class)]
class View extends \CyrildeWit\EloquentViewable\View
{
    public function browserLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'browser_lang_id');
    }
}
