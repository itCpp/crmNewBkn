<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests_clients', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 255)->nullable()->comment('Номер телефона');
            $table->string('hash', 100)->nullable()->comment('Хэш номера телефона для его поиска');
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
        Schema::dropIfExists('requests_clients');
    }
}
