<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnPinCallDetailRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('call_detail_records', function (Blueprint $table) {
            $table->string('operator', 50)->nullable()->after("extension");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('call_detail_records', function (Blueprint $table) {
            $table->dropColumn('operator');
        });
    }
}
