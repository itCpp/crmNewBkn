<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

class AddColumnStatusesNotTabsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tabs', function (Blueprint $table) {
            $table->json('statuses_not')->comment('Статусы заявок, скрытые от вывода во вкладке')->default(new Expression('(JSON_ARRAY())'))->after('statuses');
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
            $table->dropColumn('statuses_not');
        });
    }
}
