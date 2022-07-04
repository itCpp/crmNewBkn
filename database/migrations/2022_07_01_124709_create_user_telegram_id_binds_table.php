<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTelegramIdBindsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_telegram_id_binds', function (Blueprint $table) {
            $table->id();
            $table->integer('user_pin')->nullable()->comment('Персональный идентификационный номер сотрудника')->index();
            $table->integer('code')->nullable()->comment('Код привязки');
            $table->bigInteger('telegram_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['code', 'created_at', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_telegram_id_binds');
    }
}
