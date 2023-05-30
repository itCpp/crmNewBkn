<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnDomainSettingsQueuesDatabases extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings_queues_databases', function (Blueprint $table) {
            $table->string('domain', 100)->nullable()->comment("Домен сайта")->after('active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings_queues_databases', function (Blueprint $table) {
            $table->dropColumn('domain');
        });
    }
}
