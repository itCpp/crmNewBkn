<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TableUserAuthQueries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_auth_queries', function (Blueprint $table) {
            $table->string('auth_hash', 50)->nullable()->comment("Временный хэш для авторизации")->after('user_agent');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_auth_queries', function (Blueprint $table) {
            $table->dropColumn('auth_hash');
        });
    }
}
