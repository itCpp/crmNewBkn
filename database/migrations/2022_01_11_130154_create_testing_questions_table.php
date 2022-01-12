<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

class CreateTestingQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('testing_questions', function (Blueprint $table) {
            $table->id();
            $table->text('question')->comment('Текст вопроса');
            $table->string('theme', 255)->nullable()->comment('Тематика вопроса, null - Общая тематика')->index();
            $table->json('answers')->comment("Варианты ответов")->default(new Expression('(JSON_ARRAY())'));
            $table->json('right_answers')->comment("Варианты правильных ответов")->default(new Expression('(JSON_ARRAY())'));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('testing_questions');
    }
}
