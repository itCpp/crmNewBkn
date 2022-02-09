<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnNameGatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gates', function (Blueprint $table) {
            $table->string('name', 255)->nullable()->comment("Наименование шлюза")->after('addr');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gates', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
}
