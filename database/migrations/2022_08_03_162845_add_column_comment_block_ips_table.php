<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnCommentBlockIpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('block_ips', function (Blueprint $table) {
            $table->string('comment', 500)->nullable()->comment("Комментарий по IP")->after('hostname');
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
            $table->dropColumn('comment');
        });
    }
}
