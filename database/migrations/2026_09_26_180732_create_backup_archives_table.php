<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_archives', function (Blueprint $table) {
            $table->id();
            $table->string('path')->unique();
            $table->text('archive_password');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_archives');
    }
};
