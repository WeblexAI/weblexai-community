<?php

use App\Enums\ModelStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->string('name');
            $table->string('country_name');
            $table->string('color')->default('#343434');
            $table->string('iso_2')->unique()->index();
            $table->string('iso_3')->unique()->index();
            $table->boolean('is_active')->default(ModelStatus::ACTIVE);
            $table->foreignId('created_by_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
