<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsSourceTypeIncomingQueriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('incoming_queries', function (Blueprint $table) {
            $table->string('source', 100)->nullable()->after('request_id')->index();
            $table->string('type', 100)->nullable()->after('source')->index();
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
            $table->dropColumn(['source', 'type']);
        });
    }
}
