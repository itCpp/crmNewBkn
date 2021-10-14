<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTabUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tab_user', function (Blueprint $table) {
            $table->bigInteger('tab_id');
            $table->bigInteger('user_id');
            $table->foreignId('tab_id')->constrained('tabs');
            $table->foreignId('user_id')->constrained('users');
            $table->unique(['tab_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tab_user');
    }
}
