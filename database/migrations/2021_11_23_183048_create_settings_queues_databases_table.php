<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsQueuesDatabasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings_queues_databases', function (Blueprint $table) {
            $table->id();
            $table->string('host', 255);
            $table->string('port', 255)->nullable();
            $table->string('user', 255);
            $table->string('password', 255)->nullable();
            $table->string('database', 255);
            $table->string('table_name', 255)->nullable();
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
        Schema::dropIfExists('settings_queues_databases');
    }
}
