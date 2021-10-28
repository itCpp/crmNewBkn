<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('status_old', 100)->comment('Токен аутентификации');
            $table->foreignId('user_id')->comment('Идентификатор основной записи лога')->constrained('users');
            $table->string('user_pin', 50)->comment('Персональный идентификационный номер пользователя');
            $table->timestamp('created_at')->nullable()->comment('Время создания');
            $table->timestamp('deleted_at')->nullable()->comment('При наличии, считается удаленным');
            $table->timestamp('active_at')->nullable()->comment('Время последней активности');
            $table->string('ip', 100)->comment('Адрес авторизации');
            $table->string('user_agent', 500)->comment('Клиент авторизации пользователя');
            $table->timestamp('updated_at')->nullable()->comment('Время обновления строки');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_sessions');
    }
}
