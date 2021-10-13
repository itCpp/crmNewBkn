<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable()->comment("Идентификатор сотрудника");
            $table->string('table_name', 255)->nullable()->comment("Наименование таблицы");
            $table->bigInteger('row_id')->nullable()->comment("Идентификатор строки");
            $table->json('row_data')->nullable()->comment("Данные строки");
            $table->json('request_data')->nullable()->comment("Входящие данные");
            $table->timestamp('created_at')->useCurrent()->comment("Время внесения изменения");
            $table->string('ip', 150)->nullable()->comment("IP адреса обращения");
            $table->string('user_agent', 500)->nullable()->comment("Пользовательский клиент");
            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logs');
    }
}
