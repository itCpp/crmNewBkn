<?php

namespace App\Http\Controllers\Admin\DataBases\Migrations;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateVisitsTable
{
    /**
     * Создание объекта миграции
     * 
     * @param false|string $connection
     * @return void
     */
    public function __construct($connection = false)
    {
        $this->schema = Schema::connection($connection);
    }

    /**
     * Запуск миграции
     * 
     * @return null
     */
    public function up()
    {
        if ($this->schema->hasTable('visits'))
            return null;

        $this->schema->create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('ip')->nullable();
            $table->tinyInteger('is_blocked')->default(0)->comment('1 - Блокированный вход');
            $table->text('page')->nullable();
            $table->string('method', 50)->nullable();
            $table->text('referer')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('request_data')->default(new Expression('(JSON_ARRAY())'));
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }
}
