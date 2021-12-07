<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('pin', 50)->comment('Автор сообщения')->index();
            $table->integer('chat_id')->comment('Идентификатор чата')->index();
            $table->string('type', 255)->nullable()->comment('Тип сообщения');
            $table->text('message')->comment('Содержание сообщения');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['chat_id', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chat_messages');
    }
}
