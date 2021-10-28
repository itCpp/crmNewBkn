<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsStoryPinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests_story_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->nullable()->comment('Идентификатор заявки')->constrained('requests_rows');
            $table->string('old_pin', 50)->nullable()->comment('Оператор до смены');
            $table->string('new_pin', 50)->nullable()->comment('Новый оператор');
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
        Schema::dropIfExists('requests_story_pins');
    }
}
