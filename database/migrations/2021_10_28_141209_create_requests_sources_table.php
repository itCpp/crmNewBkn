<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsSourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->nullable()->comment('Наименование источника');
            $table->string('comment', 255)->nullable()->comment('Краткое описание к источнику');
            $table->tinyInteger('show_counter')->default(0)->comment('Отображать в счетчике доп. информации');
            $table->tinyInteger('actual_list')->default(0)->comment('Отображать в списке при создании новой заявки');
            $table->tinyInteger('auto_done_text_queue')->default(0)->comment('Автоматически добавлять текстовую заявку из очереди');
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
        Schema::dropIfExists('requests_sources');
    }
}
