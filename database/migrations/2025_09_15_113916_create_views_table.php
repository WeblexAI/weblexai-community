<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateViewsTable extends Migration
{
    protected $schema;

    protected $table;

    public function __construct()
    {
        $this->schema = Schema::connection(
            config('eloquent-viewable.models.view.connection')
        );

        $this->table = config('eloquent-viewable.models.view.table_name');
    }

    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->morphs('viewable');
            $table->text('visitor');
            $table->string('collection')->nullable();
            $table->timestamp('viewed_at')->useCurrent();

            $table->foreignId('project_id')->nullable()->index()->constrained()->cascadeOnDelete();
            $table->ipAddress()->nullable()->index();
            $table->string('country')->nullable()->index();
            $table->foreignId('target_lang_id')->nullable()->constrained('languages')->cascadeOnDelete();
            $table->foreignId('browser_lang_id')->nullable()->constrained('languages')->cascadeOnDelete();
            $table->string('city')->nullable();
            $table->string('lat')->nullable();
            $table->string('lon')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->table);
    }
}
