<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallcentersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('callcenters', function (Blueprint $table) {
            $table->id()->comment("Идентификатор колл-центра");;
            $table->string('name', 50)->nullable()->comment("Наименование колл-центра");
            $table->string('comment', 250)->nullable()->comment("Краткое описание");
            $table->tinyInteger('active', 1)->default(0)->comment("0 - Деактивирован, 1 - Включен");
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
        Schema::dropIfExists('callcenters');
    }
}
