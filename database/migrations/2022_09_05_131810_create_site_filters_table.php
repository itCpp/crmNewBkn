<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSiteFiltersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site_filters', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('site_id')->nullable()->comment("Идентификатор сайта");
            $table->string('utm_label')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['site_id', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('site_filters');
    }
}
