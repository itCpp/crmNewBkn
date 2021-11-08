<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeForeignKeyRequestsStoryPinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('requests_story_pins', function (Blueprint $table) {
            $table->dropForeign(['story_id']);
            $table->dropForeign(['request_id']);
            $table->foreign('story_id')->references('id')->on('requests_stories')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('request_id')->references('id')->on('requests_rows')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
