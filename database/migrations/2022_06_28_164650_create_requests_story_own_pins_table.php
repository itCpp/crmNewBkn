<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

class CreateRequestsStoryOwnPinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests_story_own_pins', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('request_id')->nullable();
            $table->string('pin_before')->nullable()->comment('Персональный номер сотрудника у которого забирают заявку');
            $table->string('pin_after')->nullable()->comment('Персональный номер нового сотрудника');
            $table->boolean('is_moscow')->default(false);
            $table->string('date_create', 12)->nullable()->comment('Дата создания');
            $table->string('date_uplift', 12)->nullable()->comment('Дата обращения');
            $table->integer('status_id')->nullable()->comment('Статус заявки на момент смены');
            $table->json('request_row')->default(new Expression('(JSON_ARRAY())'))->comment('Данные экземпляра модели на момент передачи');
            $table->timestamps();

            $table->index(['pin_before', 'date_create', 'created_at'], 'pin_date_create');
            $table->index(['status_id', 'date_create', 'created_at'], 'status_id_date_create');
            $table->index(['pin_before', 'date_uplift', 'created_at'], 'pin_date_uplift');
            $table->index(['status_id', 'date_uplift', 'created_at'], 'status_id_date_uplift');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('requests_story_own_pins');
    }
}
