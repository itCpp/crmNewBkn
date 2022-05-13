<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallcenterSectorsAutoSetSourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('callcenter_sectors_auto_set_sources', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('sector_id')->index();
            $table->bigInteger('source_id')->unique();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('callcenter_sectors_auto_set_sources');
    }
}
