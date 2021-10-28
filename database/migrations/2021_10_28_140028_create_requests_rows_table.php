<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsRowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests_rows', function (Blueprint $table) {
            $table->id();
            $table->string('query_type', 50)->nullable()->comment('Тип заявки: call, text');
            $table->integer('callcenter_sector')->nullable()->comment('Идентификатор сектора колл-центра');
            $table->string('pin', 50)->nullable()->comment('Пин сотрудника, работающего над заявкой');
            $table->integer('last_phone')->nullable()->comment('Идентификатор номера телефона последнего входящего звонка');
            $table->integer('source_id')->nullable()->comment('Идентификатор источника')->index();
            $table->string('sourse_resource', 150)->nullable()->comment('Идентификатор ресурса источника');
            $table->string('client_name', 255)->nullable()->comment('ФИО клиента');
            $table->string('theme', 150)->nullable()->comment('Тематика заявки');
            $table->string('region', 150)->nullable()->comment('Город проживания');
            $table->tinyInteger('check_moscow')->nullable()->comment('1 - Москва, 0 - Регион, NULL - Не определено');
            $table->text('comment')->nullable()->comment('Описание сути обращения');
            $table->text('comment_urist')->nullable()->comment('Комментарий для юриста первичного приёма');
            $table->text('comment_first')->nullable()->comment('Первичный комментарий секретаря');
            $table->integer('status_id')->nullable()->comment('Идентификатор статуса заявки (NULL - не обработана)')->index();
            $table->json('status_icon')->nullable()->comment('Статусные иконки клиента');
            $table->integer('address')->nullable()->comment('Идентификатор офиса записи');
            $table->timestamp('event_at')->nullable()->comment('Дата события: записи, прихода и тд');
            $table->tinyInteger('uplift')->default(1)->comment('Вывод заявки в списке необработанных (при подъеме)');
            $table->timestamp('uplift_at')->nullable()->comment('Время подъема в списке');
            $table->timestamp('created_at')->nullable()->comment('Дата создания');
            $table->timestamp('updated_at')->nullable()->comment('Дата обновления');
            $table->timestamp('deleted_at')->nullable()->comment('Время удаления заявки');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('requests_rows');
    }
}
