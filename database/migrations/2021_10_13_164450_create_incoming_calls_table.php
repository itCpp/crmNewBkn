<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncomingCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('incoming_calls', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 500)->nullable()->comment("Номер клиента");
            $table->string('phone', 50)->nullable()->comment("Номер сип, на который поступил звонок");
            $table->timestamps();
            $table->tinyInteger('locked')->default(0)->comment("Заблокировано для добавления");
            $table->dateTime('added')->nullable()->comment("Добавлено в заявку");
            $table->dateTime('failed')->nullable()->comment("Неудачная попытка добавления");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('incoming_calls');
    }
}
