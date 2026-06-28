<?php

namespace App\Observers;

use App\Models\Language;
use App\Models\View;

class ViewObserver
{
    public function creating(View $view): void
    {
        $ip = request()->ip();
        $location = geoip()->getLocation($ip);

        $targetLanguage = request()->input('target');
        $targetLanguage = Language::query()
            ->where('iso_2', $targetLanguage)
            ->first(['id']);

        $browserLanguage = null;
        $acceptLang = request()->header('Accept-Language');
        if ($acceptLang) {
            $lang = explode(',', $acceptLang)[0];
            $lang = explode('-', $lang)[0];

            $browserLanguage = Language::query()
                ->where('iso_2', $lang)
                ->first(['id']);
        }

        $view->ip_address = $ip;
        $view->country = $location->iso_code;
        $view->city = $location->city;
        $view->lat = $location->lat === null ? null : (string) $location->lat;
        $view->lon = $location->lon === null ? null : (string) $location->lon;

        $view->target_lang_id = $targetLanguage?->id;
        $view->browser_lang_id = $browserLanguage?->id;
        $view->project_id = request()->project?->id;
    }
}
