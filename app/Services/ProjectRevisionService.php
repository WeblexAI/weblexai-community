<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ProjectRevisionService
{
    public function bumpDelivery(int $projectId): void
    {
        DB::table('projects')->where('id', $projectId)->increment('delivery_revision');
    }

    public function bumpGeneration(int $projectId): void
    {
        DB::table('projects')->where('id', $projectId)->update([
            'delivery_revision' => DB::raw('delivery_revision + 1'),
            'generation_revision' => DB::raw('generation_revision + 1'),
        ]);
    }
}
