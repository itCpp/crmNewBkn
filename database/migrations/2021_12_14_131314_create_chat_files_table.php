<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_files', function (Blueprint $table) {
            $table->id();
            $table->string('hash', 255)->comment('MD5 хэш файла')->index();
            $table->string('name', 255)->comment('Имя файла');
            $table->string('original_name', 255)->comment('Изначальное имя файла');
            $table->string('path', 255)->comment('Путь до файла');
            $table->string('mime_type', 255)->nullable()->comment('Тип файла');
            $table->bigInteger('size')->default(0)->comment('Размер файла');
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
        Schema::dropIfExists('chat_files');
    }
}
