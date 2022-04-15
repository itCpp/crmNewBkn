<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnAbbrNameRequestsSourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('requests_sources', function (Blueprint $table) {
            $table->string('abbr_name')->nullable()->comment('Сокращенное наименование для вывода на дисплеи при входящем звонке')->after('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('requests_sources', function (Blueprint $table) {
            $table->dropColumn('abbr_name');
        });
    }
}
