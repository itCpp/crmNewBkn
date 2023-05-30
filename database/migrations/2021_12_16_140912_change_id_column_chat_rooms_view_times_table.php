<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeIdColumnChatRoomsViewTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('chat_rooms_view_times', function (Blueprint $table) {
            $table->renameColumn('id', 'chat_id');
        });
        Schema::table('chat_rooms_view_times', function (Blueprint $table) {
            $table->id()->first();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('chat_rooms_view_times', function (Blueprint $table) {
            $table->dropColumn('id');
        });
        Schema::table('chat_rooms_view_times', function (Blueprint $table) {
            $table->renameColumn('chat_id', 'id');
        });
    }
}
