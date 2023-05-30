<?php

namespace App\Http\Controllers\Admin\DataBases\Migrations;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBlocksTable
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
        if ($this->schema->hasTable('blocks'))
            return null;

        $this->schema->create('blocks', function (Blueprint $table) {
            $table->id();
            $table->string('host')->nullable();
            $table->tinyInteger('is_hostname')->default(0);
            $table->tinyInteger('is_block')->default(0)->comment('0 - Разблокирован, 1 - Заблокирован');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->nullable();

            $table->index(['host', 'is_block'], 'host_is_block');
            $table->index(['is_hostname', 'is_block'], 'is_hostname_is_block');
        });
    }
}
