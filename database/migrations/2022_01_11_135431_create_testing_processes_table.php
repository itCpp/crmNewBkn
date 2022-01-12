<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

class CreateTestingProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('testing_processes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();
            $table->string('pin', 50)->nullable()->comment('Персональный идентификационный номер сотрудника');
            $table->string('pin_old', 50)->nullable()->comment('Персональный идентификационный номер сотрудника старой ЦРМ');
            $table->json('questions_id')->default(new Expression('(JSON_ARRAY())'))->comment('Идентификаторы вопросов');
            $table->json('answer_process')->default(new Expression('(JSON_ARRAY())'))->comment('Процесс выполнения теста');
            $table->timestamp('created_at')->nullable()->comment('Время создания');
            $table->timestamp('start_at')->nullable()->comment('Время начала');
            $table->timestamp('done_at')->nullable()->comment('Время заврешения');
            $table->timestamp('updated_at')->nullable()->comment('Время обновления');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('testing_processes');
    }
}
