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
            $table->foreignId('story_id')->comment('Идентификатор основной записи лога')->constrained('requests_rows')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('request_id')->comment('Идентификатор заявки')->constrained('requests_stories')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('old_sector')->nullable()->comment('Сектор до смены');
            $table->integer('new_sector')->nullable()->comment('Новый сектор');
            $table->timestamp('created_at')->useCurrent()->comment('Время смены');
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
