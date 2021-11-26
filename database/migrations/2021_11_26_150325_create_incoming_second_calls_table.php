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
            $table->bigInteger('client_id')->comment('Идентификатор клиента');
            $table->json('request_id')->nullable()->comment('Идентификаторы заявок');
            $table->date('call_date')->comment('Дата звонка');
            $table->timestamps();

            $table->index('call_date');
            $table->index(['call_date', 'created_at'], 'call_date_created_at');
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
