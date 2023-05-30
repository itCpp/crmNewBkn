<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsRowsConfirmedCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests_rows_confirmed_comments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('request_id')->index();
            $table->boolean('confirmed')->nullable()->comment("0 - Не верно, 1 - Верно, NULL - не определено");
            $table->string('confirm_pin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('requests_rows_confirmed_comments');
    }
}
