<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGateSmsCountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gate_sms_counts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('gate_id')->nullable();
            $table->bigInteger('channel_id')->nullable();
            $table->date('date')->nullable();
            $table->bigInteger('count')->default(0);
            $table->timestamps();
            $table->index(['gate_id', 'channel_id', 'date'], 'count_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gate_sms_counts');
    }
}
