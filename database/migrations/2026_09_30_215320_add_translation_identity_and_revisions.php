<?php

use App\Models\Page;
use App\Support\TextHasher;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->unsignedBigInteger('delivery_revision')->default(0);
            $table->unsignedBigInteger('generation_revision')->default(0);
        });
        Schema::table('translations', function (Blueprint $table): void {
            $table->string('context_hash', 32)->default('');
            $table->text('source_context')->default('');
            $table->boolean('needs_regeneration')->default(false);
        });

        $duplicates = DB::table('pages')->select('project_id', 'domain')
            ->groupBy('project_id', 'domain')->havingRaw('COUNT(*) > 1')->get();
        foreach ($duplicates as $duplicate) {
            $pages = DB::table('pages')->where('project_id', $duplicate->project_id)
                ->where('domain', $duplicate->domain)->orderBy('id')->get();
            $canonical = $pages->first();
            $otherIds = $pages->pluck('id')->slice(1)->all();
            DB::table('translations')->whereIn('page_id', $otherIds)->update(['page_id' => $canonical->id]);
            DB::table('translation_requests')->whereIn('page_id', $otherIds)->update(['page_id' => $canonical->id]);
            DB::table('views')->where('viewable_type', (new Page)->getMorphClass())
                ->whereIn('viewable_id', $otherIds)->update(['viewable_id' => $canonical->id]);
            DB::table('pages')->where('id', $canonical->id)->update([
                'is_blacklisted' => $pages->contains(fn (object $page): bool => (bool) $page->is_blacklisted),
                'is_active' => $pages->every(fn (object $page): bool => (bool) $page->is_active),
            ]);
            DB::table('pages')->whereIn('id', $otherIds)->delete();
        }
        DB::table('translations')->orderBy('id')->chunkById(500, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('translations')->where('id', $row->id)->update([
                    'type' => $row->type ?: 'text',
                    'attr' => $row->attr ?? '',
                    'text_hash' => TextHasher::hash($row->text),
                ]);
            }
        });
        $identity = ['project_id', 'page_id', 'target_lang_id', 'type', 'attr', 'text_hash', 'context_hash'];
        $duplicates = DB::table('translations')->select($identity)->groupBy($identity)
            ->havingRaw('COUNT(*) > 1')->get();
        foreach ($duplicates as $duplicate) {
            $query = DB::table('translations');
            foreach ($identity as $column) {
                $query->where($column, $duplicate->{$column});
            }
            $ids = (clone $query)->orderByRaw("CASE WHEN quality = 'manual' THEN 0 ELSE 1 END")
                ->orderByRaw("CASE WHEN quality = 'manual' THEN updated_at END DESC")
                ->orderByDesc('is_reviewed')->orderByDesc('updated_at')->orderByDesc('id')->pluck('id');
            $query->where('id', '!=', $ids->first())->delete();
        }
        Schema::table('translations', function (Blueprint $table) use ($identity): void {
            $table->string('type')->nullable(false)->default('text')->change();
            $table->string('attr')->nullable(false)->default('')->change();
            $table->unique($identity, 'translations_identity_unique');
        });
        Schema::table('pages', fn (Blueprint $table) => $table->unique(['project_id', 'domain'], 'pages_identity_unique'));
    }

    public function down(): void
    {
        Schema::table('pages', fn (Blueprint $table) => $table->dropUnique('pages_identity_unique'));
        Schema::table('translations', function (Blueprint $table): void {
            $table->dropUnique('translations_identity_unique');
            $table->dropColumn(['context_hash', 'source_context', 'needs_regeneration']);
            $table->string('type')->nullable()->default(null)->change();
            $table->string('attr')->nullable()->default(null)->change();
        });
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn(['delivery_revision', 'generation_revision']));
    }
};
