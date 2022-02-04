<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TableLogsAddColumnsTableNameConnectionName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->string('connection_name', 255)->nullable()->comment('Наименование подключения')->after('user_id');
            $table->string('database_name', 255)->nullable()->comment('Наименование базы данных')->after('connection_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::table('logs', function (Blueprint $table) {
        //     $table->dropColumn('connection_name');
        //     $table->dropColumn('database_name');
        // });
    }
}
