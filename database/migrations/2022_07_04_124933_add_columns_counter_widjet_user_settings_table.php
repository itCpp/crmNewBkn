<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsCounterWidjetUserSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->boolean('counter_widjet_records')->default(0)->comment('Виджет счетчика записей')->after('short_menu');
            $table->boolean('counter_widjet_comings')->default(0)->comment('Виджет счетчика приходов')->after('counter_widjet_records');
            $table->boolean('counter_widjet_drain')->default(0)->comment('Виджет счетчика сливов')->after('counter_widjet_comings');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn('counter_widjet_records');
            $table->dropColumn('counter_widjet_comings');
            $table->dropColumn('counter_widjet_drain');
        });
    }
}
