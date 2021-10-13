<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncomingCallsToSourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('incoming_calls_to_sources', function (Blueprint $table) {
            $table->id();
            $table->string('extension', 100)->nullable()->comment("Учетная запись SIP");
            $table->string('phone', 50)->nullable()->comment("Номер телефона источника заявки");
            $table->tinyInteger('on_work')->default(0)->comment("Включен в работу для добавления заявок");
            $table->string('comment', 255)->nullable()->comment("Комментарий к процессу");
            $table->string('for_pin', 10)->nullable()->comment("При наличии, заявка автоматически выдастся этому сотруднику");
            $table->integer('added')->default(0)->comment("Добавлено заявок по слушателю");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('incoming_calls_to_sources');
    }
}
