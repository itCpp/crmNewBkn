<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

class AddColumnsRowDataCreatedRequestsStoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('requests_stories', function (Blueprint $table) {
            $table->renameColumn('request_data', 'row_data');
        });
        Schema::table('requests_stories', function (Blueprint $table) {
            $table->json('request_data')->default(new Expression('(JSON_ARRAY())'))->after('row_data')->comment('Данные входящего запроса');
            $table->tinyInteger('created')->default(0)->comment('1 - поступила заявка, 0 - изменение данных заявки')->after('request_data')->index();
            $table->string('ip', 100)->nullable()->after('created_pin');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('requests_stories', function (Blueprint $table) {
            $table->dropColumn(['created', 'request_data', 'ip']);
            $table->renameColumn('row_data', 'request_data');
        });
    }
}
