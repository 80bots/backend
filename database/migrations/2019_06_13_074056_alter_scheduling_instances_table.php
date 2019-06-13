<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSchedulingInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scheduling_instances', function (Blueprint $table) {
            $table->dropColumn(['utc_start_time','utc_end_time', 'start_time', 'end_time','current_time_zone']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('scheduling_instances', function (Blueprint $table) {
            $table->string('utc_start_time');
            $table->string('utc_end_time');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('current_time_zone');
        });
    }
}
