<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnPinRequestsCounterStoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('requests_counter_stories', function (Blueprint $table) {
            $table->string('to_pin', 50)->nullable()->comment('Для сотрудника')->after('counter_data');

            $table->index(['counter_date', 'to_pin'], 'date_pin');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('requests_counter_stories', function (Blueprint $table) {
            $table->dropColumn('to_pin');
            $table->dropIndex('date_pin');
        });
    }
}
