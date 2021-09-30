<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAuthQueriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_auth_queries', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable()->comment('Идентификатор пользователя');
            $table->integer('callcenter_id')->nullable()->comment('Идентификатор коллцентра');
            $table->integer('sector_id')->nullable()->comment('Идентификатор сектора');
            $table->ipAddress('ip')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('done_pin', 50)->nullable()->comment('Сотрудник, принявший решение');
            $table->timestamps();
            $table->timestamp('done_at')->nullable()->comment('Время принятия решения');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_auth_queries');
    }
}
