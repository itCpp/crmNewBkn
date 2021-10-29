<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeSoursIdColumnRequestsSourcesResourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('requests_sources_resources', function (Blueprint $table) {
            $table->unsignedBigInteger('source_id')->nullable()->comment('Идентификатор источника')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table->unsignedBigInteger('source_id')->comment('Идентификатор источника')->change();
    }
}
