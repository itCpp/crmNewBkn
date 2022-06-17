<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('account_id')->nullable()->index();
            $table->date('date')->comment('Дата рахода')->index();
            $table->integer('requests')->comment('Количество заявок')->default(0);
            $table->float('sum', 11)->comment('Сумма расходов')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'date']);
            $table->index(['account_id', 'date', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('expenses');
    }
}
