<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateIncomingQueriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('incoming_queries', function (Blueprint $table) {
            $table->string('hash_phone', 50)->nullable()->index()->after('type');
            $table->string('hash_phone_resource', 50)->nullable()->index()->after('hash_phone');
            $table->renameColumn('source', 'ad_source');
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
            $table->dropColumn('hash_phone');
            $table->dropColumn('hash_phone_resource');
            $table->renameColumn('ad_source', 'source');
        });
    }
}
