<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsStoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests_stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->comment('Идентификатор заявки')->constrained('requests_rows')->onUpdate('cascade')->onDelete('cascade');
            $table->json('request_data')->nullable()->comment('Новые данные');
            $table->string('created_pin', 50)->nullable()->comment('Сотрудник, внесший изменения');
            $table->timestamp('created_at')->useCurrent()->comment('Время внесения изменений');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('requests_stories');
    }
}
