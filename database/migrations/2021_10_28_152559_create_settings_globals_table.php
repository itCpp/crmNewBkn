<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsGlobalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings_globals', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->nullable()->comment('Наименование переменной настройки');
            $table->string('value', 500)->nullable()->comment('Значение переменной');
            $table->string('type', 50)->nullable()->comment('Тип значения настройки, по умолчанию bool');
            $table->string('comment', 500)->nullable()->comment('Комментарий к настройке');
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
        Schema::dropIfExists('settings_globals');
    }
}
