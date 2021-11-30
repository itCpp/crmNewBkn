<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

class CreateIpInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ip_infos', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 255)->comment('IP адрес')->index();
            $table->string('country_code', 50)->nullable();
            $table->string('region_name', 255)->nullable();
            $table->string('city', 255)->nullable();
            $table->json('info')->default(new Expression('(JSON_ARRAY())'))->comment('Данные, полученные со сторонних сервисов');
            $table->timestamp('checked_at')->nullable()->comment('Время проверки информации');
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
        Schema::dropIfExists('ip_infos');
    }
}
