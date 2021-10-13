<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncomingQueriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('incoming_queries', function (Blueprint $table) {
            $table->id();
            $table->json('query_data')->nullable()->comment("Входящие данные");
            $table->bigInteger('client_id')->nullable()->comment("Идентификатор клиента");
            $table->bigInteger('request_id')->nullable()->comment("Идентификатор созданной или обновленной заявки");
            $table->json('request_data')->nullable()->comment("Данные обновленной заявки");
            $table->json('response_data')->nullable()->comment("Результат обработки запроса");
            $table->string('ip', 150)->nullable()->comment("IP адреса обращения");
            $table->string('user_agent', 500)->nullable()->comment("Пользовательский клиент");
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
        Schema::dropIfExists('incoming_queries');
    }
}
