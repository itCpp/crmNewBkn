<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

class AddSmsColumnOfficesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->string('sms', 500)->nullable()->comment('Шаблон смс')->after('address');
            $table->json('statuses')->default(new Expression('(JSON_ARRAY())'))->comment('Статусы, для формирвоания шаблона смс')->after('sms');
            $table->string('tel', 15)->nullable()->comment('Номер секретаря по умолчанию')->after('statuses');
            $table->index(['base_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn('sms');
            $table->dropColumn('statuses');
            $table->dropColumn('tel');
            $table->dropIndex(['base_id']);
        });
    }
}
