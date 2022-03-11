<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsIpPeriodBlockIpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('block_ips', function (Blueprint $table) {
            $table->tinyInteger('is_period')->default(0)->comment('1 - Является периодом адресов')->after('hostname');
            $table->json('period_data')->nullable()->after('is_period');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('block_ips', function (Blueprint $table) {
            $table->dropColumn(['is_period', 'period_data']);
        });
    }
}
