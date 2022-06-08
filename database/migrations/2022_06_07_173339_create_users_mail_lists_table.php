<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

class CreateUsersMailListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_mail_lists', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable()->comment('Заголовок уведомления');
            $table->string('icon')->nullable()->comment('Иконка в окне уведомления');
            $table->string('type')->nullable()->comment('Тип уведомления');
            $table->text('message')->comment('Текст уведомления');
            $table->boolean('to_push')->default(1)->comment('Отправить моментальное уведомление');
            $table->boolean('to_notice')->default(1)->comment('Отправить классическое уведомление');
            $table->boolean('to_online')->default(0)->comment('Отправить только онлайн пользователям');
            $table->boolean('to_telegram')->default(0)->comment('Дублировать в Телеграм');
            $table->boolean('markdown')->default(0)->comment('Markdown оформление текста');
            $table->json('response')->default(new Expression('(JSON_ARRAY())'));
            $table->integer('author_pin')->nullable();
            $table->timestamp('done_at')->nullable()->comment('Время завершения рассылки');
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
        Schema::dropIfExists('users_mail_lists');
    }
}
