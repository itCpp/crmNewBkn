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
            $table->unsignedBigInteger('source_id')->comment('Идентификатор источника');
            $table->string('type', 50)->nullable()->comment('Тип ресурса');
            $table->string('val', 150)->nullable()->comment('Значение ресурса');
            $table->boolean('check_site')->default(0)->comment('Проверять статус сайта');
            $table->timestamps();

            $table->index(['source_id', 'type', 'val']);
            $table->foreign('source_id')->references('id')->on('requests_sources')->onUpdate('cascade')->onDelete('cascade');
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
