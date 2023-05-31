<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaillerLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mailler_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailler_id')->comment('Идентификатор обработчика')->constrained();
            $table->json('request_data')->nullable()->comment('Данные обработчика');
            $table->json('response_data')->nullable()->comment('Результат обработки');
            $table->timestamp('start_at')->nullable()->comment('Дата и время начала обработки');
            $table->timestamp('send_at')->nullable();
            $table->boolean('is_send')->default(false)->comment('Отправлено');
            $table->timestamp('failed_at')->nullable();
            $table->boolean('is_failed')->default(false)->comment('Невыполненная обработка');
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
        Schema::dropIfExists('mailler_logs');
    }
}
