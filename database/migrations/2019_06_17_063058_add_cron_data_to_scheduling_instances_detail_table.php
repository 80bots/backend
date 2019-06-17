<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCronDataToSchedulingInstancesDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scheduling_instances_details', function (Blueprint $table) {
            $table->string('cron_data')->after('selected_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('scheduling_instances_details', function (Blueprint $table) {
            $table->dropColumn('cron_data');
        });
    }
}
