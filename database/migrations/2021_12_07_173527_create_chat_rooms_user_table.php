<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatRoomsUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_rooms_user', function (Blueprint $table) {
            $table->bigInteger('chat_id')->comment('Идентификатор чата')->index();
            $table->bigInteger('user_id')->comment('Идентификатор сотрудника')->index();
            $table->unique(['chat_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chat_rooms_user');
    }
}
