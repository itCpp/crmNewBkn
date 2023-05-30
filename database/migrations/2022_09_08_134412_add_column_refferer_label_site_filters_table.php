<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnReffererLabelSiteFiltersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('site_filters', function (Blueprint $table) {
            $table->string('refferer_label')->nullable()->after('utm_label');
            $table->index(['site_id', 'utm_label', 'deleted_at'], 'utm_lable_index');
            $table->index(['site_id', 'refferer_label', 'deleted_at'], 'refferer_label_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('site_filters', function (Blueprint $table) {
            $table->dropIndex('utm_lable_index');
            $table->dropIndex('refferer_label_index');
            $table->dropColumn('refferer_label');
        });
    }
}
