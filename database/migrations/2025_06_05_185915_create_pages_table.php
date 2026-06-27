<?php

use App\Enums\ModelStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('domain');
            $table->string('origin');
            $table->boolean('is_blacklisted')->default(false);
            $table->json('blacklisted_languages')->nullable();
            $table->boolean('is_active')->default(ModelStatus::ACTIVE);
            $table->foreignId('created_by_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
