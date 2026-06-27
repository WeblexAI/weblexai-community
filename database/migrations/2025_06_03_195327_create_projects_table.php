<?php

use App\Enums\ModelStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('provider_credential_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('api_key_hash', 64)->unique();
            $table->text('api_key')->nullable()->after('api_key_hash');
            $table->foreignId('original_language_id')->nullable();
            $table->timestamp('pinged_at')->nullable();
            $table->boolean('should_display_automatics')->default(true);
            $table->mediumText('website_description')->nullable();
            $table->string('translation_tone')->nullable();
            $table->string('translation_audience')->nullable();
            $table->string('is_active')->default(ModelStatus::ACTIVE->value);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
