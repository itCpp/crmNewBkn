<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

class CreateGatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gates', function (Blueprint $table) {
            $table->id();
            $table->string('addr', 50)->unique()->comment('Адрес шлюза');
            $table->string('ami_user', 50)->nullable()->comment('Логин доступа API');
            $table->string('ami_pass', 255)->nullable()->comment('Пароль доступа API');
            $table->integer('channels')->default(0)->comment('Количество каналов');
            $table->boolean('for_sms')->default(0)->comment('Можно использовать для отправки смс');
            $table->boolean('check_incoming_sms')->default(0)->comment('Проверять входящие смс');
            $table->json('headers')->default(new Expression('(JSON_ARRAY())'))->comment('Заголовки для подготовки запросов');
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
        Schema::dropIfExists('gates');
    }
}
