<?php

use App\Enums\ModelStatus;
use App\Enums\TranslationQuality;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->longText('text');
            $table->longText('translated');
            $table->string('text_hash', 32)->index();
            $table->string('type')->nullable();
            $table->string('attr')->nullable();
            $table->foreignId('source_lang_id');
            $table->foreignId('target_lang_id');
            $table->integer('total_words');
            $table->boolean('is_original')->default(false);
            $table->boolean('is_on')->default(true);
            $table->boolean('is_reviewed')->default(false);
            $table->string('quality')->default(TranslationQuality::AUTOMATIC);
            $table->boolean('is_active')->default(ModelStatus::ACTIVE);
            $table->foreignId('created_by_id')->nullable();
            $table->timestamps();
            $table->timestamp('last_used_at')->nullable()->index();
            $table->index(['project_id', 'target_lang_id'], 'idx_translations_project_target_lang');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            DB::statement('CREATE INDEX translations_text_trgm_idx ON translations USING gin ("text" gin_trgm_ops)');
            DB::statement('CREATE INDEX translations_translated_trgm_idx ON translations USING gin (translated gin_trgm_ops)');
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS translations_text_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS translations_translated_trgm_idx');
        Schema::dropIfExists('translations');
    }
};
