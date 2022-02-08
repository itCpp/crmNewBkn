<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallDetailRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('call_detail_records', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('event_id')->nullable();
            $table->string('phone', 255)->nullable();
            $table->string('phone_hash', 100)->nullable()->comment("Хеш телефона для поиска");
            $table->string('extension', 255)->nullable();
            $table->string('path', 255)->nullable()->comment("Путь до файла на сервере");
            $table->timestamp('call_at')->nullable()->comment("Время вызова");
            $table->string('type', 50)->comment("Направление звонка: in, out");
            $table->integer('duration')->nullable()->comment("Длина разговора");
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
        Schema::dropIfExists('call_detail_records');
    }
}
