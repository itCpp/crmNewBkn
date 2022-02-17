<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatingOperatorsPeriodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::create('rating_operators_periods', function (Blueprint $table) {
        //     $table->id();
        //     $table->date("date")->nullable()->comment('Дата начала отчетного периода');
        //     $table->string("pin", 20)->nullable()->comment('Персональный идентификационный номер');
        //     $table->integer('comings')->default(0)->comment('Количество приходов');
        //     $table->integer('requests')->default(0)->comment('Количество московских заявок');
        //     $table->integer('requests_all')->default(0)->comment('Общее количество заявок');
        //     $table->float('efficiency')->default(0)->comment('КПД на момент расчета');
        //     $table->integer('cashbox')->default(0)->comment('Касса договоров по приходам оператора');
        //     $table->integer('loading')->default(0)->comment('Нагрузка кассы оператора');
        //     $table->timestamps();
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::dropIfExists('rating_operators_periods');
    }
}
