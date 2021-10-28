<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->nullable()->comment('Наименование статуса');
            $table->integer('theme')->nullable()->comment('Цветовая тема строки заявки');
            $table->tinyInteger('zeroing')->default(0)->comment('Обнуление заявки при поступлении');
            $table->tinyInteger('event_time')->default(0)->comment('1 - Необходимо установить время события');
            $table->json('zeroing_data')->nullable()->comment('Настройки обнуления');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('statuses');
    }
}
