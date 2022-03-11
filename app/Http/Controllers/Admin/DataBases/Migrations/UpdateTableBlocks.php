<?php

namespace App\Http\Controllers\Admin\DataBases\Migrations;

use Exception;
use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Database\Query\Expression;
// use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateTableBlocks
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
        if (!$this->schema->hasTable('blocks'))
            throw new Exception("Таблица [blocks] не существует");

        $this->schema->table('blocks', function (Blueprint $table) {
            $table->tinyInteger('is_period')->default(0)->after('is_hostname');
            $table->bigInteger('period_start')->nullable()->after('is_period');
            $table->bigInteger('period_stop')->nullable()->after('period_start');

            $table->index(['is_period', 'is_block'], 'is_period_is_block');
            $table->index(['period_start', 'period_stop'], 'period');
        });
    }
}
