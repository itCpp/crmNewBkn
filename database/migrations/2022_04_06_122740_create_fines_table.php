<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fines', function (Blueprint $table) {
            $table->id();
            $table->integer('user_pin')->nullable()->comment('Персональный номер штрафуемого сотрудника')->index();
            $table->string('from_pin')->nullable()->comment('Персональный номер сотрудника, назначевшего штраф');
            $table->float('fine')->default(0)->comment('Сумма штрафа');
            $table->text('comment')->nullable()->comment('Комметарий по штрафу');
            $table->bigInteger('request_id')->nullable()->comment('Идентификатор заявки, по которой назначен штраф (если имеется)')->index();
            $table->boolean('is_autofine')->default(false)->comment('Является автоматическим штрафом');
            $table->date('fine_date')->comment('Дата штрафа')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_pin', 'fine_date', 'deleted_at'], 'user_pin_date_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fines');
    }
}
