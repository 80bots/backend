<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSchedulingInstancesDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scheduling_instances_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('scheduling_id');
            $table->string('day');
            $table->string('selected_time');
            $table->string('time_zone');
            $table->string('cron_data');
            $table->enum('schedule_type', ['start', 'stop']);
            $table->enum('status', ['active', 'inactive']);
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->foreign('scheduling_id')
                ->references('id')->on('scheduling_instances')
                ->onUpdate('cascade')->onDelete('cascade');
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
            $table->dropForeign(['scheduling_instance_id']);
        });

        Schema::dropIfExists('scheduling_instances_details');
    }
}
