<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TableIncomingCallsToSources extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('incoming_calls_to_sources', function (Blueprint $table) {
            $table->string('ad_place', 100)->nullable()->comment("Отношение к рекламной площадке")->after('on_work');
            $table->index(['extension']);
            $table->index(['extension', 'on_work']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('incoming_calls_to_sources', function (Blueprint $table) {
            $table->dropColumn('ad_place');
            $table->dropIndex(['extension']);
            $table->dropIndex(['extension', 'on_work']);
        });
    }
}
