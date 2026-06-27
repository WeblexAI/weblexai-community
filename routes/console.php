<?php

use App\Models\TranslationRequest;
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune', ['--model' => [TranslationRequest::class]])
    ->daily()
    ->runInBackground();
