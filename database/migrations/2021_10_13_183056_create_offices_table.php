<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOfficesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('base_id', 20)->nullable()->comment("Идентификатор офиса в базах, для вывода записей ЮРИСКОНСУЛЬТ, БАУ, МАЯ и тд");
            $table->tinyInteger('active')->default(1)->comment("1 - Активно для выбора, 0 - Нет");
            $table->string('name', 50)->nullable()->comment("Имя для офиса");
            $table->string('addr', 250)->nullable()->comment("Короткий адрес");
            $table->string('address', 250)->nullable()->comment("Полный адрес");
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
        Schema::dropIfExists('offices');
    }
}
