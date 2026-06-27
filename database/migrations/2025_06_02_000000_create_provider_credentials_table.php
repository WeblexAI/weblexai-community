<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_credentials', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('provider');
            $table->text('api_key')->nullable();
            $table->text('service_account')->nullable();
            $table->string('google_project_id')->nullable();
            $table->string('model')->nullable();
            $table->string('base_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
            $table->index(['user_id', 'provider', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_credentials');
    }
};
