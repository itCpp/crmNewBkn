<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsTextQueuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('requests_queues');
        Schema::create('requests_queues', function (Blueprint $table) {
            $table->id();
            $table->json('request_data')->comment('Данные для создания заявки');
            $table->bigInteger('request_id')->nullable()->comment('Идентификатор созданой заявки');
            $table->string('ip', 100)->nullable()->comment('IP адрес клиента');
            $table->string('site', 255)->nullable()->comment('Сайт-источник заявки');
            $table->string('user_agent', 500)->nullable()->comment('Информация об устройстве клиента');
            $table->string('done_pin', 50)->nullable()->comment('Идентификатор сотрудника, принявшего решение. 0 - автоматическое решение');
            $table->integer('done_type')->nullable()->comment('Принятое решение: 1 - Одобрена, 2 - Отклонена');
            $table->timestamp('done_at')->nullable()->comment('Время завершения');
            $table->timestamps();

            $table->index('request_id');
            $table->index('done_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('requests_queues');
    }
}
