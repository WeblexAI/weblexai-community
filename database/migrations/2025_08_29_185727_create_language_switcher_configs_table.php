<?php

use App\Enums\ModelStatus;
use App\Enums\SwitcherDeviceType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('language_switcher_configs', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            $table->string('target_parent_selector')->nullable();
            $table->boolean('should_display_name')->default(true);
            $table->boolean('should_display_full_name')->default(true);
            $table->boolean('should_display_flag')->default(true);
            $table->integer('size')->default(50);

            $table->boolean('should_open_on_hover')->default(false);
            $table->boolean('should_close_on_outside_click')->default(false);
            $table->boolean('should_show_by_device')->default(false);
            $table->string('preferred_device')->default(SwitcherDeviceType::DESKTOP);
            $table->integer('device_pixel_breakpoint')->default(768);

            $table->boolean('is_active')->default(ModelStatus::ACTIVE);
            $table->foreignId('created_by_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('language_switcher_configs');
    }
};
