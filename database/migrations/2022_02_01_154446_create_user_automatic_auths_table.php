<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAutomaticAuthsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_automatic_auths', function (Blueprint $table) {
            $table->id();
            $table->string('token', 100)->index();
            $table->string('pin', 50)->comment('Персональный идентификационный номер старой ЦРМ');
            $table->string('ip', 100)->comment('IP адрес устройства, создавшего запрос');
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('auth_at')->nullable()->comment('Время авторизации');
            $table->timestamps();

            $table->index(['token', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_automatic_auths');
    }
}
