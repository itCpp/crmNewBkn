<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropTabStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('tab_status');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('tab_status', function (Blueprint $table) {
            $table->foreignId('tab_id')->constrained('tabs')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('status_id')->constrained('statuses')->onUpdate('cascade')->onDelete('cascade');
            $table->unique(['tab_id', 'status_id']);
        });
    }
}
