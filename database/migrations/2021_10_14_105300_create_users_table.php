<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->integer('pin')->nullable()->comment('Персональный идентификационный номер')->unique();
            $table->string('old_pin', 50)->nullable()->comment('Идентификатор из старой ЦРМ')->index();
            $table->string('login', 250)->nullable()->comment('Логин для авторизации')->unique();
            $table->integer('callcenter_id')->nullable()->comment('Идентификатор колл-центра');
            $table->integer('callcenter_sector_id')->nullable()->comment('Идентификатор сектора колл-центра');
            $table->string('surname', 250)->nullable()->comment('Фамилия');
            $table->string('name', 250)->nullable()->comment('Имя');
            $table->string('patronymic', 250)->nullable()->comment('Отчество');
            $table->string('password', 250)->nullable()->comment('Хэш пароля');
            $table->string('telegram_id', 100)->nullable()->comment('Идентификатор Телеграм чата');
            $table->string('auth_type', 50)->nullable()->comment('Тип авторизации');
            $table->timestamps();
            $table->softDeletes()->comment("Время удаления или блокировки");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
