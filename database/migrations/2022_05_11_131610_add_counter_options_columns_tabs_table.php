<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCounterOptionsColumnsTabsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tabs', function (Blueprint $table) {
            $table->boolean('counter_source')->default(0)->comment("Разделить счетчик на источники")->after('statuses_not');
            $table->boolean('counter_offices')->default(0)->comment("Разделить счетчик по офисам")->after('counter_source');
            $table->boolean('counter_next_day')->default(0)->comment("Подсчет данных для следующего дня")->after('counter_offices');
            $table->boolean('counter_hide_page')->default(0)->comment("Скрыть график вкладки на странице счетчиков")->after('counter_next_day');
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
                'counter_source',
                'counter_offices',
                'counter_next_day',
                'counter_hide_page'
            ]);
        });
    }
}
