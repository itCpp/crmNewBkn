<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnUtmSourceIncomingQueriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('incoming_queries', function (Blueprint $table) {
            $table->string('utm_source', 100)->nullable()->after('request_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('incoming_queries', function (Blueprint $table) {
            $table->dropColumn('utm_source');
        });
    }
}
