<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallcenterSectorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('callcenter_sectors', function (Blueprint $table) {
            $table->id()->comment("Идентификтаор сектора");
            $table->integer('callcenter_id')->comment("Идентификатор колл-центра");
            $table->string('name', 50)->nullable()->comment("Наименование сектора");
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
        Schema::dropIfExists('callcenter_sectors');
    }
}
