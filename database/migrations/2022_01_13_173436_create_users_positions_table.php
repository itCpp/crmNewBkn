<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersPositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_positions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->comment('Наименование должности');
            $table->text('description')->nullable()->comment('Опсиание должности, должностные обязанности или инструкции и тд...');
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
        Schema::dropIfExists('users_positions');
    }
}
