<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTabRoleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tab_role', function (Blueprint $table) {
            $table->foreignId('tab_id')->constrained('tabs')->onUpdate('cascade')->onDelete('cascade');
            $table->string('role_id', 100);
            $table->unique(['tab_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tab_role');
    }
}
