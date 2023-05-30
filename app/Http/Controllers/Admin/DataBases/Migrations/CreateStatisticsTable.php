<?php

namespace App\Http\Controllers\Admin\DataBases\Migrations;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateStatisticsTable
{
    /**
     * Создание объекта миграции
     * 
     * @param false|string $connection
     * @return void
     */
    public function __construct(protected $connection = false)
    {
        $this->schema = Schema::connection($this->connection);
    }

    /**
     * Запуск миграции
     * 
     * @return null
     */
    public function up()
    {
        if ($this->schema->hasTable('statistics'))
            return null;

        $this->schema->create('statistics', function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable()->comment("Дата посещения");
            $table->string('ip')->nullable()->comment("IP адрес");
            $table->string('hostname')->nullable()->comment("Имя хоста");
            $table->bigInteger('visits')->default(0)->comment("Количество посещений");
            $table->bigInteger('requests')->default(0)->comment("Количество попыток оставить заявку");
            $table->bigInteger('visits_drops')->default(0)->comment("Количество блокированных посещений");

            $table->index(['date', 'ip'], 'date_ip');
        });
    }
}
