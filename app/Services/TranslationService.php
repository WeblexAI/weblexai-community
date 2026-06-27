<?php

namespace App\Services;

use App\Models\Language;
use App\Models\Page;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TranslationService
{
    public static function applyFilter(Language $language, Page $page)
    {
        $translations = QueryBuilder::for(Translation::class)
            ->allowedFilters([
                AllowedFilter::callback('q', function (Builder $query, $input) {
                    // in case q contains "," , spatie query builder treats it as array
                    if (is_array($input)) {
                        $input = implode(',', $input);
                    }
                    $query->where('text', 'LIKE', "%$input%")
                        ->orWhere('translated', 'LIKE', "%$input%");
                }),
                AllowedFilter::exact('quality'),
                AllowedFilter::callback('status', function ($query, $value) {
                    $statuses = is_array($value) ? $value : explode(',', $value);
                    foreach ($statuses as $index => $status) {
                        if ($status == 'active') {
                            $statuses[$index] = true;
                        }
                        if ($status == 'inactive') {
                            $statuses[$index] = false;
                        }
                    }
                    $query->whereIn('is_on', $statuses);
                }),
            ])
            ->where('page_id', $page->id)
            ->where('target_lang_id', $language->id)
            ->paginate(perPage: 10, pageName: 'ppage')
            ->appends(request()->query());

        return $translations;
    }
}
