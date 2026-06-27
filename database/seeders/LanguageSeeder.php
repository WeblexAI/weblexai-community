<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['name' => 'English', 'country_name' => 'United Kingdom', 'iso_2' => 'en', 'iso_3' => 'eng', 'uuid' => Str::uuid(), 'color' => '#EF8354', 'flag' => 'gb'],
            ['name' => 'French', 'country_name' => 'France', 'iso_2' => 'fr', 'iso_3' => 'fra', 'uuid' => Str::uuid(), 'color' => '#54F0C1', 'flag' => 'fr'],
            ['name' => 'Spanish', 'country_name' => 'Spain', 'iso_2' => 'es', 'iso_3' => 'spa', 'uuid' => Str::uuid(), 'color' => '#56F054', 'flag' => 'es'],
            ['name' => 'German', 'country_name' => 'Germany', 'iso_2' => 'de', 'iso_3' => 'deu', 'uuid' => Str::uuid(), 'color' => '#E5F054', 'flag' => 'de'],
            ['name' => 'Portuguese', 'country_name' => 'Portugal', 'iso_2' => 'pt', 'iso_3' => 'por', 'uuid' => Str::uuid(), 'color' => '#F0549A', 'flag' => 'pt'],
            ['name' => 'Italian', 'country_name' => 'Italy', 'iso_2' => 'it', 'iso_3' => 'ita', 'uuid' => Str::uuid(), 'color' => '#54A0F0', 'flag' => 'it'],
            ['name' => 'Chinese (Simplified)', 'country_name' => 'China', 'iso_2' => 'zh', 'iso_3' => 'zho', 'uuid' => Str::uuid(), 'color' => '#F0A654', 'flag' => 'cn'],
            ['name' => 'Japanese', 'country_name' => 'Japan', 'iso_2' => 'ja', 'iso_3' => 'jpn', 'uuid' => Str::uuid(), 'color' => '#F05454', 'flag' => 'jp'],
            ['name' => 'Korean', 'country_name' => 'South Korea', 'iso_2' => 'ko', 'iso_3' => 'kor', 'uuid' => Str::uuid(), 'color' => '#54F0E5', 'flag' => 'kr'],
            ['name' => 'Arabic', 'country_name' => 'Saudi Arabia', 'iso_2' => 'ar', 'iso_3' => 'ara', 'uuid' => Str::uuid(), 'color' => '#8EF054', 'flag' => 'sa'],
            ['name' => 'Russian', 'country_name' => 'Russia', 'iso_2' => 'ru', 'iso_3' => 'rus', 'uuid' => Str::uuid(), 'color' => '#B454F0', 'flag' => 'ru'],
            ['name' => 'Hindi', 'country_name' => 'India', 'iso_2' => 'hi', 'iso_3' => 'hin', 'uuid' => Str::uuid(), 'color' => '#F08E54', 'flag' => 'in'],
            ['name' => 'Bengali', 'country_name' => 'Bangladesh', 'iso_2' => 'bn', 'iso_3' => 'ben', 'uuid' => Str::uuid(), 'color' => '#54F08E', 'flag' => 'bd'],
            ['name' => 'Turkish', 'country_name' => 'Turkey', 'iso_2' => 'tr', 'iso_3' => 'tur', 'uuid' => Str::uuid(), 'color' => '#F054C1', 'flag' => 'tr'],
            ['name' => 'Dutch', 'country_name' => 'Netherlands', 'iso_2' => 'nl', 'iso_3' => 'nld', 'uuid' => Str::uuid(), 'color' => '#54C1F0', 'flag' => 'nl'],
            ['name' => 'Polish', 'country_name' => 'Poland', 'iso_2' => 'pl', 'iso_3' => 'pol', 'uuid' => Str::uuid(), 'color' => '#C1F054', 'flag' => 'pl'],
            ['name' => 'Swedish', 'country_name' => 'Sweden', 'iso_2' => 'sv', 'iso_3' => 'swe', 'uuid' => Str::uuid(), 'color' => '#548EF0', 'flag' => 'se'],
            ['name' => 'Indonesian', 'country_name' => 'Indonesia', 'iso_2' => 'id', 'iso_3' => 'ind', 'uuid' => Str::uuid(), 'color' => '#F0D154', 'flag' => 'id'],
            ['name' => 'Vietnamese', 'country_name' => 'Vietnam', 'iso_2' => 'vi', 'iso_3' => 'vie', 'uuid' => Str::uuid(), 'color' => '#54F0B4', 'flag' => 'vn'],
            ['name' => 'Ukrainian', 'country_name' => 'Ukraine', 'iso_2' => 'uk', 'iso_3' => 'ukr', 'uuid' => Str::uuid(), 'color' => '#F0546A', 'flag' => 'ua'],
            ['name' => 'Swahili', 'country_name' => 'Tanzania', 'iso_2' => 'sw', 'iso_3' => 'swa', 'uuid' => Str::uuid(), 'color' => '#3CB371', 'flag' => 'tz'],
            ['name' => 'Hausa', 'country_name' => 'Nigeria', 'iso_2' => 'ha', 'iso_3' => 'hau', 'uuid' => Str::uuid(), 'color' => '#DAA520', 'flag' => 'ng'],
            ['name' => 'Yoruba', 'country_name' => 'Nigeria', 'iso_2' => 'yo', 'iso_3' => 'yor', 'uuid' => Str::uuid(), 'color' => '#9370DB', 'flag' => 'ng'],
            ['name' => 'Igbo', 'country_name' => 'Nigeria', 'iso_2' => 'ig', 'iso_3' => 'ibo', 'uuid' => Str::uuid(), 'color' => '#20B2AA', 'flag' => 'ng'],
            ['name' => 'Amharic', 'country_name' => 'Ethiopia', 'iso_2' => 'am', 'iso_3' => 'amh', 'uuid' => Str::uuid(), 'color' => '#CD5C5C', 'flag' => 'et'],
            ['name' => 'Urdu', 'country_name' => 'Pakistan', 'iso_2' => 'ur', 'iso_3' => 'urd', 'uuid' => Str::uuid(), 'color' => '#4682B4', 'flag' => 'pk'],
            ['name' => 'Persian (Farsi)', 'country_name' => 'Iran', 'iso_2' => 'fa', 'iso_3' => 'fas', 'uuid' => Str::uuid(), 'color' => '#6A5ACD', 'flag' => 'ir'],
            ['name' => 'Thai', 'country_name' => 'Thailand', 'iso_2' => 'th', 'iso_3' => 'tha', 'uuid' => Str::uuid(), 'color' => '#FF8C00', 'flag' => 'th'],
            ['name' => 'Filipino (Tagalog)', 'country_name' => 'Philippines', 'iso_2' => 'tl', 'iso_3' => 'tgl', 'uuid' => Str::uuid(), 'color' => '#1E90FF', 'flag' => 'ph'],
            ['name' => 'Hebrew', 'country_name' => 'Israel', 'iso_2' => 'he', 'iso_3' => 'heb', 'uuid' => Str::uuid(), 'color' => '#2E8B57', 'flag' => 'il'],
            ['name' => 'Zulu', 'country_name' => 'South Africa', 'iso_2' => 'zu', 'iso_3' => 'zul', 'uuid' => Str::uuid(), 'color' => '#8FBC8F', 'flag' => 'za'],
            ['name' => 'Afrikaans', 'country_name' => 'South Africa', 'iso_2' => 'af', 'iso_3' => 'afr', 'uuid' => Str::uuid(), 'color' => '#BDB76B', 'flag' => 'za'],
            ['name' => 'Somali', 'country_name' => 'Somalia', 'iso_2' => 'so', 'iso_3' => 'som', 'uuid' => Str::uuid(), 'color' => '#6495ED', 'flag' => 'so'],
            ['name' => 'Tamil', 'country_name' => 'India', 'iso_2' => 'ta', 'iso_3' => 'tam', 'uuid' => Str::uuid(), 'color' => '#FF6347', 'flag' => 'in'],
            ['name' => 'Telugu', 'country_name' => 'India', 'iso_2' => 'te', 'iso_3' => 'tel', 'uuid' => Str::uuid(), 'color' => '#DA70D6', 'flag' => 'in'],
            ['name' => 'Malay', 'country_name' => 'Malaysia', 'iso_2' => 'ms', 'iso_3' => 'msa', 'uuid' => Str::uuid(), 'color' => '#00CED1', 'flag' => 'my'],
            ['name' => 'Greek', 'country_name' => 'Greece', 'iso_2' => 'el', 'iso_3' => 'ell', 'uuid' => Str::uuid(), 'color' => '#4169E1', 'flag' => 'gr'],
            ['name' => 'Romanian', 'country_name' => 'Romania', 'iso_2' => 'ro', 'iso_3' => 'ron', 'uuid' => Str::uuid(), 'color' => '#DC143C', 'flag' => 'ro'],
            ['name' => 'Czech', 'country_name' => 'Czech Republic', 'iso_2' => 'cs', 'iso_3' => 'ces', 'uuid' => Str::uuid(), 'color' => '#708090', 'flag' => 'cz'],
            ['name' => 'Danish', 'country_name' => 'Denmark', 'iso_2' => 'da', 'iso_3' => 'dan', 'uuid' => Str::uuid(), 'color' => '#FF4500', 'flag' => 'dk'],
            ['name' => 'Norwegian', 'country_name' => 'Norway', 'iso_2' => 'no', 'iso_3' => 'nor', 'uuid' => Str::uuid(), 'color' => '#4682B4', 'flag' => 'no'],
        ];

        foreach ($languages as $language) {
            $uuid = $language['uuid'];
            $flag = $language['flag'];
            
            unset($language['uuid']);
            unset($language['flag']);

            $lang = Language::query()->firstOrCreate(
                ['iso_2' => $language['iso_2']],
                [...$language, 'uuid' => $uuid],
            );
            
            $flagPath = public_path("flags/{$flag}.svg");
            
            if (file_exists($flagPath) && !$lang->hasMedia('flag')) {
                $lang->addMedia($flagPath)
                    ->preservingOriginal()
                    ->toMediaCollection('flag');
            }
        }
    }
}
