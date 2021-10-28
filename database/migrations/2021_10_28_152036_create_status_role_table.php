<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatusRoleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('status_role', function (Blueprint $table) {
            $table->string('role', 50);
            $table->unsignedBigInteger('status_id');

            $table->foreign('role')->references('role')->on('roles');
            $table->foreign('status_id')->references('id')->on('statuses');

            $table->unique(['role', 'status_id'], 'role_status_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('status_role');
    }
}
