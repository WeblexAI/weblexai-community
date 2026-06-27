<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_accepted_origins', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('origin');
            $table->string('normalized_origin');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['project_id', 'normalized_origin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_accepted_origins');
    }
};
