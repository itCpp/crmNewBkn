<?php

namespace App\Http\Controllers\Admin\DataBases\Migrations;

use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAutomaticBlockTable
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
        if (!$this->schema->hasTable('automatic_blocks'))
            throw new Exception("Таблица [automatic_blocks] не существует");

        $this->schema->table('automatic_blocks', function (Blueprint $table) {
            $table->tinyInteger('drop_block')->default(0)->after('date');
        });
    }
}
