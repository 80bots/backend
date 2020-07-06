<?php

use App\SchedulingInstancesDetails;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->string('time');
            $table->string('day');
            $table->string('week');
            $table->string('month');
            $table->string('year');
            $table->string('time_zone');

            $table->enum('schedule_type', [
                SchedulingInstancesDetails::TYPE_START,
                SchedulingInstancesDetails::TYPE_STOP
            ])->default(SchedulingInstancesDetails::TYPE_START);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('scheduling_id')
                ->references('id')
                ->on('scheduling_instances')
                ->onUpdate('cascade')
                ->onDelete('cascade');
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
            $table->dropForeign(['scheduling_id']);
        });

        Schema::dropIfExists('scheduling_instances_details');
    }
}
