<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatingGlobalDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rating_global_data', function (Blueprint $table) {
            $table->id();
            $table->string('pin')->comment('Персональный идентификационный номер сотрудника')->unique();
            $table->integer('requests')->default(0)->comment('Всего выдано заявок');
            $table->integer('requests_moscow')->default(0)->comment('Всего выдано московских заявок');
            $table->integer('comings')->default(0)->comment('Количество приходов');
            $table->integer('drains')->default(0)->comment('Количество сливов');
            $table->integer('agreements_firsts')->default(0)->comment('Договоры с первичных приходов');
            $table->integer('agreements_seconds')->default(0)->comment('Договоры со вторичных приходов');
            $table->bigInteger('cashbox')->default(0)->comment('Общая сумма кассы');
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
        Schema::dropIfExists('rating_global_data');
    }
}
