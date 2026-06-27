<?php

namespace App\Contracts;

use App\Models\Language;

interface TranslationServiceInterface
{
    public function translateNmt(array $translatables, Language $source, Language $target): array;

    public function translateLlm(array $translatables, Language $source, Language $target, array $options = []): array;
}
