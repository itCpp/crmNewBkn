<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCountColumnRequestsSourcesResourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('requests_sources_resources', function (Blueprint $table) {
            $table->integer('count_requests')->default(0)->comment("Счетчик обращений")->after('val');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('requests_sources_resources', function (Blueprint $table) {
            $table->dropColumn('count_requests');
        });
    }
}
