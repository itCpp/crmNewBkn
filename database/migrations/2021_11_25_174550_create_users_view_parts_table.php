<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersViewPartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_view_parts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->comment('Идентификатор пользователя');
            $table->string('part_name', 255)->comment('Наименование раздела просмотра');
            $table->timestamp('view_at')->nullable()->comment('Время последнего просмотра');

            $table->unique(['user_id', 'part_name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_view_parts');
    }
}
