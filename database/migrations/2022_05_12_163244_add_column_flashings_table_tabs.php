<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnFlashingsTableTabs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tabs', function (Blueprint $table) {
            $table->boolean('flash_null')->default(0)->comment("Подсчитывать количество необработанных заявок для индикации")->after('counter_hide_page');
            $table->boolean('flash_records_confirm')->default(0)->comment("Индикация подтверждения записей")->after('flash_null');
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
            $table->dropColumn([
                'flash_null',
                'flash_records_confirm',
            ]);
        });
    }
}
