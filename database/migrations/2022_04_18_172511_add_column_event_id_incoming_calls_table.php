<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnEventIdIncomingCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('incoming_calls', function (Blueprint $table) {
            $table->bigInteger('event_id')->nullable()->comment('Идентификатор события входящего запроса')->after('sip');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('incoming_calls', function (Blueprint $table) {
            $table->dropColumn('event_id');
        });
    }
}
