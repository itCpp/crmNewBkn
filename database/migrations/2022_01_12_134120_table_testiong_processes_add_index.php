<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TableTestiongProcessesAddIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('testing_processes', function (Blueprint $table) {
            $table->index('pin');
            $table->index(['pin', 'done_at']);
            $table->index('pin_old');
            $table->index(['pin_old', 'done_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('testing_processes', function (Blueprint $table) {
            $table->dropIndex('pin');
            $table->dropIndex(['pin', 'done_at']);
            $table->dropIndex('pin_old');
            $table->dropIndex(['pin_old', 'done_at']);
        });
    }
}
