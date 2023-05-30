<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatUsersIdChangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_users_id_changes', function (Blueprint $table) {
            $table->id();
            $table->integer('new_id')->nullable();
            $table->integer('old_id')->nullable();
            $table->string('pin', 50)->nullable();
            $table->integer('crm_old_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chat_users_id_changes');
    }
}
