<?php

use App\Enums\ModelStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('glossaries', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('text');
            $table->string('translated')->nullable();
            $table->string('placeholder')->unique()->index();
            $table->boolean('is_case_sensitive')->default(false);
            $table->boolean('is_all_languages')->default(true);
            $table->string('rule');
            $table->boolean('is_active')->default(ModelStatus::ACTIVE);
            $table->foreignId('created_by_id')->nullable();
            $table->timestamps();

        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            DB::statement('CREATE INDEX glossaries_text_trgm_idx ON glossaries USING gin ("text" gin_trgm_ops)');
            DB::statement('CREATE INDEX glossaries_translated_trgm_idx ON glossaries USING gin (translated gin_trgm_ops)');
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS glossaries_text_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS glossaries_translated_trgm_idx');
        Schema::dropIfExists('glossaries');
    }
};
