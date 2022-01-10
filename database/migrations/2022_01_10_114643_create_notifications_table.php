<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('notif_type', 50)->nullable()->comment('Тип уведомления');
            $table->text('notification')->nullable();
            $table->bigInteger('user_by_id')->nullable()->comment('Уведомление от пользователя');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('readed_at')->nullable();
            $table->softDeletes();
            $table->timestamp('updated_at')->nullable();

            $table->index(['user_id', 'deleted_at'], "user_id");
            $table->index(['user_id', 'notif_type', 'deleted_at'], "user_id_type");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}
