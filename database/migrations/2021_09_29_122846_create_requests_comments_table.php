<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests_comments', function (Blueprint $table) {
            $table->id();
            $table->integer('request_id')->comment('Идентификатор заявки');
            $table->string('type_comment', 50)->nullable()->comment('Тип комментария');
            $table->string('created_pin', 50)->nullable()->comment('Сотрудник, написавший комментарий. NULL - системный комментарий');
            $table->text('comment')->nullable()->comment('Текст комментария');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->comment('Время удаления');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('requests_comments');
    }
}
