<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsRowsViewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests_rows_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->comment('Идентификатор сотрудника')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('request_id')->comment('Идентификатор заявки')->constrained('requests_rows')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamp('view_at');

            $table->unique(['user_id', 'request_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('requests_rows_views');
    }
}
