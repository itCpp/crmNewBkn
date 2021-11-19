<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_id', 50)->nullable()->comment('Идентификатор сообщения');
            $table->string('gate', 50)->nullable()->comment('Адрес шлюза');
            $table->integer('channel')->nullable()->comment('Номер канала');
            $table->string('created_pin', 50)->nullable()->comment('Персональный идентификационный номер сотрудника');
            $table->string('phone', 255)->nullable()->comment('Номер телефона');
            $table->text('message')->nullable()->comment('Текст сообщения');
            $table->string('direction')->nullable()->comment('Направление сообщения');
            $table->timestamp('sent_at')->nullable()->comment('Дата получения или отправки');
            $table->timestamps();
        });

        Schema::create('sms_request', function (Blueprint $table) {
            $table->foreignId('sms_id')->constrained('sms_messages')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('request_id')->constrained('requests_rows')->onUpdate('cascade')->onDelete('cascade');
            $table->unique(['sms_id', 'request_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sms_request');
        Schema::dropIfExists('sms_messages');
    }
}
