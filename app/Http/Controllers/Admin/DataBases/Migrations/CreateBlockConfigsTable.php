<?php

namespace App\Http\Controllers\Admin\DataBases\Migrations;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBlockConfigsTable
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
        if ($this->schema->hasTable('block_configs'))
            return null;

        $this->schema->create('block_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('value');
        });

        DB::connection($this->connection)->table('block_configs')->insert([
            'name' => 'COUNT_REQUESTS_TO_AUTO_BLOCK',
            'value' => 3,
        ]);
    }
}
