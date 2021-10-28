<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsStoryStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests_story_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->nullable()->comment('Идентификатор основной записи лога')->constrained('requests_stories');
            $table->foreignId('request_id')->nullable()->comment('Идентификатор заявки')->constrained('requests_rows');
            $table->string('status_old', 100)->nullable()->comment('Старый статус');
            $table->string('status_new', 100)->nullable()->comment('Новый статус');
            $table->string('created_pin', 50)->nullable()->comment('Сотрудник, внесший изменения');
            $table->timestamp('created_at')->useCurrent()->comment('Время смены статуса');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('requests_story_statuses');
    }
}
