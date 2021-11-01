<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRolesPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('roles_permissions', function (Blueprint $table) {
            $table->string('role', 50);
            $table->string('permission', 100);

            $table->foreign('role')->references('role')->on('roles')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('permission')->references('permission')->on('permissions')->onUpdate('cascade')->onDelete('cascade');

            $table->unique(['role', 'permission'], 'role_permission');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('roles_permissions');
    }
}
