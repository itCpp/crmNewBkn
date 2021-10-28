<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsStorySectorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests_story_sectors', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('request_id')->comment('Идентификатор заявки');
            $table->integer('old_sector', 50)->nullable()->comment('Сектор до смены');
            $table->integer('new_sector', 50)->nullable()->comment('Новый сектор');
            $table->bigInteger('story_id')->comment('Идентификатор основной записи лога');
            $table->timestamp('created_at')->useCurrent()->comment('Время смены');

            $table->foreign('request_id')->references('id')->on('requests_rows')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('story_id')->references('id')->on('requests_stories')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('requests_story_sectors');
    }
}
