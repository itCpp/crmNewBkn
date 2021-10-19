<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TableTabsAddSettingsColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tabs', function (Blueprint $table) {
            $table->string('request_all', 50)->nullable()->comment("Вывод заявок по условиям разрешений")->after('order_by_settings');
            $table->tinyInteger('request_all_permit')->default(0)->comment("Учитывать настройки разрешений при выбранной опции request_all")->after('request_all');
            $table->tinyInteger('date_view')->default(0)->comment("1 - Показывать за все время, 0 - Показывать с учетом выбранного периода")->after('request_all_permit');
            $table->json('date_types')->nullable()->comment("Учитываемые даты при выводе заявок")->after('date_view');
            $table->json('statuses')->nullable()->comment("Идентификаторы статусов")->after('date_types');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tabs', function (Blueprint $table) {
            $table->dropColumn('request_all');
            $table->dropColumn('request_all_permit');
            $table->dropColumn('date_view');
            $table->dropColumn('date_types');
            $table->dropColumn('statuses');
        });
    }
}
