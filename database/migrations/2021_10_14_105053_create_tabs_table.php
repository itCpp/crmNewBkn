<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTabsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tabs', function (Blueprint $table) {
            $table->id();
            $table->integer('position')->nullable()->comment("Порядок расположения");
            $table->string('name', 50)->nullable()->comment("Наименование вкладки");
            $table->string('name_title', 255)->nullable()->comment("Заголовок при наведении");
            $table->json('where_settings')->nullable()->comment("Объект настроек выборки данных");
            $table->json('order_by_settings')->nullable()->comment("Условия сортировки");
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
        Schema::dropIfExists('tabs');
    }
}
