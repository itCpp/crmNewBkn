<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatIdUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_id_user', function (Blueprint $table) {
            $table->string('chat_id', 50)->comment('Идентификатор чата, он же id сотрудника');
            $table->string('user_id', 50)->comment('Идентификатор сотрудника');
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
        Schema::dropIfExists('chat_id_user');
    }
}
