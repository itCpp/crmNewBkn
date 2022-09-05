<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSiteIpHidesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site_ip_hides', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('site_id')->nullable()->index();
            $table->ipAddress('ip')->nullable()->index();
            $table->timestamps();
            $table->index(['site_id', 'ip']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('site_ip_hides');
    }
}
