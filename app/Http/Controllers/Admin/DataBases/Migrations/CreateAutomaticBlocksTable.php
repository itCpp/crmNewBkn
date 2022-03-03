<?php

namespace App\Http\Controllers\Admin\DataBases\Migrations;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAutomaticBlocksTable
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
        if ($this->schema->hasTable('automatic_blocks'))
            return null;

        $this->schema->create('automatic_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('ip')->nullable();
            $table->date('date')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->index(['ip', 'date']);
        });
    }
}
