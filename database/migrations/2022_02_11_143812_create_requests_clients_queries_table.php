<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsClientsQueriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests_clients_queries', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('client_id')->nullable()->comment('Идентификатор клиента')->index();
            $table->bigInteger('request_id')->nullable()->comment('Идентификатор заявки')->index();
            $table->bigInteger('source_id')->nullable()->comment('Идентификатор источника')->index();
            $table->bigInteger('resource_id')->nullable()->comment('Идентификатор ресурса')->index();
            $table->timestamp('created_at')->nullable();
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('requests_clients_queries');
    }
}
