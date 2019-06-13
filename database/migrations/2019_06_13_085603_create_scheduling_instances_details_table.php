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
            $table->integerIncrements('id');

            $table->unsignedInteger('scheduling_instances_id');
            $table->foreign('scheduling_instances_id')
                ->references('id')->on('scheduling_instances')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->enum('schedule_type', ['start', 'stop']);

            $table->string('day');
            $table->string('selected_time');
            $table->enum('status', ['active', 'inactive']);
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scheduling_instances_details');
    }
}
