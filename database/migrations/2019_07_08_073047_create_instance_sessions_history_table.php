<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->unsignedInteger('scheduling_instances_id');
            $table->unsignedInteger('user_id');
            $table->enum('schedule_type', ['start', 'stop']);
            $table->string('selected_time');
            $table->enum('status', ['failed', 'succeed'])->default('succeed');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));

            $table->foreign('scheduling_instances_id')
                ->references('id')->on('scheduling_instances')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('user_id')
                ->references('id')->on('users')
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
        Schema::table('instance_sessions_history', function (Blueprint $table) {
            $table->dropForeign(['scheduling_instances_id']);
            $table->dropForeign(['user_id']);
        });
        Schema::dropIfExists('instance_sessions_history');
    }
}
