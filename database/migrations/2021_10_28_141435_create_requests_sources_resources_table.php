<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsSourcesResourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests_sources_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->comment('Идентификатор источника')->constrained('requests_sources');
            $table->string('type', 50)->nullable()->comment('Тип ресурса');
            $table->string('val', 150)->nullable()->comment('Значение ресурса');
            $table->timestamps();

            $table->index(['source_id', 'type', 'val']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('requests_sources_resources');
    }
}
