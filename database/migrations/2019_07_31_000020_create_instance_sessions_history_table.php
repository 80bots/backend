<?php

use App\InstanceSessionsHistory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstanceSessionsHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('instance_sessions_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('scheduling_instances_id');
            $table->unsignedInteger('user_id');

            $table->enum('schedule_type', [
                InstanceSessionsHistory::STATUS_RUNNING,
                InstanceSessionsHistory::STATUS_STOPPED
            ]);

            $table->string('cron_data');
            $table->string('current_time_zone');

            $table->enum('status', [
                InstanceSessionsHistory::STATUS_SUCCEED,
                InstanceSessionsHistory::STATUS_FAILED
            ])->default(InstanceSessionsHistory::STATUS_SUCCEED);

            $table->timestamps();

            $table->foreign('scheduling_instances_id')
                ->references('id')
                ->on('scheduling_instances')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
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
        Schema::table('instance_sessions_history', function (Blueprint $table) {
            $table->dropForeign(['scheduling_instances_id', 'user_id']);
        });

        Schema::dropIfExists('instance_sessions_history');
    }
}
