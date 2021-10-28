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
            $table->foreignId('request_id')->nullable()->comment('Идентификатор заявки')->constrained('requests_rows');
            $table->integer('old_sector', 50)->nullable()->comment('Сектор до смены');
            $table->integer('new_sector', 50)->nullable()->comment('Новый сектор');
            $table->foreignId('story_id')->nullable()->comment('Идентификатор основной записи лога')->constrained('requests_stories');
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
