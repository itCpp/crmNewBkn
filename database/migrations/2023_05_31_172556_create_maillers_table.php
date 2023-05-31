<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaillersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('maillers', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable()->comment('Наименование почтовика');
            $table->unsignedInteger('type')->nullable()->comment('Тип обработчика');
            $table->string('destination')->comment('Адресат');
            $table->boolean('is_active')->default(false)->comment('Активное действие');
            $table->json('config')->nullable()->comment('Кофигурация обработчика');
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
        Schema::dropIfExists('maillers');
    }
}
