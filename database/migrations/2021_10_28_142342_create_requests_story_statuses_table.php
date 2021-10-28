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
            $table->bigInteger('story_id')->comment('Идентификатор основной записи лога');
            $table->bigInteger('request_id')->comment('Идентификатор заявки');
            $table->string('status_old', 100)->nullable()->comment('Старый статус');
            $table->string('status_new', 100)->nullable()->comment('Новый статус');
            $table->string('created_pin', 50)->nullable()->comment('Сотрудник, внесший изменения');
            $table->timestamp('created_at')->useCurrent()->comment('Время смены статуса');

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
        Schema::dropIfExists('requests_story_statuses');
    }
}
