<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedIndexesRequestsRowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('requests_rows', function (Blueprint $table) {
            $table->index('event_at', 'event_at');
            $table->index('uplift_at', 'uplift_at');
            $table->index('created_at', 'created_at');
            $table->index('updated_at', 'updated_at');
            $table->index('deleted_at', 'deleted_at');
            $table->index('check_moscow', 'check_moscow');
            $table->index('uplift', 'uplift');
            $table->index(['status_id', 'deleted_at'], 'status_id_deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('requests_rows', function (Blueprint $table) {
            $table->dropIndex('event_at');
            $table->dropIndex('uplift_at');
            $table->dropIndex('created_at');
            $table->dropIndex('updated_at');
            $table->dropIndex('check_moscow');
            $table->dropIndex('uplift');
            $table->dropIndex('status_id_deleted_at');
        });
    }
}
