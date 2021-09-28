<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserWorkTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_work_times', function (Blueprint $table) {
            $table->id();
            $table->integer("user_pin")->comment("Персональный идентификационный номер пользователя");
            $table->string('event_type', 50)->nullable()->comment("Тип события");
            $table->date('date')->nullable()->comment("Дата события");
            $table->timestamp('created_at')->comment("Время начала события");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_work_times');
    }
}
