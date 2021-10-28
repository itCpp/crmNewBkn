<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsRowsRequestsClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests_rows_requests_clients', function (Blueprint $table) {
            $table->foreignId('id_request')->constrained('requests_rows');
            $table->foreignId('id_requests_clients')->constrained('requests_clients');

            $table->unique(['id_request', 'id_requests_clients'], 'id_requests_clients');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('requests_rows_requests_clients');
    }
}
