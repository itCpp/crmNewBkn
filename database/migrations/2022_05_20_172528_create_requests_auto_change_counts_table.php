<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsAutoChangeCountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests_auto_change_counts', function (Blueprint $table) {
            $table->id();
            $table->integer('pin');
            $table->date('date');
            $table->integer('count');
            $table->timestamps();

            $table->index(['pin', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('requests_auto_change_counts');
    }
}
