<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncomingSecondCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('incoming_second_calls', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('client_id')->comment('Идентификтаор клиента');
            $table->bigInteger('request_id')->nullable()->comment('Идентификтаор заявки');
            $table->date('call_date')->comment('Дата звонка');
            $table->timestamps();

            $table->index('call_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('incoming_second_calls');
    }
}
