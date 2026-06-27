<?php

use App\Enums\ModelStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid();

            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_lang_id')->constrained('languages')->cascadeOnDelete();
            $table->foreignId('target_lang_id')->constrained('languages')->cascadeOnDelete();
            $table->ipAddress('ip');
            $table->string('country')->nullable();

            $table->boolean('is_active')->default(ModelStatus::ACTIVE);
            $table->foreignId('created_by_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_requests');
    }
};
