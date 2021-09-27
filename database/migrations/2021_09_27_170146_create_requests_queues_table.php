<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsQueuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests_queues', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 255)->nullable()->comment("Номер телефона клиента");
            $table->string('name', 255)->nullable()->comment("Имя клиента");
            $table->longText('comment')->nullable()->comment("Комментарий");
            $table->string('ip', 100)->nullable()->comment("IP адрес обращения");
            $table->string('site', 100)->nullable()->comment("Сайт-источник обращения");
            $table->json('gets')->nullable()->comment("Дполнительные параметры запроса");
            $table->integer('done')->nullable()->comment("Принятое решение: 1 - отклонить, 2 - добавить");
            $table->string('done_pin')->nullable()->comment("Сотрудник, принявший решение");
            $table->dateTime('done_at')->nullable()->comment("Время принятия решения");
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
        Schema::dropIfExists('requests_queues');
    }
}
